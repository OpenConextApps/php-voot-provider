<?php

define('FTS_DIR', 0);
define('FTS_FILE', 1);
define('FTS_PARENT', 2);

class SlimStorage {

    private $_app;
    private $_c;
    private $_s;
    private $_oauthStorage;

    public function __construct(Slim $app, Config $c, Config $s) {
        $this->_app = $app;
        $this->_c = $c;
        $this->_s = $s;

        $oauthStorageBackend = $this->_c->getValue('storageBackend');
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_c);

        // in PHP 5.4 $this is possible inside anonymous functions.
        $self = &$this;

        // FIXME: we need to add matches for entries ending in "/" to redirect 
        // to directory operations

        $this->_app->get('/:uid/:category/:name', function ($uid, $category, $name) use ($self) {
            $self->getFile($uid, $category, $name);
        });

        $this->_app->put('/:uid/:category/:name', function ($uid, $category, $name) use ($self) {
            $self->putFile($uid, $category, $name);
        });

        $this->_app->delete('/:uid/:category/:name', function ($uid, $category, $name) use ($self) {
            $self->deleteFile($uid, $category, $name);
        });

        $this->_app->options('/:uid/:category/:name', function() use ($self) {
            $self->options();
        });

        $this->_app->get('/lrdd/', function() use ($self) {
            $self->lrdd();
        });

    }

    public function putFile($uid, $category, $name) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        $o = new AuthorizationServer($this->_oauthStorage, $this->_c);
        $result = $o->verify($this->_app->request()->headers("X-Authorization"));

        // we need scope for the category we want to use
        if(!AuthorizationServer::isSubsetScope($category, $result->scope)) {
            throw new VerifyException("insufficient_scope:need approved scope for this category");
        }

        // we can only put files in our own directories
        if($uid !== $result->resource_owner_id) {
            throw new VerifyException("invalid_request:unable to write to directory belong to other user");
        }
    
        // validate root directory (we know this is correct)
        $rootDirectory = $this->_s->getValue('filesDirectory') . DIRECTORY_SEPARATOR . $result->resource_owner_id; 

        if(!file_exists($rootDirectory)) {
            if (@mkdir($rootDirectory, 0775, TRUE) === FALSE) {
                $this->_app->halt(500, "Unable to create rootDirectory '$rootDirectory'");
            }
        }

        // FIXME: category should be checked!!!
        $absCategoryDirectory = $rootDirectory . DIRECTORY_SEPARATOR . $category;
        if(!file_exists($absCategoryDirectory)) {
            if (@mkdir($absCategoryDirectory, 0775, TRUE) === FALSE) {
                $this->_app->halt(500, "Unable to create categoryDirectory");
            }
        }

        // FIXME: name should be checked!!!
        $absFilePath = $absCategoryDirectory . DIRECTORY_SEPARATOR . $name;

        $contentType = $this->_app->request()->headers("Content-Type");
        file_put_contents($absFilePath, $this->_app->request()->getBody());
        // also store the accompanying mime type in the file system extended attribute
        xattr_set($absFilePath, 'mime_type', $contentType);
    }


    public function getFile($uid, $category, $name) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        if($category !== "public") {    
            $o = new AuthorizationServer($this->_oauthStorage, $this->_c);
            $result = $o->verify($this->_app->request()->headers("X-Authorization"));

            // we need scope for the category we want to use
            if(!AuthorizationServer::isSubsetScope($category, $result->scope)) {
                throw new VerifyException("insufficient_scope:need approved scope for this category");
            }

            // we can only put files in our own directories
            if($uid !== $result->resource_owner_id) {
                throw new VerifyException("invalid_request:unable to write to directory belong to other user");
            }

            // validate root directory (we know this is correct)
            $rootDirectory = $this->_s->getValue('filesDirectory') . DIRECTORY_SEPARATOR . $result->resource_owner_id; 

            // user input, we need to be very strict in checking this to make sure 
            // we cannot escape from the user directory
            $relativePath = $category . DIRECTORY_SEPARATOR . $name;

            $absFilePath = self::validatePath($rootDirectory, $relativePath, FTS_FILE);
        } else {

            // validate root directory 
            // FIXME: we have to be sure uid is not malicious!?!
            $rootDirectory = $this->_s->getValue('filesDirectory') . DIRECTORY_SEPARATOR . $uid . DIRECTORY_SEPARATOR . "public"; 

            // user input, we need to be very strict in checking this to make sure 
            // we cannot escape from the user directory
            $relativePath = $name;

            $absFilePath = self::validatePath($rootDirectory, $relativePath, FTS_FILE);
        }
        $mimeType = xattr_get($absFilePath, 'mime_type');
        $this->_app->response()->header("Content-Type", $mimeType);
        $this->_app->response()->body(file_get_contents($absFilePath));
    }

    public function deleteFile($uid, $category, $name) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        $o = new AuthorizationServer($this->_oauthStorage, $this->_c);
        $result = $o->verify($this->_app->request()->headers("X-Authorization"));

        // we need scope for the category we want to use
        if(!AuthorizationServer::isSubsetScope($category, $result->scope)) {
            throw new VerifyException("insufficient_scope:need approved scope for this category");
        }

        // we can only put files in our own directories
        if($uid !== $result->resource_owner_id) {
            throw new VerifyException("invalid_request:unable to write to directory belong to other user");
        }
    
        // validate root directory (we know this is correct)
        $rootDirectory = $this->_s->getValue('filesDirectory') . DIRECTORY_SEPARATOR . $result->resource_owner_id; 

        // user input, we need to be very strict in checking this to make sure 
        // we cannot escape from the user directory
        $relativePath = $category . DIRECTORY_SEPARATOR . $name;

        $absFilePath = self::validatePath($rootDirectory, $relativePath, FTS_FILE);

        if(!file_exists($absFilePath)) {
            if (@unlink($absFilePath) === FALSE) {
                $this->_app->halt(500, "Unable to delete file");
            }
        }
    }

    /**
     * Validate the relative path specified with the request
     * @param string $rootDirectory the root directory where to start from
     * @param string $relativePath the relative path to a file or directory
     * @param enum $validateOption can be either
     * FTS_FILE: validate that the absolute file location is inside the base file storage
     * directory and the file exists
     * FTS_DIR: validate that the absolute directory location is inside the base file storage
     * directory and that the directory exists
     * FTS_PARENT: validate that the absolute directory location of the parent is inside the
     * base file storage directory and that this parent directory exists
     * @return The absolute location of the file or directory when validated
     * @throws Exception on path/option failures
     */
    public static function validatePath($rootDirectory, $relativePath, $validateOption) {
        if ($validateOption == FTS_FILE || $validateOption == FTS_DIR) {
            $absPath = realpath($rootDirectory . DIRECTORY_SEPARATOR . $relativePath);
            $rootPos = strpos($absPath, $rootDirectory, 0);
            if ($rootPos === FALSE || $rootPos !== 0) {
                throw new Exception("invalid path ('$absPath')");
            }
            if (!file_exists($absPath)) {
                throw new Exception("path does not exist");
            }
            if ($validateOption == FTS_FILE && !is_file($absPath)) {
                throw new Exception("path is not a file");
            }
            if ($validateOption == FTS_DIR && !is_dir($absPath)) {
                throw new Exception("path is not a directory");
            }
            return $absPath;
        } else if ($validateOption == FTS_PARENT) {
            /* first validate the parent directory */
            $absPath = self::validatePath($rootDirectory, dirname($relativePath), FTS_DIR);
            /* now validate the file/directory itself */
            $baseName = basename($relativePath);
            if (empty($baseName)) {
                throw new Exception("no empty path allowed");
            }
            if (substr($baseName, 0, 1) === FALSE
                    || substr($baseName, 0, 1) === ".") {
                throw new Exception("invalid name, cannot start with '.'");
            }
            return $absPath . DIRECTORY_SEPARATOR . basename($relativePath);
        } else {
            throw new Exception("invalid validation option");
        }
    }

    public function options() {
        $this->_app->response()->header('Access-Control-Allow-Origin', $this->_app->request()->headers('Origin'));
        $this->_app->response()->header('Access-Control-Allow-Methods','GET, PUT, DELETE');
        $this->_app->response()->header('Access-Control-Allow-Headers','content-length, authorization');
    }

    public function lrdd() {
        $subject = $this->_app->request()->get('uri');
        list($x,$userAddress) = explode(":", $subject);
        $baseUri = $this->_app->request()->getUrl() . $this->_app->request()->getRootUri();
        $authUri = $baseUri . "/oauth/authorize?user_address=" . $userAddress;
        $templateUri = $baseUri . "/$userAddress/{category}/";
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        $this->_app->response()->header("Content-Type", "application/xrd+xml; charset=UTF-8");
        $this->_app->render('webFinger.php', array ( 'subject' => $subject, 'templateUri' => $templateUri, 'authUri' => $authUri));
    }
}

?>
