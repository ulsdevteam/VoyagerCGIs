<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$config = require_once("../../config/lcsu_callslip_config.inc.php");
require_once("../../config/callslipQuery.inc.php");
require_once("../../classes/callslip.inc.php");
require_once("../../classes/PhpNetworkLprPrinter.php");

$printer = $config["printer_path"];
$port = $config["printer_port"];
$queue = $config["printer_queue"];

# Database settings
$db = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = ".$config["db_protocol"]
		.")(HOST = ".$config["db_host"].")(PORT = ".$config["db_port"]
		.")))(CONNECT_DATA=(SID=".$config["db_sid"].")))";
$db_username=$config["db_user"];
$db_password=$config["db_pass"];

if(!@($conn = oci_connect($db_username,$db_password,$db))) 
{ $conn = oci_connect($db_username,$db_password,$db) or die (ocierror()); } 

$stid = oci_parse($GLOBALS['conn'], $callslipQuery);
oci_execute($stid);

$temporaryCallslip = null;
$response = [];
$response['status'] = "";
$response['callslipsPrinted'] = [];

$lpr = new PhpNetworkLprPrinter($printer, $port, $queue);
if ($lpr->getErrStr()) {
	header('Content-Type: application/json', TRUE, 500);
	print '{"error": "'.$lpr->getErrStr().'"}';
}


if($lpr) {
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
		$temporaryCallslip = new Callslip($row);
		$lpr->printText($temporaryCallslip->getLabel());
		$response['callslipsPrinted'][] = $temporaryCallslip->getAllComponents();
	}
}
header('Content-Type: application/json', TRUE, 200);
$response['status'] = success;
print json_encode($response);