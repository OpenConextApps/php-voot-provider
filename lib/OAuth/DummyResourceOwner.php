<?php

class DummyResourceOwner implements IResourceOwner {

    public function getResourceOwnerId() {
        return "urn:collab:person:surfnet.nl:francois";
    }

    public function getResourceOwnerDisplayName() {
        return "François Kooman";
    }

}

?>
