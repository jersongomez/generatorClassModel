<?php
// include files
include 'core_classes/DbConnection.php';
include 'core_classes/DGEN_Generator.php';

// default database
if($_POST['db_name']){
    $dbName = $_POST['db_name'];
}else{
    $dbName = 'ggt_tareas_thursday';
}

// Database Configuration
$dbConn = new DbConnection();
$dbConn->useManualDefinition("192.168.10.10", $dbName, "root", "Ahmed.Necdet");
$dbConn->doConnection();



?>
