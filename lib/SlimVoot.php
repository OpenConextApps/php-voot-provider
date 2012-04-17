<?php 

require_once 'lib/Voot/Provider.php';

class SlimVoot {

    private $_app;
    private $_oauthConfig;
    private $_vootConfig;

    private $_oauthStorage;
    private $_vootStorage;

    public function __construct(Slim $app, array $oauthConfig, array $vootConfig) {
        $this->_app = $app;
        $this->_oauthConfig = $oauthConfig;
        $this->_vootConfig = $vootConfig;

        $oauthStorageBackend = $this->_oauthConfig['OAuth']['storageBackend'];
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_oauthConfig[$oauthStorageBackend]);

        $vootStorageBackend = $this->_vootConfig['voot']['storageBackend'];
        require_once "lib/Voot/$vootStorageBackend.php";
        $this->_vootStorage = new $vootStorageBackend($this->_vootConfig[$vootStorageBackend]);

        // in PHP 5.4 $this is possible inside anonymous functions.
        $self = &$this;

        $this->_app->get('/groups/:name', function ($name) use ($self) {
            $self->isMemberOf($name);
        });

        $this->_app->get('/people/:name/:groupId', function ($name, $groupId) use ($self) {
            $self->getGroupMembers($name, $groupId);
        });
    }

    public function isMemberOf($name) {
        // enable CORS (http://enable-cors.org)
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");

        $as = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);

        // Apache Only!
        $httpHeaders = apache_request_headers();
        if(!array_key_exists("Authorization", $httpHeaders)) {
            throw new VerifyException("invalid_request: authorization header missing");
        }
        $authorizationHeader = $httpHeaders['Authorization'];

        $result = $as->verify($authorizationHeader);
        $g = new Provider($this->_vootStorage);
        $grp_array = $g->isMemberOf($result->resource_owner_id, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

    public function getGroupMembers($name, $groupId) {
        // enable CORS (http://enable-cors.org)
        $this->_app->response()->header("Access-Control-Allow-Origin", "*");
        $as = new AuthorizationServer($this->_oauthStorage, $this->_oauthConfig['OAuth']);

        // Apache Only!
        $httpHeaders = apache_request_headers();
        if(!array_key_exists("Authorization", $httpHeaders)) {
            throw new VerifyException("invalid_request: authorization header missing");
        }
        $authorizationHeader = $httpHeaders['Authorization'];

        $result = $as->verify($authorizationHeader);
        $g = new Provider($this->_vootStorage);
        $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

}

?>
