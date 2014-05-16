<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>AEM - Translation Config</title>
<body style="width: 90%">

<?php 
$basePath = $_SERVER['DOCUMENT_ROOT'];
require_once($basePath.'/conf/config.php');
require_once($basePath.'/lib/functions.php');

$db_tbl['primary']="aem_translation";
$db_tbl['stage']=$db_tbl['primary']."_stage";
$db_col['prefix']="atran_";
$db_col['step']=$db_col['prefix']."step";
$db_col['seq'] =$db_col['prefix']."sequence";
$db_col['match'] =$db_col['prefix']."match";
$db_col['value'] =$db_col['prefix']."value";

function moveUp($step,$seq) {
	global $aem,$db_tbl,$db_col;
	$sql="update ".$db_tbl['stage']." set ".$db_col['seq']."=-1";
	$sql.=" where ".$db_col['step']."='".$step."'";
	$sql.=" and ".$db_col['seq']."='".$seq."'";
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="update ".$db_tbl['stage']." set ".$db_col['seq']."=".$seq;
	$sql.=" where ".$db_col['step']."='".$step."'";
	$sql.=" and ".$db_col['seq']."='".($seq-1)."'";
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="update ".$db_tbl['stage']." set ".$db_col['seq']."=".($seq-1);
	$sql.=" where ".$db_col['step']."='".$step."'";
	$sql.=" and ".$db_col['seq']."='-1'";
	mysql_query($sql,$aem) or die(mysql_error());
}
function moveDn($step,$seq) {
	global $aem,$db_tbl,$db_col;
	$sql="update ".$db_tbl['stage']." set ".$db_col['seq']."=-1";
	$sql.=" where ".$db_col['step']."='".$step."'";
	$sql.=" and ".$db_col['seq']."='".$seq."'";
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="update ".$db_tbl['stage']." set ".$db_col['seq']."=".$seq;
	$sql.=" where ".$db_col['step']."='".$step."'";
	$sql.=" and ".$db_col['seq']."='".($seq+1)."'";
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="update ".$db_tbl['stage']." set ".$db_col['seq']."=".($seq+1);
	$sql.=" where ".$db_col['step']."='".$step."'";
	$sql.=" and ".$db_col['seq']."='-1'";
	mysql_query($sql,$aem) or die(mysql_error());
}

topOpg();  // load top of page and links from function

if (isset($_REQUEST['action']) && $_REQUEST['action'] == "commit") {
	$sql="truncate table ".$db_tbl['primary'];
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="insert into ".$db_tbl['primary']." select * from ".$db_tbl['stage'];
	mysql_query($sql,$aem) or die(mysql_error());
	echo("<h3>Changes committed</h3><a href='/index.php'>Home</a></body></html>");
	die;
} elseif (isset($_REQUEST['continue']) && $_REQUEST['continue'] != "true") {
	$sql="truncate table ".$db_tbl['stage'];
	mysql_query($sql,$aem) or die(mysql_error());
	$sql="insert into ".$db_tbl['stage']." select * from ".$db_tbl['primary'];
	mysql_query($sql,$aem) or die(mysql_error());
}

if(isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case "moveup":
			moveUp($_REQUEST['step'],$_REQUEST['sequence']);
			break;
		case "movedown":
			moveDn($_REQUEST['step'],$_REQUEST['sequence']);
			break;
		case "delete":
			$sql="delete from ".$db_tbl['stage'];
			$sql.=" where ".$db_col['step']."='".$_REQUEST['step']."'";
			$sql.=" and ".$db_col['seq']."='".$_REQUEST['sequence']."'";
			mysql_query($sql,$aem) or die(mysql_error());
			$sql="select max(".$db_col['seq'].") max from ".$db_tbl['stage'];
			$sql.=" where ".$db_col['step']."='".$_REQUEST['step']."'";
			$maxres=mysql_query($sql,$aem) or die(mysql_error());
			$max=mysql_fetch_assoc($maxres);
			if ($_REQUEST['sequence'] < $max['max']) {
				for ($i=$_REQUEST['sequence']; $i<=$max['max']; $i++) {
					$sql="update ".$db_tbl['stage']." set ".$db_col['seq']."=".($i-1);
					$sql.=" where ".$db_col['step']."='".$_REQUEST['step']."'";
					$sql.=" and ".$db_col['seq']."='".$i."'";
					mysql_query($sql,$aem) or die(mysql_error());
				}
			}
			break;
		case "add":
			if (isset($_REQUEST['done']) && $_REQUEST['done'] == 'true') {
				$search = '';
				$replace = '.*';
				$_REQUEST['config'] = array_replace($_REQUEST['config'],array_fill_keys(array_keys($_REQUEST['config'], $search),$replace));
				foreach($_REQUEST['config'] as &$config) {
					$config = "(".$config.")";
				}
				$match = implode('\|',$_REQUEST['config']);
				$sql="select max(".$db_col['seq'].") max from ".$db_tbl['stage'];
				$sql.=" where ".$db_col['step']."='".$_REQUEST['step']."'";
				$maxres=mysql_query($sql,$aem) or die(mysql_error());
				$max=mysql_fetch_assoc($maxres);
				$seq = $max['max'] + 1;
				$sql  = "INSERT INTO ".$db_tbl['stage']." (".$db_col['step'].", ".$db_col['seq'].", ".$db_col['match'].", ".$db_col['value'].")";
				$sql .= " VALUES ('".$_REQUEST['step']."', '".$seq."', '".mysql_real_escape_string($match)."', '".mysql_real_escape_string($_REQUEST['translation'])."')";
				mysql_query($sql, $aem) or die(mysql_error());
				echo("<script language='Javascript'> alert ('Translation added, step: ".$_REQUEST['step'].", sequence: ".$seq."') </script>");
				echo("<script language='Javascript'> window.location='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&continue=true' </script>");
			} else {
				echo("<h4>Insert new step</h4><table><tr>");
				echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
				echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
				echo("<input type=hidden name='action' value='add' />");
				echo("<input type=hidden name='continue' value='true' />");
				foreach($_REQUEST['config'] as $k => $v) {
					echo("<td>".$v."<br /><input type=text name='config[".$k."]' /></td>");
				}
				echo("</tr></table>");
				echo("<table><tr><td>Output<br /><input type=text size=150 name='translation' />");
				echo("<input type='hidden' name='done' value='true' />");
				echo("<input type=submit value='Add' /></form></td></tr></table>");
				echo("</td></tr></table>");
				echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
				echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
				echo("<input type=hidden name='continue' value='true' />");
				echo("<input type=submit value='Cancel' /></form>");
				die("</body></html>");
			}
			break;
		case "modify":
			if (isset($_REQUEST['done']) && $_REQUEST['done'] == 'true') {
				$search = '';
				$replace = '.*';
				$_REQUEST['token'] = array_replace($_REQUEST['token'],array_fill_keys(array_keys($_REQUEST['token'], $search),$replace));
				foreach($_REQUEST['token'] as &$token) {
					$token = "(".$token.")";
				}
				$match = implode('\|',$_REQUEST['token']);
				$sql  = "UPDATE ".$db_tbl['stage']." SET ".$db_col['match']."='".mysql_real_escape_string($match)."', ".$db_col['value']."='".mysql_real_escape_string($_REQUEST['translation'])."'";
				$sql .= " WHERE ".$db_col['step']."='".$_REQUEST['step']."' AND ".$db_col['seq']."='".$_REQUEST['sequence']."'";
				mysql_query($sql, $aem) or die(mysql_error());
				echo("<script language='Javascript'> alert ('Translation modified, step: ".$_REQUEST['step'].", sequence: ".$_REQUEST['sequence']."') </script>");
				echo("<script language='Javascript'> window.location='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&continue=true' </script>");
			} else {
				echo("<table><tr>");
				echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
				echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
				echo("<input type=hidden name='action' value='modify' />");
				echo("<input type=hidden name='done' value='true' />");
				echo("<input type=hidden name='continue' value='true' />");
				echo("<input type=hidden name='sequence' value='".$_REQUEST['sequence']."' />");
				foreach($_REQUEST['config'] as $k => $v) {
					echo("<td>".$v."<input type=text name='token[".$k."]' value='".$_REQUEST['token'][$k]."' /></td>");
				}
				echo("</tr></table><table><tr><td>Output<br /><input type=text size=150 name='translation' value='".$_REQUEST['translation']."' />");
				echo("<input type=submit value='Modify' /></form></td></tr></table>");
				echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
				echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
				echo("<input type=hidden name='continue' value='true' />");
				echo("<input type=submit value='Cancel' /></form>");
				die("</body></html>");
			}
			break;
		case "newseq":
			if(isset($_REQUEST['step']) && isset($_REQUEST['sequence']) && isset($_REQUEST['endseq'])) {
				if($_REQUEST['endseq'] == $_REQUEST['sequence']) {
					break;
				} elseif($_REQUEST['endseq'] > $_REQUEST['sequence']) {
					$sql = "SELECT * FROM ".$db_tbl['stage']." WHERE ".$db_col['step']."='".$_REQUEST['step']."'";
					$result = mysql_query($sql, $aem) or die(mysql_error());
					$nr = mysql_num_rows($result);
					if($_REQUEST['endseq'] > $nr) $_REQUEST['endseq'] = $nr;
					for($i = $_REQUEST['sequence']; $i < $_REQUEST['endseq']; $i++) {
						moveDn($_REQUEST['step'],$i);
					}
				} else {
					if($_REQUEST['endseq'] < 1) $_REQUEST['endseq'] = 1;
					for($i = $_REQUEST['sequence']; $i > $_REQUEST['endseq']; $i--) {
						moveUp($_REQUEST['step'],$i);
					}
				}
			}
			break;
	}
}

$sql = "SELECT at_name FROM aem_step_config, aem_tokens";
$sql.= " WHERE at_id = asc_token AND asc_step = ".$_REQUEST['step'];
$sql.= " ORDER BY asc_sequence";
$config =  mysql_query($sql,$aem) or die(mysql_error());
$configs=array();
while($row = mysql_fetch_assoc($config)) {
	array_push($configs,$row['at_name']);
}

$sql = "select * from ".$db_tbl['stage']." where atran_step = ".$_REQUEST['step']." order by atran_sequence";
$translations = mysql_query($sql, $aem) or die(mysql_error());

$sql = "select as_name, at_name from aem_step, aem_tokens where as_return_token = at_id and as_id = ".$_REQUEST['step'];
$result = mysql_query($sql, $aem) or die(mysql_error());
$stepName = mysql_fetch_assoc($result);

echo("You are working in a temporary table.  Nothing will be saved until you click on 'Commit Changes'.<br />");
echo("<h3>".$stepName['as_name']."</h3>");
echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
echo("<input type=hidden name='action' value='commit' />");
echo("<input type=submit value='Commit Changes' /></form>");
echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
echo("<input type=hidden name='action' value='add' />");
echo("<input type=hidden name='continue' value='true' />");
foreach($configs as $k => $v) {
	echo("<input type=hidden name='config[".$k."]' value='".$v."' />");
}
echo("<input type=submit value='Add new item' /></form>");

//echo("<div style='max-width: 85%; height: 400px; overflow-y: scroll;'>");
echo("<table style='display: block max-width: 100%' border=1 cellspacing=0><tr><th></th><th>Sequence</th>");
foreach($configs as $config) {
	echo("<th>".$config."</th>");
}
echo("</tr>");


$sql="select max(".$db_col['seq'].") max from ".$db_tbl['stage'];
$sql.=" where ".$db_col['step']."='".$_REQUEST['step']."'";
$maxres=mysql_query($sql,$aem) or die(mysql_error());
$max=mysql_fetch_assoc($maxres);

while($row = mysql_fetch_assoc($translations)) {
	if(empty($row['atran_match'])) {
		$tokens=$default;
	} else {
		$tokens = split('\|',$row['atran_match']);
	}
	echo("<tr id=".$row['atran_sequence'].">");
	echo("<td>");
	if ($row[$db_col['seq']] != 1) {
		echo("<a href='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&sequence=".$row[$db_col['seq']]."&continue=true&action=moveup'>");
		echo("<img src='/images/uparrow.gif' /></a>");
/*
		echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
		echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
		echo("<input type=hidden name='sequence' value='".$row[$db_col['seq']]."' />");
		echo("<input type=hidden name='continue' value='true' />");
		echo("<input type=hidden name='action' value='moveup' />");
		echo("<input type=image alt='Submit' src='/images/uparrow.gif' /></form>");
*/
	} else {
		echo("<img src=/images/spacer.gif width='20' />");
	}
	if ($row[$db_col['seq']] != $max['max']) {
		echo("<a href='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&sequence=".$row[$db_col['seq']]."&continue=true&action=movedown'>");
		echo("<img src='/images/downarrow.gif' /></a>");
/*
		echo("<form method='get' action='".$_SERVER['PHP_SELF']."'>");
		echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
		echo("<input type=hidden name='sequence' value='".$row[$db_col['seq']]."' />");
		echo("<input type=hidden name='continue' value='true' />");
		echo("<input type=hidden name='action' value='movedown' />");
		echo("<input type=image alt='Submit' src='/images/downarrow.gif' /></form>");
*/
	} else {
		echo("<img src=/images/spacer.gif width='20' />");
	}
	echo("<a href='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&sequence=".$row[$db_col['seq']]."&continue=true&action=delete'>");
	echo("<img src='/images/delete.png' /></a>");
/*
	echo("<form method='get' action=".$_SERVER['PHP_SELF'].">");
	echo("<input type=hidden name='action' value='delete' />");
	echo("<input type=hidden name='sequence' value='".$row[$db_col['seq']]."' />");
	echo("<input type=hidden name='continue' value='true' />");
	echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
	echo("<input type=image alt='Submit' src='/images/delete.png' /></form>");
*/
	echo("<a href='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&sequence=".$row[$db_col['seq']]."&continue=true&action=modify");
	foreach($tokens as $k => $v) {
		echo("&token[".$k."]=".str_replace(array("(",")","\\"),"",$v));
	}
	foreach($configs as $k => $v) {
		echo("&config[".$k."]=".str_replace(array("(",")","\\"),"",$v));
	}
	echo("&translation=".$row['atran_value']."'>");
	echo("<img src='/images/edit.gif' /></a></td>");
/*
	echo("<form method='get' action=".$_SERVER['PHP_SELF'].">");
	echo("<input type=hidden name='action' value='modify' />");
	echo("<input type=hidden name='sequence' value='".$row['atran_sequence']."' />");
	foreach($tokens as $k => $v) {
		echo("<input type=hidden name='token[".$k."]' value='".str_replace(array("(",")","\\"),"",$v)."' />");
	}
	foreach($configs as $k => $v) {
		echo("<input type=hidden name='config[".$k."]' value='".str_replace(array("(",")","\\"),"",$v)."' />");
	}
	echo("<input type=hidden name='translation' value='".$row['atran_value']."' />");
	echo("<input type=hidden name='continue' value='true' />");
	echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
	echo("<input type=image alt='Submit' src='/images/edit.gif' /></form></td>");
*/
	echo("<td><form method='get' action=".$_SERVER['PHP_SELF'].">");
	echo("<input type=hidden name='action' value='newseq' />");
	echo("<input type=hidden name='sequence' value='".$row[$db_col['seq']]."' />");
	echo("<input type=hidden name='continue' value='true' />");
	echo("<input type=hidden name='step' value='".$_REQUEST['step']."' />");
	echo("<input type=text name='endseq' size=3 value='".$row[$db_col['seq']]."' />");
	echo("<input type=submit value='Move' /></form></td>");
	$count=0;
	foreach($tokens as $token) {
		echo("<td>".str_replace(array("(",")","\\"),"",$token)."</td>");
		$count++;
	}
	echo("<td>".$row['atran_value']."</td>");
	echo("</tr>");
}
echo("</table></div>");
echo("</body></html>");

?>
