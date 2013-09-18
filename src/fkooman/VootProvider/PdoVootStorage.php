<?php

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

    public function getUserAttributes($resourceOwnerId)
    {
        $startIndex = 0;

        $stmt = $this->storage->prepare("SELECT id, display_name as displayName, mail FROM users WHERE id = :id");
        $stmt->bindValue(":id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (false === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve user attributes");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // fill emails element according to OpenSocial "Plural-Field"
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]["emails"] = array( array("type" => "work", "value" => $data[$i]['mail']));
            unset($data[$i]["mail"]);
        }

        return array ( 'startIndex' => $startIndex, 'totalResults' => count($data), 'itemsPerPage' => count($data), 'entry' => $data);
    }

    public function isMemberOf($resourceOwnerId, $startIndex = 0, $count = null)
    {
        $stmt = $this->storage->prepare("SELECT COUNT(*) AS count FROM users_groups_roles ugr, groups g, roles r WHERE ugr.user_id = :user_id AND ugr.group_id = g.id AND ugr.role_id = r.id");
        $stmt->bindValue(":user_id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (false === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve membership" . var_export($this->storage->errorInfo(), true));
        }
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalResults = $data['count'];

        if (!is_numeric($startIndex) || $startIndex < 0) {
            $startIndex = 0;
        }
        if (!is_numeric($count) || $count < 0) {
            $count = $totalResults;
        }

        $stmt = $this->storage->prepare("SELECT g.id, g.title, g.description, r.voot_membership_role FROM users_groups_roles ugr, groups g, roles r WHERE ugr.user_id = :user_id AND ugr.group_id = g.id AND ugr.role_id = r.id LIMIT :start_index, :count");
        $stmt->bindValue(":user_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":start_index", $startIndex, PDO::PARAM_INT);
        $stmt->bindValue(":count", $count, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (false === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve membership");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => count($data), 'entry' => $data);
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null)
    {
        // FIXME: check whether or not $resourceOwnerId is a member of the group, if not don't
        // return anything (or error).

        $stmt = $this->storage->prepare("SELECT COUNT(*) AS count FROM users u, users_groups_roles ugr, groups g, roles r WHERE u.id = ugr.user_id AND g.id = ugr.group_id AND r.id = ugr.role_id AND g.id = :group_id");
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

        $stmt = $this->storage->prepare("SELECT u.id, u.display_name as displayName, u.mail, r.voot_membership_role FROM users u, users_groups_roles ugr, groups g, roles r WHERE u.id = ugr.user_id AND g.id = ugr.group_id AND r.id = ugr.role_id AND g.id = :group_id ORDER BY r.id LIMIT :start_index, :count");
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

        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => count($data), 'entry' => $data);
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
        $stmt = $this->storage->prepare("INSERT INTO groups (id, title, description) VALUES(:id, :title, :description)");
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
        $stmt = $this->storage->prepare("INSERT INTO users_groups_roles (user_id, group_id, role_id) VALUES(:user_id, :group_id, :role_id)");
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":group_id", $groupId, PDO::PARAM_STR);
        $stmt->bindValue(":role_id", $roleId, PDO::PARAM_INT);
        if (false === $stmt->execute()) {
            throw new VootStorageException("internal_server_error", "unable to add membership");
        }

        return 1 === $stmt->rowCount();
    }

    public function initDatabase()
    {
        $this->storage->exec("
            CREATE TABLE users (
            id VARCHAR(64) NOT NULL,
            display_name TEXT DEFAULT NULL,
            mail TEXT DEFAULT NULL,
            PRIMARY KEY (id))
        ");

        $this->storage->exec("
            CREATE TABLE groups (
            id VARCHAR(64) NOT NULL,
            title TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            PRIMARY KEY (id))
        ");

        $this->storage->exec("
            CREATE TABLE roles (
            id INT(11) NOT NULL,
            voot_membership_role VARCHAR(64) NOT NULL,
            PRIMARY KEY (id))
        ");

        $this->storage->exec("
            CREATE TABLE users_groups_roles (
            user_id VARCHAR(64) NOT NULL,
            group_id VARCHAR(64) NOT NULL,
            role_id INT(11) NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (group_id) REFERENCES groups (id),
            FOREIGN KEY (role_id) REFERENCES roles (id))
        ");

        // add some default roles
        $this->storage->exec("INSERT INTO roles VALUES (10, 'member')");
        $this->storage->exec("INSERT INTO roles VALUES (20, 'manager')");
        $this->storage->exec("INSERT INTO roles VALUES (50, 'admin')");
    }
}
