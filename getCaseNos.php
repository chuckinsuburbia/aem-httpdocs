<?php

$basePath=$_SERVER['DOCUMENT_ROOT'];
require_once($basePath.'/lib/nusoap/lib/nusoap.php');
require_once($basePath."/conf/config.php");
require_once($basePath.'/lib/functions.php');

$debug=false;

$alerts = getActiveAlerts();
//$alerts = getAlertDetailsByText($_GET['text']);
//echo $alert['CaseNo']; 
foreach($alerts as $alert){
	if(!strstr($alert['text'],$_GET['text'])) continue;
	$text = split(" ",$alert['text']);
	$key = $text[0]." ".$text[1];
	$return[$key] = $alert['CaseNo'];
}
print serialize($return);
//print "<pre>";print_r($return);print "</pre>";
?>
