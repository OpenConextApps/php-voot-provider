<?php

class Groups {
    
    private $_pdo;

    public function __construct(PDO $pdo) {
        $this->_pdo = $pdo;
    }

    public function getGroups($resourceOwnerId) {
	$x = $this->_pdo->exec("PRAGMA foreign_keys = ON");
        $stmt = $this->_pdo->prepare("SELECT m.id, g.id, g.title, g.description, r.voot_membership_role FROM membership m, groups g, roles r WHERE m.id=:id AND m.groupid = g.id AND m.role = r.id");
        $stmt->bindValue(":id", $resourceOwnerId, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (FALSE === $result) {
            return FALSE;
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (FALSE === $data) {
            return FALSE;
        }
	$returnData = array ( 'startIndex' => 0, 'totalResults' => sizeof($data), 'itemsPerPage' => sizeof($data), 'entry' => $data);
        return $returnData;
    }

}
?>
