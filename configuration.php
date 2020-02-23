<?php
// include files
include 'core_classes/DbConnection.php';
include 'core_classes/DGEN_Generator.php';

// default database
if($_POST['db_name']){
    $dbName = $_POST['db_name'];
}else{
    $dbName = 'gestion';
}

// Database Configuration
$dbConn = new DbConnection();
$dbConn->useManualDefinition("localhost", $dbName, "root", "root");
$dbConn->doConnection();



?>
