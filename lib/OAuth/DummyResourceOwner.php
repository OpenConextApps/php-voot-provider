<?php

class DummyResourceOwner implements ResourceOwner {

    public function getResourceOwnerId() {
        return "urn:collab:person:surfnet.nl:francois";
    }

    public function getResourceOwnerDisplayName() {
        return "François Kooman";
    }

}

?>
