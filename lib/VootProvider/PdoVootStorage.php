<?php

namespace VootProvider;

use \RestService\Utils\Config as Config;
use \PDO as PDO;

class PdoVootStorage implements IVootStorage
{
    private $_c;
    private $_pdo;

    public function __construct(Config $c)
    {
        $this->_c = $c;

        $driverOptions = array();
        if (TRUE === $this->_c->getSectionValue('PdoVootStorage', 'persistentConnection')) {
            $driverOptions = array(PDO::ATTR_PERSISTENT => TRUE);
        }

        $this->_pdo = new PDO($this->_c->getSectionValue('PdoVootStorage', 'dsn'), $this->_c->getSectionValue('PdoVootStorage', 'username', FALSE), $this->_c->getSectionValue('PdoVootStorage', 'password', FALSE), $driverOptions);

        if(0 === strpos($this->_c->getSectionValue('PdoVootStorage', 'dsn'), "sqlite:")) {
            // only for SQlite
            $this->_pdo->exec("PRAGMA foreign_keys = ON");
        }
    }

    public function getUserAttributes($resourceOwnerId)
    {
        $startIndex = 0;

        $stmt = $this->_pdo->prepare("SELECT id, display_name as displayName, mail FROM users WHERE id = :id");
        $stmt->bindValue(":id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve user attributes");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // backwards compatible "emails" element with array
        for ($i = 0; $i < count($data) ; $i++) {
            $data[$i]["emails"] = array($data[$i]['mail']);
        }

        return array ( 'startIndex' => $startIndex, 'totalResults' => count($data), 'itemsPerPage' => count($data), 'entry' => $data);
    }

    public function isMemberOf($resourceOwnerId, $startIndex = 0, $count = NULL)
    {
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) AS count FROM users_groups_roles ugr, groups g, roles r WHERE ugr.user_id = :user_id AND ugr.group_id = g.id AND ugr.role_id = r.id");
        $stmt->bindValue(":user_id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve membership" . var_export($this->_pdo->errorInfo(), TRUE));
        }
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalResults = $data['count'];

        if (!is_numeric($startIndex) || $startIndex < 0) {
            $startIndex = 0;
        }
        if (!is_numeric($count) || $count < 0) {
            $count = $totalResults;
        }

        $stmt = $this->_pdo->prepare("SELECT g.id, g.title, g.description, r.voot_membership_role FROM users_groups_roles ugr, groups g, roles r WHERE ugr.user_id = :user_id AND ugr.group_id = g.id AND ugr.role_id = r.id LIMIT :start_index, :count");
        $stmt->bindValue(":user_id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":start_index", $startIndex, PDO::PARAM_INT);
        $stmt->bindValue(":count", $count, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve membership");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => count($data), 'entry' => $data);
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = NULL)
    {
        // FIXME: check whether or not $resourceOwnerId is a member of the group, if not don't
        // return anything (or error).

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) AS count FROM users u, users_groups_roles ugr, groups g, roles r WHERE u.id = ugr.user_id AND g.id = ugr.group_id AND r.id = ugr.role_id AND g.id = :group_id");
        $stmt->bindValue(":group_id", $groupId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
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

        $stmt = $this->_pdo->prepare("SELECT u.id, u.display_name as displayName, u.mail, r.voot_membership_role FROM users u, users_groups_roles ugr, groups g, roles r WHERE u.id = ugr.user_id AND g.id = ugr.group_id AND r.id = ugr.role_id AND g.id = :group_id ORDER BY r.id LIMIT :start_index, :count");
        $stmt->bindValue(":group_id", $groupId, PDO::PARAM_STR);
        $stmt->bindValue(":start_index", $startIndex, PDO::PARAM_INT);
        $stmt->bindValue(":count", $count, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (FALSE === $result) {
            throw new VootStorageException("internal_server_error", "unable to retrieve members");
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // backwards compatible "emails" element with array
        for ($i = 0; $i < count($data) ; $i++) {
            $data[$i]["emails"] = array($data[$i]['mail']);
        }

        return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => count($data), 'entry' => $data);
    }

    public function addUser($id, $displayName, $mail)
    {
        $stmt = $this->_pdo->prepare("INSERT INTO users (id, display_name, mail) VALUES(:id, :display_name, :mail)");
        $stmt->bindValue(":id", $id, PDO::PARAM_STR);
        $stmt->bindValue(":display_name", $displayName, PDO::PARAM_STR);
        $stmt->bindValue(":mail", $mail, PDO::PARAM_STR);
        if (FALSE === $stmt->execute()) {
            throw new VootStorageException("internal_server_error", "unable to add user");
        }

        return 1 === $stmt->rowCount();
    }

    public function addGroup($id, $title, $description)
    {
        $stmt = $this->_pdo->prepare("INSERT INTO groups (id, title, description) VALUES(:id, :title, :description)");
        $stmt->bindValue(":id", $id, PDO::PARAM_STR);
        $stmt->bindValue(":title", $title, PDO::PARAM_STR);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);
        if (FALSE === $stmt->execute()) {
            throw new VootStorageException("internal_server_error", "unable to add group");
        }

        return 1 === $stmt->rowCount();
    }

    public function addMembership($userId, $groupId, $roleId)
    {
        $stmt = $this->_pdo->prepare("INSERT INTO users_groups_roles (user_id, group_id, role_id) VALUES(:user_id, :group_id, :role_id)");
        $stmt->bindValue(":user_id", $userId, PDO::PARAM_STR);
        $stmt->bindValue(":group_id", $groupId, PDO::PARAM_STR);
        $stmt->bindValue(":role_id", $roleId, PDO::PARAM_INT);
        if (FALSE === $stmt->execute()) {
            throw new VootStorageException("internal_server_error", "unable to add membership");
        }

        return 1 === $stmt->rowCount();
    }

    public function initDatabase()
    {
        $this->_pdo->exec("
            CREATE TABLE users (
            id VARCHAR(64) NOT NULL,
            display_name TEXT DEFAULT NULL,
            mail TEXT DEFAULT NULL,
            PRIMARY KEY (id))
        ");

        $this->_pdo->exec("
            CREATE TABLE groups (
            id VARCHAR(64) NOT NULL,
            title TEXT NOT NULL,
            description TEXT DEFAULT NULL,
            PRIMARY KEY (id))
        ");

        $this->_pdo->exec("
            CREATE TABLE roles (
            id INT(11) NOT NULL,
            voot_membership_role VARCHAR(64) NOT NULL,
            PRIMARY KEY (id))
        ");

        $this->_pdo->exec("
            CREATE TABLE users_groups_roles (
            user_id VARCHAR(64) NOT NULL,
            group_id VARCHAR(64) NOT NULL,
            role_id INT(11) NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (group_id) REFERENCES groups (id),
            FOREIGN KEY (role_id) REFERENCES roles (id))
        ");

        // add some default roles
        $this->_pdo->exec("INSERT INTO roles VALUES (10, 'member')");
        $this->_pdo->exec("INSERT INTO roles VALUES (20, 'manager')");
        $this->_pdo->exec("INSERT INTO roles VALUES (50, 'admin')");
    }

}
