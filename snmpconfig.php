<?php 
$basePath = $_SERVER['DOCUMENT_ROOT'];
require_once($basePath.'/conf/config.php');
require_once($basePath."/lib/functions.php");

$db_tbl['map']="aem_snmp_mapping";
$db_tbl['map_stage']=$db_tbl['map']."_stage";
$db_tbl['mibfiles']="aem_snmp_mibfiles";
$db_tbl['objects']="aem_snmp_objects";
$db_tbl['tokens']="aem_tokens";

function list_mibs(){
	global $aem;
	$sql = "select * from aem_snmp_mibfiles order by asmf_name";
	$result = mysql_query($sql, $aem) or die(mysql_error());
	echo("<form id='form1' name='form1' enctype='multipart/form-data' method='post' action='".$_SERVER['PHP_SELF']."'>\n");
	echo <<<EOF
  Mib
  <input type="file" name="mibfile" id="mibfile" />
  <input name="action" type="hidden" value="upload" />
  <input value="Upload" type="submit" />
</form>
</p>
<table>
EOF;
	while($row = mysql_fetch_assoc($result)){
		echo("<tr><td><a href='".$_SERVER['PHP_SELF']."?action=update&id=".$row['asmf_id']."'>".$row['asmf_name']."</a></td><td><a href='".$_SERVER['PHP_SELF']."?action=delete&id=".$row['asmf_id']."' onClick='return confirm(\"Are you sure you want to delete MIB ".$row['asmf_name']."?\");'> <img src='images/delete.png' border='0' width='16' height='16' /></a></td></tr>\n");
	}
	echo("</table>\n");

}

function commit_tbl($primary,$stage){
	global $aem;
	$sql="truncate table ".$primary;
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="insert into ".$primary." select * from ".$stage;
	mysql_query($sql,$aem) or die(mysql_error());
	echo("<h3>Changes committed</h3><a href='/index.php'>Home</a></body></html>");
}

function update_mib($id){
	global $aem,$db_tbl;
	if (!isset($_REQUEST['continue']) || $_REQUEST['continue'] != "true") {
		$sql="truncate table ".$db_tbl['map_stage'];
		mysql_query($sql,$aem) or die(mysql_error());
		$sql="insert into ".$db_tbl['map_stage']." select * from ".$db_tbl['map'];
		mysql_query($sql,$aem) or die(mysql_error());
	}

	if(isset($_REQUEST['aso_id']) && isset($_REQUEST['token'])) {
		if($_REQUEST['token']=='') $_REQUEST['token']="NULL";
		$sql="INSERT INTO ".$db_tbl['map_stage']." (asm_object,asm_token) VALUES (".$_REQUEST['aso_id'].",".$_REQUEST['token'].") ON DUPLICATE KEY UPDATE asm_token=".$_REQUEST['token'];
		mysql_query($sql,$aem) or die(mysql_error());
	}

	$sql = "SELECT at_id,at_name FROM ".$db_tbl['tokens'];
	$result = mysql_query($sql, $aem) or die(mysql_error());
	while($row = mysql_fetch_assoc($result)){
		$tokens[$row['at_id']]=$row['at_name'];
	}
	$tokens['']="NULL";

	$sql = "SELECT aso_oid,aso_name,asm_id,aso_id,asm_token FROM aem_snmp_objects LEFT JOIN ".$db_tbl['map_stage']." ON aem_snmp_objects.aso_id=".$db_tbl['map_stage'].".asm_object WHERE aso_mib=".$id." ORDER BY aso_id";
	$result = mysql_query($sql, $aem) or die(mysql_error());
	echo("<form method='get' action='".$_SERVER['PHP_SELF']."' /><input type='hidden' name='action' value='commit-map' />");
	echo("<input type='submit' value='Commit' /></form>");
	echo("<table>\n");
	while($row = mysql_fetch_assoc($result)){
		echo("<tr><td>".$row['asm_id']."</td><td>".$row['aso_oid']."</td><td>".$row['aso_name']."</td><td>");
		echo("<form method='get' action='".$_SERVER['PHP_SELF']."' /><input type='hidden' name='continue' value='true' />");
		echo("<input type='hidden' name='action' value='update' /><input type='hidden' name='id' value='".$id."' />");
		echo("<input type='hidden' name='aso_id' value='".$row['aso_id']."' /><select name='token' onchange='this.form.submit()'>");
		foreach($tokens as $k => $v) {
			echo $k==$row['asm_token'] ? "<option selected=selected " : "<option ";
			echo("value='".$k."'>".$v."</option>");
		}
		echo("</select></form></td></tr>\n");
	}
	echo("</table>\n");
}

function upload_mib(){
	global $aem,$db_tbl,$basePath,$_FILES;
	$uploaddir=$basePath."/snmp/mibs/";
	$file = str_replace(array(".txt",".mib"),"",basename($_FILES['mibfile']['name'])).".txt";
	$uploadfile = $uploaddir.$file;
	move_uploaded_file($_FILES['mibfile']['tmp_name'], $uploadfile) or die("Unable to save file ".$uploadfile);
	echo("Saved file as ".$uploadfile."<br />");

	if(empty($_POST['mibname'])){
		$mibname = str_replace(array(".txt",".mib"),"",$file);
	}else{
		$mibname = $_POST['mibname'];
	}

	exec("/usr/bin/snmptranslate -Lo -To -m $mibname -M$uploaddir:/usr/share/snmp/mibs",$oids);
	$missingMibs = array();
	foreach($oids as $oid){
		if(strstr($oid,"Cannot find module")){
			$missingMibs[] = array_pop(split('\(',array_shift(split('\)',$oid))));
		}
	}
	if(sizeof($missingMibs) > 0){
		$error = "The following mibs are missing, please upload:<br><br>";
		foreach($missingMibs as $mib){
			$error .= $mib."<br>";
		}
		echo($error);
	}else{
		exec("/usr/bin/snmptranslate -Ts -m $mibname -M$uploaddir:/usr/share/snmp/mibs",$names);
		#strip off up to enterprises
		while(substr($names[0],strlen($names[0])-11) != "enterprises"){
			array_shift($names);
			array_shift($oids);
		}
		array_shift($names);
		array_shift($oids);

		$enterpriseName = array_pop(split('\.',array_shift($names)));
		$enterpriseNum = array_pop(split('\.',array_shift($oids)));
		print "<h2>Processing Mib<br>Enterprise: ".$enterpriseName." (".$enterpriseNum.")<br />";
		$startName = array_shift($names);
		$productName = array_pop(split('\.',$startName));
		$startOID = array_shift($oids);
		$productNum = array_pop(split('\.',$startOID));
		print "Product: ".$productName." (".$productNum.")</h2>";
		#strip off bottom of list
		while(substr($oids[sizeof($oids)-1],0,strlen($startOID)) != $startOID){
			array_pop($names);
			array_pop($oids);
		}
		#insert mibfile and objects into db
		$sql = "insert into ".$db_tbl['mibfiles']." (`asmf_name`, `asmf_enterprise_name`, `asmf_enterprise_num`, `asmf_product_name`, `asmf_product_num`) VALUES ('".$mibname."','".$enterpriseName."',".$enterpriseNum.",'".$productName."',".$productNum.") ON DUPLICATE KEY UPDATE asmf_name = '".$mibname."', asmf_enterprise_name = '".$enterpriseName."', asmf_product_name = '".$productName."'";
		mysql_query($sql, $aem) or die(mysql_error());
		$sql = "select asmf_id from ".$db_tbl['mibfiles']." where asmf_enterprise_num = ".$enterpriseNum." and asmf_product_num = ".$productNum;
		$result = mysql_query($sql,$aem) or die(mysql_error());
		$mibfile_id = mysql_result($result,0,0);

		for($i=0; $i<sizeof($oids); $i++){
			$sql = "insert into ".$db_tbl['objects']." (`aso_mib`, `aso_oid`, `aso_name`) VALUES (".$mibfile_id.",'".str_replace(".1.3.6.1.4.1.","",$oids[$i])."','".str_replace(".iso.org.dod.internet.private.enterprises.","",$names[$i])."') ON DUPLICATE KEY UPDATE aso_mib = ".$mibfile_id.", aso_name = '".str_replace(".iso.org.dod.internet.private.enterprises.","",$names[$i])."'";
			mysql_query($sql, $aem) or die(mysql_error());
			$sql = "select aso_id from ".$db_tbl['objects']." where aso_oid = '".str_replace(".1.3.6.1.4.1.","",$oids[$i])."'";
			$result = mysql_query($sql,$aem) or die(mysql_error());
			$object_ids[$i] = mysql_result($result,0,0);
		}
		echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
		echo("<input type='hidden' name='action' value='update'><input type='hidden' name='id' value='".$mibfile_id."'>");
		echo("<input type='submit' value='Next'></form>");
	}
}

function delete_mib($id){
	global $aem,$db_tbl;
	$sql = "SELECT aso_id FROM ".$db_tbl['objects']." WHERE aso_mib=".$id;
	$result = mysql_query($sql,$aem) or die(mysql_error());
	while($row = mysql_fetch_assoc($result)){
		$objects[]=$row['aso_id'];
	}
	foreach($objects as $object){
		$sql="DELETE FROM ".$db_tbl['map']." WHERE asm_object=".$object;
		mysql_query($sql,$aem) or die(mysql_error());
	}
	$sql="DELETE FROM ".$db_tbl['objects']." WHERE aso_mib=".$id;
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="DELETE FROM ".$db_tbl['mibfiles']." WHERE asmf_id=".$id;
	mysql_query($sql,$aem) or die(mysql_error());
	echo("Complete<br /><a href='".$_SERVER['PHP_SELF']."'>Back</a>");
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SNMP Config</title>
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/ico" href="/favicon.ico">
<style type="text/css">
.linktxt {
	font-family: Tahoma, Geneva, sans-serif;
}
.a_noline {
}
a {
	text-decoration: none;
}
</style>
<script language="javascript">
actionUrl="<?php echo $_SERVER['PHP_SELF'] ?>";
</script>
<?php htmlHead(); ?>
</head>

<body>
    <?php // load top of page and links from function
topOpg();


if(isset($_REQUEST['action'])){
	switch($_REQUEST['action']) {
		case "commit-map":
			commit_tbl($db_tbl['map'],$db_tbl['map_stage']);
			break;
		case "update":
			if(!isset($_REQUEST['id'])) die("Error: No Mib ID specified.");
			update_mib($_REQUEST['id']);
			break;
		case "upload":
			if(!isset($_FILES['mibfile'])) die("Error: No Mib File specified");
			upload_mib();
			break;
		case "delete":
			if(!isset($_REQUEST['id'])) die("Error: No Mib ID specified.");
			delete_mib($_REQUEST['id']);
			break;
		default:
			list_mibs();
	}
} else {
	list_mibs();
}

bottomOpg();
?>
</body>
</html>
