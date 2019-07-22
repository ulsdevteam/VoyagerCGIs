<?php
/*
LCSU CallSlip

This is used by LCSU staff to print labels for LCSU Callslips.

BDGREGG - 6/11/2019
 * Some modifications by Alex Wreschnig, 2019/06/21
*/

ini_set("display_errors","on");
error_reporting(E_ALL);


$config = require_once("config/lcsu_callslip_config.inc.php");
require_once("callslip.inc.php");
require_once("PhpNetworkLprPrinter.php");
require_once("callslipQuery.inc.php");
$labels = Array();


?><!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>LCSU Callslip</title>
		<link rel="stylesheet" href="ui/callslip.css?v=1.0">
		<script src="ui/callslip.js"></script>
	</head>
	<body>
		<div class="content">
			<h1>LCSU Callslip Printing</h1>
			<div class="controlBar">
				<div class="left">
					<div class="reload">
						<button></button>
					</div>
					<div class="message">
					</div>
				</div>
				<div class="print-labels">
					<button>Print These Labels</button>
				</div>
			</div>
			
			<div class="results">
				<table>
					<thead>
						<tr>
							<td class=\"label-count\"></td>
							<td class=\"call-slip-id\">Callslip ID</td>
							<td class=\"i-barcode\">Barcode</td>
							<td class=\"call-number\">Call No.</td>
							<td class=\"p-barcode\">Patron Barcode</td>
							<td class=\"tray-address\">Tray Address</td>
							<td class=\"title-brief\">Title</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td class=\"label-count\"></td>
							<td class=\"call-slip-id\">Callslip ID</td>
							<td class=\"i-barcode\">Barcode</td>
							<td class=\"call-number\">Call No.</td>
							<td class=\"p-barcode\">Patron Barcode</td>
							<td class=\"tray-address\">Tray Address</td>
							<td class=\"title-brief\">Title</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td colspan="7" class="table-message">
								Loading content...
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="controlBar">
				<div class="left">
					<div class="reload">
						<button></button>
					</div>
					<div class="message">
					</div>
				</div>
				<div class="print-labels">
					<button>Print These Labels</button>
				</div>
			</div>

<?php
/*
# Begin Code

# Printer settings
# interim step in breaking out hardcoded config
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

# Main

$today = date("Y/m/d H:i");

# See if the user checked the print box.
if( $_POST['print_operation'] == "1" ) {
	$print_opt=1;
} else {
	$print_opt=0;
}
*/
/*
$stid = oci_parse($GLOBALS['conn'],$sql);
oci_execute($stid);

$labelcount = 0;

print "<div class=\"results\">\n";
print "<table>\n";
print "<thead><tr><td class=\"label-count\"></td><td class=\"call-slip-id\">Callslip ID</td><td class=\"i-barcode\">Barcode</td>"
		."<td class=\"call-number\">Call No.</td><td class=\"p-barcode\">Patron Barcode</td><td class=\"tray-address\">Tray Address</td>"
		."<td class=\"title-brief\">Title</td></thead><tbody>\n";
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS))
{
	echo "<!--";
	print_r($row);
	echo "-->";

	# For each row in array do...
	$labelcount++;

	# Clean up some variables.
	$title = clean($row['TITLE']);
	$title_brief = clean($row['TITLE_BRIEF']);
	$author = clean($row['AUTHOR']);
	$note = clean($row['NOTE']);
	$tray = clean($row['SPINE_LABEL']);
	$itemyr = clean($row['ITEM_YEAR']);
	$itemchron = clean($row['ITEM_CHRON']);
	$patname_first = clean($row['FIRST_NAME']);
	$patname_middle = clean($row['MIDDLE_NAME']);
	$patname_last = clean($row['LAST_NAME']);
	$patron_id = clean($row['PATRON_ID']);

	# Do minor re-work.
	$perflag='n';
	if ($row['PICKUP_LOCATION_ID'] == '861' || $row['PICKUP_LOCATION_ID'] == '885') {
		$perflag='y';
	}

	$wrapper = '';
	if (( $row['PERM_ITEM_TYPE_CODE'] == 'Nocirc' ) || 
		($row['PERM_ITEM_TYPE_CODE'] == 'PeriodicaA') || 
		($row['PERM_ITEM_TYPE_CODE'] == 'PeriodicaB') || 
		($row['PERM_ITEM_TYPE_CODE'] == 'Microform') ||
		($row['PERM_ITEM_TYPE_CODE'] == 'Map') ) {
		$wrapper = 'RED';
	} else {
		$wrapper = '';
	}

	$patname = '';
	$patname = "$patname_last, $patname_first $patname_middle";
	// Alex note: probably want to convert strlen to mb_strlen
	if (strlen($patname > 30)) {
		$patname = substr($patname,0,30);
	}

	$note1 = '';
	$note2 = '';
	if (strlen($note) > 40 ) {
		$note1 = substr($note,0,40);
		$note2 = substr($note,41,40);
	} else {
		$note1 = $note;
	}

	if (( substr($tray,0,1) == 'R') && ( substr($tray,4,1) == 'M')) {
		$traynodate = substr($tray,0,15);
	} else {
		$traynodate = 'Invalid Tray';
	}

	# Set variables.
	$ibarcode = $row['ITEM_BARCODE'];
	$pickuploc = $row['PICKUP_LOCATION_NAME'];
	$callslipid = $row['CALL_SLIP_ID'];
	$callno = $row['DISPLAY_CALL_NO'];
	$pgroup = $row['PATRON_GROUP_CODE'];
	$reqdate = $row['DATE_REQUESTED'];
	$pbarcode = $row['PATRON_BARCODE'];

	# Print to screen

	print "<tr>\n";
	print "<td class=\"label-count\">$labelcount</td>\n";
	print "<td class=\"call-slip-id\">$callslipid</td>\n";
	print "<td class=\"i-barcode\">$ibarcode</td>\n";
	print "<td class=\"call-number\">$callno</td>\n";
	print "<td class=\"p-barcode\">$pbarcode</td>\n";
	print "<td class=\"tray-address\">$traynodate</td>\n";
	print "<td class=\"title-brief\">$title_brief</td>\n";
	print "</tr>\n";

	# Print Label

	# Begin Label Production 
	$q01 = "{^A^PS^WKCall Slip 4.lbl^%0";
	$q02 = "^H0080^V0007^L0101^P02^WL0$traynodate^%0";
	$q03 = "^H0008^V0224^FW02H0595^%0";
	$q04 = "^H0005^V0509^FW02H0595^%0";
	$q05 = "^H0009^V0408^FW02H0595^%0";
	$q06 = "^H0009^V0179^FW02H0595^%0";
	$q07 = "^H0008^V0064^FW02H0595^%0";
	$q08 = "^H0095^V0075^L0101^P02^WL0$ibarcode^%0";
	$q09 = "^H0034^V0127^B102044*$ibarcode*^%0";
	$q10 = "^H0428^V0383^L0202^P02^XU$wrapper^%0";
	$q11 = "^H0022^V0185^L0202^P02^XS$pickuploc^%0";
	$q12 = "^H0009^V0257^L0202^P02^XUPull Date:  $today^%0";
	$q13 = "^H0009^V0233^L0202^P02^XURequest ID: $callslipid^%0";
	$q14 = "^H0009^V0281^L0202^P02^XUCall No.:   $callno^%0";
	$q15 = "^H0009^V0306^L0202^P02^XUTitle: $title_brief^%0";
	$q16 = "^H0009^V0416^L0202^P02^XU$patname^%0";
	$q17 = "^H0009^V0439^L0202^P02^XU$pgroup^%0";
	$q18 = "^H0009^V0486^L0202^P02^XUReq. Date: $reqdate^%0";
	$q19 = "^H0008^V0536^L0202^P02^XU$note2^%0";
	$q20 = "^H0008^V0515^L0202^P02^XU$note1^%0";
	$q21 = "^H0009^V0462^L0202^P02^XU$pbarcode";
	$q22 = "^~A0^Q1^Z}";	
	$labels[] = $q01.$q02.$q03.$q04.$q05.$q06.$q07.$q08.$q09.$q10.$q11.$q12.$q13.$q14.$q15.$q16.$q17.$q18.$q19.$q20.$q21.$q22;
	# End Label Production 
}	// end For-Loop
print "</tbody></table>\n";
print "</div>\n";

printCallslips($labels);

print "<h2>Number of Labels: $labelcount</h2>\n";

if ( $print_opt == '1' ) {
	print "<h3>$labelcount Labels sent to printer.</h3>\n";
} else {
	print "<h3>Only printed to screen.</h3>\n";
}

oci_free_statement($stid);
oci_close($conn);
*/

?>
		</div>
	</body>
</html>

<?php

function clean($str) {
	# As defined here: https://www.php.net/manual/en/filter.filters.sanitize.php
	$filter_flag = array( 
			'flags' => FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_LOW,
		);
	#$ret = filter_var($str,FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_STRIP_HIGH);
	$ret = filter_var($str,FILTER_SANITIZE_SPECIAL_CHARS,$filter_flag);
	$ret = preg_replace("/\!/", "", $ret);
	return $ret;
}


function printCallslips($array) {
	$lpr = new PhpNetworkLprPrinter($printer, $port, $queue);
	if ($lpr->getErrStr()) {
		print "Error: ".$lpr->getErrStr()."\n";
	} else {
		//print "<td>OK\n";
	}
	if($lpr) {
		foreach($array as $label) {
			$lpr->printText($satolabel);
		}
	}
}

?>