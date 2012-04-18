<?php

class SlimStorage {

    private $_app;
    private $_oauthConfig;
    private $_storageConfig;

    private $_oauthStorage;

    public function __construct(Slim $app, array $oauthConfig, array $storageConfig) {
        $this->_app = $app;
        $this->_oauthConfig = $oauthConfig;
        $this->_storageConfig = $storageConfig;

        $oauthStorageBackend = $this->_oauthConfig['OAuth']['storageBackend'];
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_oauthConfig[$oauthStorageBackend]);

        // in PHP 5.4 $this is possible inside anonymous functions.
        $self = &$this;

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

    public function getFile($uid, $category, $name) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");

        if($category !== "public") {    
            $o = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);

            // Apache Only!
            $httpHeaders = apache_request_headers();
            if(!array_key_exists("Authorization", $httpHeaders)) {
                throw new VerifyException("invalid_request: authorization header missing");
            }
            $authorizationHeader = $httpHeaders['Authorization'];

            $result = $o->verify($authorizationHeader);

            $absPath = $this->_storageConfig['remoteStorage']['filesDirectory'] . DIRECTORY_SEPARATOR . 
                    $result->resource_owner_id . DIRECTORY_SEPARATOR . 
                    $category . DIRECTORY_SEPARATOR . 
                    $name;
        } else {
            $absPath = $this->_storageConfig['remoteStorage']['filesDirectory'] . DIRECTORY_SEPARATOR . 
                    $uid . DIRECTORY_SEPARATOR . 
                    "public" . DIRECTORY_SEPARATOR . 
                    $name;
        }

        // user directory
        if(!file_exists(dirname(dirname($absPath)))) {
            if (@mkdir(dirname(dirname($absPath)), 0775) === FALSE) {
                $this->_app->halt(500, "Unable to create directory");
            }
        }

        // category directory
        if(!file_exists(dirname($absPath))) {
            if (@mkdir(dirname($absPath), 0775) === FALSE) {
                $this->_app->halt(500, "Unable to create directory");
            }
        }

        if(!file_exists($absPath) || !is_file($absPath)) {
            $this->_app->halt(404, "File Not Found");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->_app->response()->header("Content-Type", $finfo->file($absPath));
        echo file_get_contents($absPath);
    }

    public function putFile($uid, $category, $name) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        $o = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);

        // Apache Only!
        $httpHeaders = apache_request_headers();
        if(!array_key_exists("Authorization", $httpHeaders)) {
            throw new VerifyException("invalid_request: authorization header missing");
        }
        $authorizationHeader = $httpHeaders['Authorization'];

        $result = $o->verify($authorizationHeader);

        $absPath = $this->_storageConfig['remoteStorage']['filesDirectory'] . DIRECTORY_SEPARATOR . $result->resource_owner_id . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $name;

        // user directory
        if(!file_exists(dirname(dirname($absPath)))) {
            if (@mkdir(dirname(dirname($absPath)), 0775) === FALSE) {
                $this->_app->halt(500, "Unable to create directory");
            }
        }

        // category directory
        if(!file_exists(dirname($absPath))) {
            if (@mkdir(dirname($absPath), 0775) === FALSE) {
                $this->_app->halt(500, "Unable to create directory");
            }
        }
        file_put_contents($absPath, $this->_app->request()->getBody());
    }

    public function deleteFile($uid, $category, $name) {

    }

    public function options() {
        $this->_app->response()->header('Access-Control-Allow-Origin', $this->_app->request()->headers('Origin'));
        $this->_app->response()->header('Access-Control-Allow-Methods','GET, PUT, DELETE');
        $this->_app->response()->header('Access-Control-Allow-Headers','content-length, authorization');
    }

    public function lrdd() {
        $subject = $this->_app->request()->get('uri');
        list($x,$userAddress) = explode(":", $subject);
        
        // FIXME: too bad there is no helper function to get the RootUri including domain
        $baseUri = Slim_Http_Uri::getScheme() . "://" . $_SERVER['HTTP_HOST'] . $this->_app->request()->getRootUri();
//        $authUri = $baseUri . "/oauth/$userAddress/authorize";
	$authUri = $baseUri . "/oauth/authorize";
        $templateUri = $baseUri . "/$userAddress/{category}/";
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        $this->_app->response()->header("Content-Type", "application/xrd+xml; charset=UTF-8");
        $this->_app->render('webFinger.php', array ( 'subject' => $subject, 'templateUri' => $templateUri, 'authUri' => $authUri));
    }

}

?>
