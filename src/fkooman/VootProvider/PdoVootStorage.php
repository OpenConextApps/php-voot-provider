<?php

/**
* Copyright 2013 François Kooman <fkooman@tuxed.net>
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/

namespace fkooman\VootProvider;

use fkooman\Config\Config;
use PDO;

class PdoVootStorage implements VootStorageInterface
{
    private $config;
    private $storage;

    public function __construct(Config $c)
    {
        $this->config = $c;

        $driverOptions = array();
        if ($this->config->s('PdoVootStorage')->l('persistentConnection')) {
            $driverOptions = array(PDO::ATTR_PERSISTENT => true);
        }

        $this->storage = new PDO(
            $this->config->s('PdoVootStorage')->l('dsn', true),
            $this->config->s('PdoVootStorage')->l('username', false),
            $this->config->s('PdoVootStorage')->l('password', false),
            $driverOptions
        );

        if (0 === strpos($this->config->s('PdoVootStorage')->l('dsn'), "sqlite:")) {
            // only for SQlite
            $this->storage->exec("PRAGMA foreign_keys = ON");
        }
    }

    public function isMemberOf($resourceOwnerId, $startIndex = 0, $count = null)
    {
        $query = <<< EOQ
SELECT
    COUNT(*) AS count
FROM
    users_groups_roles ugr,
    groups g,
    roles r
WHERE
    ugr.user_id = :user_id AND ugr.group_id = g.id AND ugr.role_id = r.id
EOQ;

        $stmt = $this->storage->prepare($query);
        $stmt->bindValue(":user_id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (false === $result) {
            throw new VootStorageException(
                "internal_server_error",
                "unable to retrieve membership"
            );
        }
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalResults = $data['count'];

        if (!is_numeric($startIndex) || $startIndex < 0) {
            $startIndex = 0;
        }
        if (!is_numeric($count) || $count < 0) {
            $count = $totalResults;
        }

        $query = <<< EOQ
SELECT
    g.id, g.title, g.description, r.voot_membership_role
FROM
    users_groups_roles ugr,
    groups g,
    roles r
WHERE
    ugr.user_id = :user_id AND ugr.group_id = g.id AND ugr.role_id = r.id
LIMIT :start_index, :count;
EOQ;

        $stmt = $this->storage->prepare($query);
        $stmt->bindValue(":user_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":start_index", $startIndex, PDO::PARAM_INT);
        $stmt->bindValue(":count", $count, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (false === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve membership");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array(
            'startIndex' => intval($startIndex),
            'totalResults' => intval($totalResults),
            'itemsPerPage' => count($data),
            'entry' => $data
        );
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null)
    {
        // FIXME: check whether or not $resourceOwnerId is a member of the group, if not don't
        // return anything (or error).
        $query = <<< EOQ
SELECT
    COUNT(*) AS count
FROM
    users u,
    users_groups_roles ugr,
    groups g,
    roles r
WHERE
    u.id = ugr.user_id AND g.id = ugr.group_id AND r.id = ugr.role_id AND g.id = :group_id
EOQ;

        $stmt = $this->storage->prepare($query);
        $stmt->bindValue(":group_id", $groupId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (false === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve members");
        }
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalResults = $data['count'];

        if (!is_numeric($startIndex) || $startIndex < 0) {
            $startIndex = 0;
        }
        if (!is_numeric($count) || $count < 0) {
            $count = $totalResults;
        }

        $query = <<< EOQ
SELECT
    u.id,
    u.display_name as displayName,
    u.mail,
    r.voot_membership_role
FROM
    users u,
    users_groups_roles ugr,
    groups g,
    roles r
WHERE
    u.id = ugr.user_id AND g.id = ugr.group_id AND r.id = ugr.role_id AND g.id = :group_id
ORDER BY r.id
LIMIT :start_index, :count
EOQ;

        $stmt = $this->storage->prepare($query);
        $stmt->bindValue(":group_id", $groupId, PDO::PARAM_STR);
        $stmt->bindValue(":start_index", $startIndex, PDO::PARAM_INT);
        $stmt->bindValue(":count", $count, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (false === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve members");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // fill emails element according to OpenSocial "Plural-Field"
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]["emails"] = array( array("type" => "work", "value" => $data[$i]['mail']));
            unset($data[$i]["mail"]);
        }

        return array(
            'startIndex' => intval($startIndex),
            'totalResults' => intval($totalResults),
            'itemsPerPage' => count($data),
            'entry' => $data
        );
    }

    public function addUser($id, $displayName, $mail)
    {
        $stmt = $this->storage->prepare("INSERT INTO users (id, display_name, mail) VALUES(:id, :display_name, :mail)");
        $stmt->bindValue(":id", $id, PDO::PARAM_STR);
        $stmt->bindValue(":display_name", $displayName, PDO::PARAM_STR);
        $stmt->bindValue(":mail", $mail, PDO::PARAM_STR);
        if (false === $stmt->execute()) {
            throw new VootStorageException("internal_server_error", "unable to add user");
        }

        return 1 === $stmt->rowCount();
    }

    public function addGroup($id, $title, $description)
    {
        $stmt = $this->storage->prepare(
            "INSERT INTO groups (id, title, description) VALUES(:id, :title, :description)"
        );
        $stmt->bindValue(":id", $id, PDO::PARAM_STR);
        $stmt->bindValue(":title", $title, PDO::PARAM_STR);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);
        if (false === $stmt->execute()) {
            throw new VootStorageException("internal_server_error", "unable to add group");
        }

        return 1 === $stmt->rowCount();
    }

    public function addMembership($userId, $groupId, $roleId)
    {
        $stmt = $this->storage->prepare(
            "INSERT INTO users_groups_roles (user_id, group_id, role_id) VALUES(:user_id, :group_id, :role_id)"
        );
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":group_id", $groupId, PDO::PARAM_STR);
        $stmt->bindValue(":role_id", $roleId, PDO::PARAM_INT);
        if (false === $stmt->execute()) {
            throw new VootStorageException("internal_server_error", "unable to add membership");
        }

        return 1 === $stmt->rowCount();
    }
}
