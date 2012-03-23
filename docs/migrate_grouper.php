<?php

/*** Grouper MySQL database ***/
$dsn = 'mysql:dbname=teams;host=127.0.0.1';
$user = 'root';
$password = '';

$p = new PDO($dsn, $user, $password);

/*** ENABLE FOREIGN KEY CONSTRAINT ***/
echo "PRAGMA foreign_keys = ON;" . PHP_EOL;

/*** GROUPS ***/
$query = "
SELECT DISTINCT gg.name, gg.display_name, gg.display_extension, gg.description, gs.name AS stem_name, gs.display_name AS stem_display_name, gs.description AS stem_description
FROM grouper_groups gg, grouper_stems gs, grouper_members gm, grouper_memberships gms, grouper_fields gf, grouper_group_set ggs
WHERE gg.parent_stem = gs.id 
AND gms.member_id = gm.id
AND gms.owner_group_id = gg.id
AND gs.name != 'etc'
AND ggs.field_id = gf.id
AND gg.id = ggs.owner_group_id
AND gms.owner_id = ggs.member_id
AND gms.field_id = ggs.member_field_id
ORDER BY gg.name";

// AND ((gf.type = 'access' AND gf.name = 'viewers') OR gm.subject_id = :subject_id)

$stmt = $p->prepare($query);
$result = $stmt->execute();
if (FALSE === $result) {
    var_dump($p->errorInfo());
}
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $r) {
    echo 'INSERT INTO groups VALUES("' . $r['name'] . '","' . $r['display_extension'] . '","' . $r['description'] .'");' . PHP_EOL;
}

/*** MEMBERSHIPS ***/
/*** ROLES ***/
$query = "
SELECT gm.subject_id, gf.name AS fieldname, gg.name AS groupname  FROM grouper_memberships gms, grouper_groups gg, grouper_fields gf, grouper_stems gs, grouper_members gm  WHERE gms.field_id = gf.id AND gms.owner_group_id = gg.id  AND gms.member_id = gm.id AND gg.parent_stem = gs.id AND gs.name != 'etc' AND (gf.name = 'members') GROUP BY subject_id, groupname";

$stmt = $p->prepare($query);
$result = $stmt->execute();
if (FALSE === $result) {
    var_dump($p->errorInfo());
}
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $r) {
    $level = 10;
    echo 'INSERT INTO membership VALUES("' . $r['subject_id'] . '","' . $r['groupname'] . '",' . $level .');' . PHP_EOL;
}

$query = "
SELECT gm.subject_id, gf.name AS fieldname, gg.name AS groupname  FROM grouper_memberships gms, grouper_groups gg, grouper_fields gf, grouper_stems gs, grouper_members gm  WHERE gms.field_id = gf.id AND gms.owner_group_id = gg.id  AND gms.member_id = gm.id AND gg.parent_stem = gs.id AND gs.name != 'etc' AND (gf.name = 'updaters') GROUP BY subject_id, groupname";

$stmt = $p->prepare($query);
$result = $stmt->execute();
if (FALSE === $result) {
    var_dump($p->errorInfo());
}
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $r) {
    $level = 20;
    echo 'UPDATE membership SET role=' . $level . ' WHERE id="' . $r['subject_id'] . '" AND groupid="' . $r['groupname'] . '";' . PHP_EOL;
}

$query = "
SELECT gm.subject_id, gf.name AS fieldname, gg.name AS groupname  FROM grouper_memberships gms, grouper_groups gg, grouper_fields gf, grouper_stems gs, grouper_members gm  WHERE gms.field_id = gf.id AND gms.owner_group_id = gg.id  AND gms.member_id = gm.id AND gg.parent_stem = gs.id AND gs.name != 'etc' AND (gf.name = 'admins') GROUP BY subject_id, groupname";

$stmt = $p->prepare($query);
$result = $stmt->execute();
if (FALSE === $result) {
    var_dump($p->errorInfo());
}
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($results as $r) {
    $level = 50;
    echo 'UPDATE membership SET role=' . $level . ' WHERE id="' . $r['subject_id'] . '" AND groupid="' . $r['groupname'] . '";' . PHP_EOL;
}

?>
