<?php

class PdoVootStorage implements IVootStorage {

    private $_c;
    private $_pdo;

    public function __construct(Config $c) {
        $this->_c = $c;
        $this->_pdo = new PDO($this->_c->getSectionValue('PdoVootStorage', 'dsn'), $this->_c->getSectionValue('PdoVootStorage', 'username', FALSE), $this->_c->getSectionValue('PdoVootStorage', 'password', FALSE));
    	$this->_pdo->exec("PRAGMA foreign_keys = ON");
    }

    public function isMemberOf($resourceOwnerId, $startIndex = null, $count = null) {
        $stmt = $this->_pdo->prepare("SELECT COUNT(*) AS count FROM membership m, groups g, roles r WHERE m.id=:id AND m.groupid = g.id AND m.role = r.id");
        $stmt->bindValue(":id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalResults = $data['count'];

        if(!is_numeric($startIndex)) {
            $startIndex = 0;
        }
        if(!is_numeric($count)) {
            $count = $totalResults;
        }

        $stmt = $this->_pdo->prepare("SELECT m.id, g.id, g.title, g.description, r.voot_membership_role FROM membership m, groups g, roles r WHERE m.id=:id AND m.groupid = g.id AND m.role = r.id LIMIT :startIndex, :count");
        $stmt->bindValue(":id", $resourceOwnerId, PDO::PARAM_STR);
        $stmt->bindValue(":startIndex", $startIndex, PDO::PARAM_INT);
        $stmt->bindValue(":count", $count, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (FALSE === $data) {
            return FALSE;
        }

        // FIXME: should itemsPerPage return the count value or the actual number of results returned?
	    return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => sizeof($data), 'entry' => $data);
    }

    public function getGroupMembers($resourceOwnerId, $groupId, $startIndex = 0, $count = null) {
        // FIXME: check whether or not $resourceOwnerId is a member of the group, if not don't 
        // return anything (or error).

        $stmt = $this->_pdo->prepare("SELECT COUNT(*) AS count FROM membership m, groups g, roles r WHERE g.id = m.groupid AND r.id=m.role AND g.id=:groupId");
        $stmt->bindValue(":groupId", $groupId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalResults = $data['count'];

        if(!is_numeric($startIndex)) {
            $startIndex = 0;
        }
        if(!is_numeric($count)) {
            $count = $totalResults;
        }

        $stmt = $this->_pdo->prepare("SELECT m.id, r.voot_membership_role FROM membership m, groups g, roles r WHERE g.id = m.groupid AND r.id=m.role AND g.id=:groupId ORDER BY r.id LIMIT :startIndex, :count");
        $stmt->bindValue(":groupId", $groupId, PDO::PARAM_STR);
        $stmt->bindValue(":startIndex", $startIndex, PDO::PARAM_INT);
        $stmt->bindValue(":count", $count, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (FALSE === $data) {
            return FALSE;
        }

        // FIXME: should itemsPerPage return the count value or the actual number of results returned?
	    return array ( 'startIndex' => $startIndex, 'totalResults' => $totalResults, 'itemsPerPage' => sizeof($data), 'entry' => $data);
    } 

}

?>
