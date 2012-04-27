<?php

class SlimStorage {

    private $_app;
    private $_oauthConfig;
    private $_storageConfig;
    private $_slimOAuth;

    private $_oauthStorage;

    public function __construct(Slim $app, array $oauthConfig, array $storageConfig, SlimOAuth $s) {
        $this->_app = $app;
        $this->_oauthConfig = $oauthConfig;
        $this->_storageConfig = $storageConfig;
        $this->_slimOAuth = $s;

        $oauthStorageBackend = $this->_oauthConfig['OAuth']['storageBackend'];
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_oauthConfig[$oauthStorageBackend]);

        // in PHP 5.4 $this is possible inside anonymous functions.
        $self = &$this;

        $this->_app->get('/lrdd/', function() use ($self) {
            $self->lrdd();
        });

        $this->_app->get('/portal', function() use ($self) {
            $self->showPortal();
        });

    }
    private function isPublic($path) {
        return (substr($path, 0, 7) == 'public/');
    }
    private function hasTrailingSlash($path) {
        return ($path[strlen($path)-1]);
    }
    private function clientHasReadAccess($uid, $category, $authorizationHeader) {
//            $o = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);
//            $result = $o->verify($authorizationHeader);
//
//            $absPath = $this->_storageConfig['remoteStorage']['filesDirectory'] . DIRECTORY_SEPARATOR . 
//                    $result->resource_owner_id . DIRECTORY_SEPARATOR . 
//                    $uid . DIRECTORY_SEPARATOR . 
//                    $category . DIRECTORY_SEPARATOR . 
//                    $name;
        return true;
    }
    private function clientHasWriteAccess() {
        return true;
    }
    private function parseUriPath($uriPath) {
        $parts = explode('/', $uriPath);
        $uid = $parts[0];
        if(count($uriPath)>1) {
            $category = $parts[1];
        } else {
            $category = '';
        }
        if(count($uriPath) > 2) {
            $path = implode('/', array_slice($parts, 2));
        } else {
            $path = '';
        }
        //reassembling on-disk path from parsed parts, in case there was a bug in the parsing, we don't want to diverge auth and access:
        $absPath = $this->_storageConfig['remoteStorage']['filesDirectory']
            . DIRECTORY_SEPARATOR . $uid
            . DIRECTORY_SEPARATOR . $category
            . DIRECTORY_SEPARATOR . $path;
        return array($uid, $category, $path, $absPath);
    }
    public function handleStorageCall($method, $uriPath, $authorizationHeader=null, $data=null) {
        list($uid, $category, $path, $absPath) = $this->parseUriPath($uriPath);
        if($method == 'GET' && ($this->isPublic($path) || $this->clientHasReadAccess($uid, $category, $authorizationHeader))) {
            if($this->hasTrailingSlash($path)) {
                $this->listDir($absPath);
            } else {
                $this->getFile($absPath);
            }
        } else if($method == 'PUT' && ($this->clientHasWriteAccess($uid, $category, $authorizationHeader))) {
            $this->putFile($absPath, $data);
        } else if($method == 'DELETE') {
            $this->deleteFile($absPath);
        } else if($method == 'OPTIONS') {
            $this->options();
        } else {
            header('403 Access Denied');
            die('403 Access Denied');
        }
    }
    public function listDir($absPath) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");

        if(!file_exists($absPath) || !is_dir($absPath)) {
            $this->_app->halt(404, "File Not Found");
        }
      	$this->_app->response()->header("Content-Type", "application/json");
        $entries=[];
        if ($handle = opendir($absPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $entries[] = $entry;
                }
            }
            closedir($handle);
        }
        echo json_encode($entries);
    }
    public function getFile($absPath) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");

        if(!file_exists($absPath) || !is_file($absPath)) {
            $this->_app->halt(404, "File Not Found");
        }
//        $finfo = new finfo(FILEINFO_MIME_TYPE);
//        $this->_app->response()->header("Content-Type", $finfo->file($absPath));
        //TODO: echo MIME type from PUT back in GET
      	$this->_app->response()->header("Content-Type", "application/octet-stream");
        echo file_get_contents($absPath);
    }

    public function putFile($absPath) {
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");


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

    public function deleteFile($absPath) {

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

        // $authUri = $baseUri . "/oauth/$userAddress/authorize";
        $authUri = $baseUri . "/oauth/authorize";
        $templateUri = $baseUri . "/$userAddress/{category}/";
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        $this->_app->response()->header("Content-Type", "application/xrd+xml; charset=UTF-8");
        $this->_app->render('webFinger.php', array ( 'subject' => $subject, 'templateUri' => $templateUri, 'authUri' => $authUri));
    }

    public function showPortal() {
        $registeredClients = $this->_oauthStorage->getClients();
        $resourceOwner = $this->_slimOAuth->getResourceOwner();
        $resourceOwnerApprovals = $this->_oauthStorage->getApprovals($resourceOwner);
        $baseUri = $this->_app->request()->getUrl() . $this->_app->request()->getRootUri();
        $appLaunchFragment = "#remote_storage_uri=$baseUri&remote_storage_uid=$resourceOwner";
        $this->_app->render('portalPage.php', array ('resourceOwnerApprovals' => $resourceOwnerApprovals, 'registeredClients' => $registeredClients, 'appLaunchFragment' => $appLaunchFragment, 'resourceOwner' => $resourceOwner));
    }

}

?>