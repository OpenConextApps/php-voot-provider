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
        $env = $this->_app->environment();
        // FIXME verify scope if BearerAuth is used

        $g = new Provider($this->_vootStorage);
        $grp_array = $g->isMemberOf($name, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

    public function getGroupMembers($name, $groupId) {
        $env = $this->_app->environment();
        // FIXME verify scope if BearerAuth is used

        $g = new Provider($this->_vootStorage);
        $grp_array = $g->getGroupMembers($name, $groupId, $this->_app->request()->get('startIndex'), $this->_app->request()->get('count'));
        $this->_app->response()->header('Content-Type','application/json');
        echo json_encode($grp_array);
    }

}

?>
