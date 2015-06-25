<?php
require_once("db_connect.php");
if(isset($_GET['id'])) {
    $id=$_GET['id'];
    $json_array=array();
    $query = "SELECT `antivirus` from `file_scan` WHERE `id`=".$id;
    //echo $query;
    if (!$result = $db->query($query)) {
        $db->error;
    }
    $row = $result->fetch_assoc();
//    var_dump($row);
    if($row['antivirus']==null){
        $json_array['Report']="Your file has no vulnerability";
        die( json_encode($json_array));
    }
    $antiviruses = explode(',', $row['antivirus']);
    foreach($antiviruses as $antivirus){
        $query="Select `result` from `".strtolower($antivirus)."` Where `id`='".$id."'";
        if(!$result=$db->query($query))
            echo $db->error;
        $row=$result->fetch_assoc();
        $json_array[$antivirus]=$row["result"];
    }
    echo json_encode($json_array);
}