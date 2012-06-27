<?php 

require_once 'lib/Voot/Provider.php';

class SlimVoot {

    private $_app;
    private $_v;

    private $_vootStorage;

    public function __construct(Slim $app, Config $v) {
        $this->_app = $app;
        $this->_v = $v;

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
        $g = new Provider($this->_vootStorage);
        $grp_array = $g->isMemberOf($result->resource_owner_id, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

    public function getGroupMembers($name, $groupId) {
        $g = new Provider($this->_vootStorage);
        $grp_array = $g->getGroupMembers($result->resource_owner_id, $groupId, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

}

?>
