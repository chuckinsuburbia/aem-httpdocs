<?php
$basePath=$_SERVER['DOCUMENT_ROOT'];
require_once($basePath."/conf/config.php");
require_once($basePath."/lib/functions.php");

$db_tbl_alert="aem_alert";

$tokens = $_REQUEST;

if(!isset($tokens['incident_id'])) die("Incident ID required.\n");

if($aem_incident=$tokens['incident_id']) unset($tokens['incident_id']);

if($debug) aemlog("Received update for incident ID: ".$aem_incident);

$sql ="select * from ".$db_tbl_alert;
$sql.=" where aa_id='".$aem_incident."'";
$res=mysql_query($sql,$aem) or die(mysql_error());
switch (mysql_num_rows($res))
 {
  case 0:
   if($debug) aemlog("No alerts found with incident ID: ".$aem_incident);
   die;
   break;
  case 1:
   $alert=mysql_fetch_assoc($res);
   break;
  default:
   if($debug) aemlog("Error: ".mysql_num_rows($res)." alerts found with incident ID: ".$aem_incident);
   die;
 }  

foreach($tokens as $tokenKey => $tokenValue)
 {
  if($tokenKey == "comment") $tokenValue=mysql_real_escape_string(date(DATE_RFC822).": ".$tokenValue);
  $sql="select at_id from aem_tokens";
  $sql.=" where at_name='".$tokenKey."'";
  $res=mysql_query($sql,$aem) or die(mysql_error());
  if(mysql_num_rows($res) !== 0)
   {
    list($tokenId)=mysql_fetch_array($res);
    if($debug) aemlog("Setting new value ".$tokenValue." for token ".$tokenKey);
    $sql ="select aat_value from aem_alert_tokens";
    $sql.=" where aat_alert='".$aem_incident."'";
    $sql.=" and aat_token='".$tokenId."'";
    $res2=mysql_query($sql,$aem) or die(mysql_error());
    if(mysql_num_rows($res2) !== 0)
     {
      if($tokenKey == "comment")
       {
        $sql="select aat_value, aatl_value from aem_alert_tokens left join aem_alert_token_longValue";
        $sql.=" on aem_alert_tokens.aat_long_value = aem_alert_token_longValue.aatl_id";
        $sql.=" where aat_alert='".$aem_incident."'";
        $sql.=" and aat_token='".$tokenId."'";
        $res3=mysql_query($sql,$aem) or die(mysql_error());
        $existingComments=mysql_fetch_assoc($res3);
        if(isset($existingComments['aat_value'])) $tokenValue.="\n".$existingComments['aat_value'];
        if(isset($existingComments['aatl_value'])) $tokenValue.="\n".$existingComments['aatl_value'];
       }
      if(strlen($tokenValue) > 255)
       {
        $sql="insert into aem_alert_token_longValue (aatl_value) VALUES (".GetSQLValueString($tokenValue,'text').")";
        mysql_query($sql,$aem) or handleError("update - token - longValue",mysql_error()." - ".$sql);
        $longValue=mysql_insert_id($aem);
        $tokenValue="";
       }
      $sql ="update aem_alert_tokens";
      $sql.= ($tokenValue!="" ? " set aat_value='".$tokenValue."'" : " set aat_value=NULL, aat_long_value='".$longValue."'");
      $sql.=" where aat_alert='".$aem_incident."'";
      $sql.=" and aat_token='".$tokenId."'";
     }
    else
     {
      $longValue="NULL";
      if(strlen($tokenValue) > 255)
       {
        $sql="insert into aem_alert_token_longValue (aatl_value) VALUES (".GetSQLValueString($tokenValue,'text').")";
        mysql_query($sql,$aem) or handleError("update - token - longValue",mysql_error()." - ".$sql);
        $longValue=mysql_insert_id($aem);
        $tokenValue="";
       }
      $sql = "insert into aem_alert_tokens (aat_alert, aat_token, aat_value, aat_long_value)";
      $sql.= " VALUES (".$aem_incident.",".$tokenId.",".GetSQLValueString($tokenValue,'text').",".$longValue.")";
     }
    mysql_query($sql,$aem) or handleError("Failed to update - ",mysql_error()." - ".$sql);
   }
  else
   {
    if($debug) aemlog("Unknown token: ".$tokenKey);
   }
 }

$sql ="update aem_alert set aa_update_time=NOW()";
$sql.=" where aa_id='".$aem_incident."'";
mysql_query($sql,$aem) or die(mysql_error());

$sql ="select aat_value from aem_tokens left join aem_alert_tokens";
$sql.=" on aem_tokens.at_id=aem_alert_tokens.aat_token";
$sql.=" where at_name='source' and aat_alert='".$aem_incident."'";
$res=mysql_query($sql,$aem) or die(mysql_error());
list($source)=mysql_fetch_array($res);
if($debug) aemlog("Source for incident ID ".$aem_incident." is: ".$source);

/*
Run Steps needed here
*/
$type="update";
$dPath=getDestPath($source,$type);
#process each step for this path
foreach($dPath as $step)
 {
  $stepRc=runStep($aem_incident,$step);
  if(!$stepRc)
   {
    aemlog("Destination Step Failed!");
   }
 }



if($debug) aemlog("End of update Processing for incident ID: ".$aem_incident);

?>
