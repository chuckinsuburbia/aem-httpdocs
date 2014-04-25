<?php 

$basePath=$_SERVER['DOCUMENT_ROOT'];
require_once($basePath.'/lib/nusoap/lib/nusoap.php');
require_once($basePath."/conf/config.php");

$err = $sc_client->getError();
if ($err) {
        die( '<h2>Constructor error</h2><pre>' . $err . '</pre>');
}
// Doc/lit parameters get wrapped
$keys = new soapval('keys','IncidentKeysType','',"http://servicecenter.peregrine.com/PWS",false,array("query" => "IMTicketStatus~=\"Closed\" & PrimaryAssignmentGroup=\"VERIZON BUSINESS\""));
//$PrimaryAssignmentGroup = new soapval("PrimaryAssignmentGroup","StringType","VERIZON BUSINESS","http://servicecenter.peregrine.com/PWS/Common");
//$IMTicketStatus = new soapval("IMTicketStatus","StringType","Open","http://servicecenter.peregrine.com/PWS/Common");
//$Location = new soapval("Location","StringType",$_REQUEST['store'],"http://servicecenter.peregrine.com/PWS/Common");
//$instance = new soapval("instance","IncidentInstanceType",array($PrimaryAssignmentGroup,$IMTicketStatus,$Location),"http://servicecenter.peregrine.com/PWS");
$instance = new soapval("instance","IncidentInstanceType","","http://servicecenter.peregrine.com/PWS");
$model = new soapval("model", "IncidentModelType",array($keys,$instance),null,"http://servicecenter.peregrine.com/PWS");
$RetrieveIncidentListRequest = new soapval("RetrieveIncidentListRequest","RetrieveIncidentListRequestType",$model,"http://servicecenter.peregrine.com/PWS");
$sc_client->setCredentials('pem', 'Pemspassword');
$result = $sc_client->call('RetrieveIncidentList',$RetrieveIncidentListRequest->serialize('literal'),"http://servicecenter.peregrine.com/PWS");//,
// Check for a fault
if ($sc_client->fault) {
        #echo '<h2>Retrieve Fault</h2><pre>';
#        print_r($result);
		echo $result['faultstring'];
        #echo '</pre>';
} else {
        // Check for errors
        $err = $sc_client->getError();
        if ($err) {
                // Display the error
                echo '<h2>Retrieve Error</h2><pre>' . $err . '</pre>';
        } else {
                // Display the result
        		if(!empty($result['instance']['IncidentID'])){
					//echo "got 1 incident";
					$results[] = $result['instance'];
				}else{
					$results = $result['instance'];
				}
		       // echo '<h2>Retrieve Result</h2><pre>';print_r($results);echo '</pre>';
			//	print_r($result['instance']);
				if(!empty($results[0]['IncidentID'])){
					foreach($results as $key => $resultInstance){
						$return[$resultInstance['Location']] .= "<a href=\"http://controlm/bip/scticket.php?im=".$resultInstance['IncidentID']."\" target=\"_new\">".$resultInstance['IncidentID']."<br />".$resultInstance['anp.graffiti']."</a><br />";
					}
					//$output = substr($output,0, strlen($output)-6); //take off last <br />
					foreach($return as $key => $val){
						$return[$key] = substr($val,0, strlen($val)-6); //take off last <br />
					}
					print serialize($return);
					//print "<pre>";print_r($return);print "</pre>";
				}
        }
}
?>
