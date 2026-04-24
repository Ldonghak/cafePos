<?php
require 'db.php';
$menus = getDB()->query('SELECT id, name FROM menus')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($menus, JSON_UNESCAPED_UNICODE);
?>
