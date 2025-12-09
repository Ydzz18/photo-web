<?php
require_once 'db_connect.php';

$pdo = getDBConnection();
$sql = file_get_contents('db/migration_add_contact_messages.sql');

try {
    $pdo->exec($sql);
    echo 'Migration applied successfully!';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
