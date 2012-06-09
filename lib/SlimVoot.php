<?php 

require_once 'lib/Voot/Provider.php';
require_once 'lib/OAuth/ResourceServer.php';

class SlimVoot {

    private $_app;
    private $_c;
    private $_v;

    private $_oauthStorage;
    private $_vootStorage;

    public function __construct(Slim $app, Config $c, Config $v) {
        $this->_app = $app;
        $this->_c = $c;
        $this->_v = $v;

        $oauthStorageBackend = $this->_c->getValue('storageBackend');
        require_once "lib/OAuth/$oauthStorageBackend.php";
        $this->_oauthStorage = new $oauthStorageBackend($this->_c);

        $vootStorageBackend = $this->_v->getValue('storageBackend');
        require_once "lib/Voot/$vootStorageBackend.php";
        $this->_vootStorage = new $vootStorageBackend($this->_v);

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
        $rs = new ResourceServer($this->_oauthStorage, $this->_c);
        $result = $rs->verify($this->_app->request()->headers("X-Authorization"));
        $g = new Provider($this->_vootStorage);
        $grp_array = $g->isMemberOf($result->resource_owner_id, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

    public function getGroupMembers($name, $groupId) {
        $rs = new ResourceServer($this->_oauthStorage, $this->_c);
        $result = $rs->verify($this->_app->request()->headers("X-Authorization"));
        $g = new Provider($this->_vootStorage);
        $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

}

?>
