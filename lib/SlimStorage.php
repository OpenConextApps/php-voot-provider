<?php

class SlimStorage {

    private $_app;
    private $_oauthConfig;
    private $_storageConfig;
    private $_slimOAuth;

    private $_oauthStorage;

    public function __construct($app, array $oauthConfig, array $storageConfig, SlimOAuth $s) {
        $this->_app = $app;
        $this->_oauthConfig = $oauthConfig;
        $this->_storageConfig = $storageConfig;
        $this->_slimOAuth = $s;

        $oauthStorageBackend = $this->_oauthConfig['OAuth']['storageBackend'];
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_oauthConfig[$oauthStorageBackend]);

        if($this->_app) {
            // in PHP 5.4 $this is possible inside anonymous functions.
            $self = &$this;

            $this->_app->get('/lrdd/', function() use ($self) {
                $self->lrdd();
            });

            $this->_app->get('/portal', function() use ($self) {
                $self->showPortal();
            });
        }
    }
    private function isPublic($path) {
        return (substr($path, 0, 7) == 'public/');
    }
    private function hasTrailingSlash($path) {
        return ($path[strlen($path)-1]=='/');
    }
    private function clientHasReadAccess($uid, $category, $authorizationHeader) {
        $o = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);
        $result = $o->verify($authorizationHeader);
        $scopes = AuthorizationServer::getScopeArray($result->scope);
        return (in_array($category.':r', $scopes) || in_array($category.':rw', $scopes));
    }
    private function clientHasWriteAccess($uid, $category, $authorizationHeader) {
        $o = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);
        $result = $o->verify($authorizationHeader);
        $scopes = AuthorizationServer::getScopeArray($result->scope);
//var_dump($scopes);die();
        return (in_array($category.':rw', $scopes));
    }
    private function parseUriPath($uriPath) {
        $parts = explode('/', $uriPath);
        $userAddressParts = explode('@', $parts[2]);
        if(count($userAddressParts) == 2) {
            list($userName, $userHost) = $userAddressParts;
        } else {
            list($userName, $userHost) = array('unknown.user', 'unknown.host');
        }
        if(count($parts) > 3) {
            $category = $parts[3];
        } else {
            $category = '';
        }
        if(count($parts) > 3) {
            $path = implode('/', array_slice($parts, 4));
        } else {
            $path = '';
        }
        //reassembling on-disk path from parsed parts, in case there was a bug in the parsing, we don't want to diverge auth and access:
        $absPath = $this->_storageConfig['remoteStorage']['filesDirectory']
            . DIRECTORY_SEPARATOR . $userHost . DIRECTORY_SEPARATOR . $userName . DIRECTORY_SEPARATOR;
        if(strlen($category)) {
            $absPath .= $category . DIRECTORY_SEPARATOR;
            if(strlen($path)) {
                $absPath .= $path;
            }
        }
        return array($userName.'@'.$userHost, $category, $path, $absPath);
    }
    public function handleStorageCall($method, $uriPath, $origin='*', $authorizationHeader=null, $contentTypeHeader='application/octet-stream', $data=null) {
        //var_dump(array($method, $uriPath, $origin, $authorizationHeader, $contentTypeHeader, $data, $this->parseUriPath($uriPath)));
        $this->options($origin);
        list($uid, $category, $path, $absPath) = $this->parseUriPath($uriPath);
        if($method == 'GET' && ($this->isPublic($path) || $this->clientHasReadAccess($uid, $category, $authorizationHeader))) {
            if($this->hasTrailingSlash($path)) {
                $this->listDir($absPath);
            } else {
                $this->getFile($absPath);
            }
        } else if($method == 'PUT' && ($this->clientHasWriteAccess($uid, $category, $authorizationHeader))) {
            $this->putFile($absPath, $data, $contentTypeHeader);
        } else if($method == 'DELETE') {
            $this->deleteFile($absPath);
        } else if($method == 'OPTIONS') {
            //$this->options();
        } else {
            header('HTTP/1.0 403 Access Denied');
            die('403 Access Denied');
        }
    }
    public function listDir($absPath) {
      	header('Content-Type: application/json');
        $entries = array();
        if(file_exists($absPath) && is_dir($absPath) && $handle = opendir($absPath)) {
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
        if(!file_exists($absPath) || !is_file($absPath)) {
            header('HTTP/1.0 404 File Not Found');
            die('404 File Not Found');
        }
        //$finfo = new finfo(FILEINFO_MIME_TYPE);
        //header('Content-Type: '.$finfo->file($absPath));
      	header('Content-Type: application/octet-stream');
        echo file_get_contents($absPath);
    }

    public function putFile($absPath, $data, $mimeType) {
        $pathParts = explode('/', $absPath);
        for($i=2; $i < count($pathParts); $i++) {
            $parentPath = '/'.implode('/', array_slice($pathParts, 0, $i)); 
            if(!file_exists($parentPath)) {
                if (@mkdir($parentPath, 0775) === FALSE) {
                    die('Unable to create directory: '.$parentPath);
                }
            }
        }
        file_put_contents($absPath, $data);
    }

    public function deleteFile($absPath) {

    }

    public function options($origin) {
        header('Access-Control-Allow-Methods: GET, PUT, DELETE');
        header('Access-Control-Allow-Origin: '.$origin);
        header('Access-Control-Allow-Headers: content-length, authorization');
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

    private static function _getAuthorizationHeader() {
        // Apache Only!
        $httpHeaders = apache_request_headers();
        if(!array_key_exists("Authorization", $httpHeaders)) {
            throw new VerifyException("invalid_request: authorization header missing");
        }
        return $httpHeaders['Authorization'];
    }

}

?>
