<?php
$basePath=$_SERVER['DOCUMENT_ROOT'];
require_once($basePath."/conf/config.php");
require_once($basePath."/lib/functions.php");
require_once($basepath.'/lib/CronParser.php');

//print_r($_REQUEST);

$tokens['source'] = "WEBAPI";

if(empty($_REQUEST['originSeverity'])){
	$_REQUEST['originSeverity'] = isset($_REQUEST['severity']) ? $_REQUEST['severity'] : "30";
}

foreach($_REQUEST as $key=>$val){
	if($debug) file_put_contents($webapilog,$key." = ".$val."\n",FILE_APPEND);
	$tokens[$key] = $val;
}

//Special exception for QA controlm jobs
if($tokens['domain'] == 'controlm') {
	if(preg_match("/^qa_/",$tokens['object']) || preg_match("/^node_id/",$tokens['object'])) { 
		aemlog("Alerts suppressed for QA controlm jobs and nodes");
		die; 
	}
}

$alertId=createAlert($tokens);
if($debug) aemlog("Alert $alertId created");
echo($alertId."\n");

#get the path for this source
$sPath=getSourcePath($tokens['source']);

#process each step for this path
foreach($sPath as $step){
        $stepRc=runStep($alertId,$step);
        if(!$stepRc){
                aemlog("Source Step Failed, aborting!");
                die();
        }
}

#check if ticket is blacked out if not set status to open
$returnAlert = processAlert($alertId);

if($returnAlert != false){
        $destType = $returnAlert['type'];
        $alertId = $returnAlert['alertId'];

        $dPath=getDestPath($tokens['source'],$destType);
        //print_r($dPath);
        if($debug) aemlog("running outbound steps");
        #process each step for this path
        foreach($dPath as $step){
                $stepRc=runStep($alertId,$step);
                if(!$stepRc){
                        aemlog("Destination Step Failed!");
                }
        }
}

if($debug) aemlog("End of Alert Processing for $alertId");

?>
