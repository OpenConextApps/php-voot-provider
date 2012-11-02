<?php

namespace VootProvider;

interface IVootStorage
{
    public function getUserAttributes($resourceOwnerId);
    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null);
    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null);

}
