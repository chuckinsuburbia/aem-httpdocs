<?php
$basePath=".";
require_once($basePath."/lib/aemdb.php");
require_once($basePath."/conf/config.php");

print_r($_REQUEST);

$tokens="eventType=\"WEBAPI\" ";

if(empty($_REQUEST['originSeverity']))
	$_REQUEST['originSeverity'] = $_REQUEST['severity'];


foreach($_REQUEST as $key=>$val){
	if($debug) file_put_contents($webapilog,$key." = ".$val."\n",FILE_APPEND);
	$tokens .= '"'.$key."=".$val.'" ';
}


if($debug) file_put_contents($mainlog,"TOKENS = ".$tokens."\n",FILE_APPEND);

//passthru($aemopen." WEBAPI ".$tokens." 2>>$webapilog >>$webapilog");
passthru($aemopen." source=WEBAPI ".$tokens." 2>>$webapilog >>$webapilog");
?>
