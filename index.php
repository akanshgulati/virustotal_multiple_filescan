<!DOCTYPE HTML>
<html>
<head>
    <script src="js/jquery.js" type="application/javascript"></script>
    <link href="css/foundation.css" type="text/css" rel="stylesheet">
    <link href="css/style.css" type="text/css" rel="stylesheet">
</head>
<body>
<div class="row">
    <div class="column small-12" id="heading">VirusTotal Multiple File Scanning</div>
</div>
<div class="row">
    <div class="columns small-12">
        <form action="index.php" method="post" enctype="multipart/form-data">
            <fieldset>
                <legend>Upload Files</legend>
                <input type="file" name="file[]" multiple="multiple" id="file" value="Upload File(s)">
                <input type="submit" value="Upload" name="upload_submit" class="button">
                </fieldset>
        </form>
    </div>
</div>
<div class="row">
    <div class="columns small-12">
<?php
require_once('db_connect.php');
if($db->connect_errno > 0) {
    die('Unable to connect to database [' . $db->connect_error . ']');
}
if(isset($_POST['upload_submit'])) {
    $file_ary = array();
    $file_count = count($_FILES['file']['name']);
    $file_keys = array_keys($_FILES['file']);

    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $_FILES['file'][$key][$i];
        }
    }
    //var_dump($file_ary);
    foreach($file_ary as $key=> $value) {
        $file_src = $value['tmp_name'];
        $file_type = $value['type'];
        $file_dest = $_SERVER['DOCUMENT_ROOT'] . "/data/" . $value['name'];
        /*echo $file_dest.PHP_EOL;
        echo $_SERVER['DOCUMENT_ROOT'].PHP_EOL;*/
        if(move_uploaded_file($file_src, $file_dest)){
            echo "File ".$value['name']." Uploaded Successfully"."<br>";
            $query="Insert into `file_scan`(`name`) VALUES ('".$value['name']."')";
            if(!$result = $db->query($query)){
                die("Error:".$db->error);
            }
        }
    }
}   
?>
    </div>
</div>
<div class="row">
    <div class="columns small-12">

<form method="POST" name="data">
<table id="files">
    <tr>
        <td>File Name
        <td>Queue File</td>
        <td>Report</td>
    </tr>

    <?php $query="Select * from `file_scan`";
    if(!$result=$db->query($query)){
        echo "Error:".$db->error;
    }
    while($row=$result->fetch_assoc()){
        echo "<tr>";
        echo "<td>".$row['name']."</td>";
        echo "<td>";
        if($row['scan_id']==null){
            echo "<input type='checkbox' name='scan_file[]' value='".$row['id']."'>";
        }else{
        }
        echo "</td>";
        echo "<td>";
        if($row['report']) {
            echo '<a href="#" data-reveal-id="myModal" id='.$row['id'].' class="show_report">Show Report</a>';
        }
        else if($row['scan_id']) {
            echo "<input type='checkbox' name='report_file[]' value='".$row['id']."'>";
        }
        echo "</td>";
        echo "</tr>";
        /*echo "<tr class='result ".$row['id']."'><td>Antivirus</td><td colspan='2'>Result</td></tr>";*/
    }
    ?>
    <tr>
        <td></td>
        <td><input type="submit" value="Queue File" name="scan_button" class="button"/></td>
        <td><input type="submit" value="Get Report" name="report_button" class="button"/></td>
    </tr>
    
<tr><td colspan="3"><h4>
<?php
require_once 'VirusTotalApiV2.php';
ini_set('max_execution_time', 300);
$obj= new VirusTotalAPIV2("be6591b7ba93d10e26c18baf69e46b8769960676b09dfaf383d48430ffa92abb");

if(isset($_POST['scan_button'])){
    if(!isset($_POST['scan_file'])){
        die("No File Selected");
    }
    $data=$_POST['scan_file'];
    foreach($data as $id)
    {
        $query="SELECT `name` from `file_scan` WHERE `id`=".$id;
        //echo $query;
        if(!$result=$db->query($query)){
            $db->error;
        }
        $row=$result->fetch_assoc();
        $file_dest="data/".$row['name'];
        if(!$scanFile=$obj->scanFile($file_dest))
        {
            die("It`s not working, Try again later");
        }
        //var_dump($scanFile);
        $scan_id=$scanFile->scan_id;
        $query="UPDATE `file_scan` SET  `scan_id` ='".$scan_id."',`sha1`='".$scanFile->sha1."',`resource`='".$scanFile->resource."',`response_code`='".$scanFile->response_code."',`sha256`='".$scanFile->sha256."',`permalink`='".$scanFile->permalink."',`md5`='".$scanFile->md5."',`verbose_msg`='".$scanFile->verbose_msg."',`report`=0 WHERE `id` =".$id;
        if(!$result=$db->query($query)){
            die($db->error);
        }else{
            echo "File,".$row['name']." Queued Successfully, Refresh page.<br>";
        }
    }

}

if(isset($_POST['report_button'])){
    $antivirus_array=array();
    $data=$_POST['report_file'];
    foreach($data as $id) {
        $query = "SELECT `scan_id`,`name` from `file_scan` WHERE `id`=" . $id;
        //echo $query;
        if (!$result = $db->query($query)) {
            echo $db->error;
        }
        $row = $result->fetch_assoc();
        ($report_file = $obj->getFileReport($row['scan_id']));
        if(isset($report_file->scans)){
            $virus_data=$report_file->scans;
            //var_dump($report_file);
            foreach($virus_data as $virus=>$result){
                if($result->result!=null){
                    $antivirus_array[]=$virus;
                }
                $query="Insert into `".strtolower($virus)."`(`id`,`result`) VALUES('".$id."','".$result->result."')";
                if(!$result=$db->query($query)){
                    echo "Error:".$db->error;
                }
            }
            $antivirus_string=implode(",",$antivirus_array);
            //var_dump($antivirus_string);
            $query="UPDATE `file_scan` SET  `report` =1,`antivirus`='".$antivirus_string."' WHERE `id` =".$id;
            if(!$result=$db->query($query))
                echo $db->error;
            else
            {
                echo "Successfully generated report, Refresh page <br>";
            }

        }
        else
            echo("File,".$row['name']." is in queue <br>");
    }

}

?>
                    </h4>
                </td>
            </tr>
        </table>
    </form>
    </div>
</div>
<div id="myModal" class="reveal-modal xlarge" data-reveal aria-labelledby="modalTitle" aria-hidden="true" role="dialog">
    <table id="modal_table">
        <tr class="result">
            <td>Antivirus</td>
            <td>Result</td>
        </tr>
    </table>
    <a class="close-reveal-modal" aria-label="Close">&#215;</a>
</div>
<script src="js/app.js"></script>
<script src="js/foundation.min.js"></script>
<script>
    $(document).foundation();
</script>
</body>
</html>