<?php

date_default_timezone_set('America/New_York');

// Pull in the NuSOAP code
$basePath = $_SERVER['DOCUMENT_ROOT'];
require_once($basePath.'/conf/config.php');
require_once($basePath.'/lib/nusoap/lib/nusoap.php');
require_once($basePath.'/lib/CronParser.php');
require_once($basePath.'/lib/functions.php');

// Create the server instance
$server = new soap_server();
// Initialize WSDL support
$server->configureWSDL('aemticket', 'urn:aemticket');

// Register the methods to expose

//Hello
$server->register('hello',                // method name
    array('name' => 'xsd:string'),        // input parameters
    array('return' => 'xsd:string'),      // output parameters
    'urn:aemticket',                      // namespace
    'urn:aemticket#hello',                // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Says hello to the caller'            // documentation
);

//Close incident
$server->register('closeIncident',        // method name
    array('incidentId' => 'xsd:string'),  // input parameters
    array('return' => 'xsd:string'),      // output parameters
    'urn:aemticket',                      // namespace
    'urn:aemticket#closeIncident',        // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Closes an AEM Incident'              // documentation
);


// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>
