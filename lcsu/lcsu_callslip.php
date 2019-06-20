<?php
/*
LCSU CallSlip

This is used by LCSU staff to print lables for LCSU Callslips.

BDGREGG - 6/11/2019
*/

$config = require_once("lcsu_callslip_config.inc.php");
include "PhpNetworkLprPrinter.php";


?><html>
        <head>
                <title>LCSU Callslip</title>
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        </head>
        <body>
                <h1>LCSU Callslip</h1>
					Description:  Prints Labels for LCSU callslips.</p>
				<form method="post" enctype="multipart/form-data">
                <table>
				<tr>
				<td>Print Labels:</td>
				<td><input type="checkbox" name="print" value="1" id="print"/></td>
				<td>If checked, this will send the labels to the printer. Otherwise it will just list them below.</td>
                </tr>
                <tr>
                <td colspan=3>
                <input type="submit" name="submit" value="Print" id="submit"/>
                </td>
                </tr>
                </table>
				</form>

<?php

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
if( $_POST['print'] == "1" ) {
	$print_opt=1;
} else {
	$print_opt=0;
}

if(isset($_POST['submit'])) {
    /*
    $lpr = new PhpNetworkLprPrinter($printer, $port, $queue);
    if ($lpr->getErrStr()) {
        print "Error: ".$lpr->getErrStr()."</br>\n";
		die("Unable to connect to printer.</br>\n");
    }
	*/

	# Get list of Callslips.
    $sql = "select cs.call_slip_id,
		cs.bib_id,
		cs.item_id,
		cs.mfhd_id,
		cs.patron_id,
		cs.patron_group_id,
		(select pgrp.patron_group_code
			from pittdb.patron_group pgrp
			where pgrp.patron_group_id = cs.patron_group_id) patron_group_code,
		to_char(cs.date_requested, 'yyyy/mm/dd hh24:mi:ss') date_requested,
		to_char(cs.date_processed, 'yyyy/mm/dd hh24:mi:ss') date_processed,
		cs.location_id,
		cs.status call_slip_status,
		(select csst.status_desc
			from pittdb.call_slip_status_type csst
			where csst.status_type = cs.status) call_slip_status_type,
		to_char(cs.status_date, 'yyyy/mm/dd') status_date,
		cs.status_opid,
		cs.no_fill_reason,
		cs.item_year,
		cs.item_enum,
		cs.item_chron,
		cs.note,
		cs.pickup_location_id,
		(select l.location_code
			from pittdb.location l
			where l.location_id = cs.pickup_location_id) pickup_location_code,
		(select l.location_display_name
			from pittdb.location l
			where l.location_id = cs.pickup_location_id) pickup_location_disp,
		(select l.location_name
			from pittdb.location l
			where l.location_id = cs.pickup_location_id) pickup_location_name,
		upper(i.spine_label) spine_label,
		i.perm_location,
		(select l.location_code
			from pittdb.location l
			where l.location_id = i.perm_location) perm_location_code,
		i.temp_location,
		(select l.location_code
			from pittdb.location l
			where l.location_id = i.temp_location) temp_location_code,
		i.item_type_id perm_item_type_id,
		(select it.item_type_code
			from pittdb.item_type it, pittdb.item i
			where it.item_type_id = i.item_type_id
			and i.item_id = cs.item_id) perm_item_type_code,
		i.temp_item_type_id,
		(select it.item_type_code
			from pittdb.item_type it, pittdb.item i
			where it.item_type_id = i.temp_item_type_id
			and i.item_id = cs.item_id) temp_item_type_code,
		mm.location_id,
		(select l.location_code
			from pittdb.location l
			where l.location_id = mm.location_id) mfhd_location_code,
		mm.display_call_no,
		ib.item_barcode,
		p.last_name,
		p.first_name,
		p.middle_name,
		(select pb.patron_barcode
			from pittdb.patron_barcode pb
			where pb.patron_id = cs.patron_id
			and pb.barcode_status = 1
			fetch first 1 rows only) patron_barcode,
		bt.author,
		bt.title,
		substr(bt.title_brief,1,30) title_brief,
		(select pa.address_line1
			from pittdb.patron_address pa
			where pa.patron_id = cs.patron_id
			and pa.address_type = 2
			fetch first 1 rows only) patron_campus_address,
		(select pp.phone_number
			from pittdb.patron_phone pp,
				pittdb.patron_address pa
			where pp.address_id = pa.address_id
			and pa.patron_id = cs.patron_id
			and pa.address_type = 2
			fetch first 1 rows only) patron_campus_phone,
		(select count(*)
			from pittdb.mfhd_item mi
			where mi.mfhd_id = cs.mfhd_id) mfhd_item_count
	from pittdb.call_slip cs
	left join pittdb.item i on i.item_id = cs.item_id
	left join pittdb.mfhd_master mm on mm.mfhd_id = cs.mfhd_id
	left outer join pittdb.item_barcode ib on ib.item_id = cs.item_id and ib.barcode_status = 1
	left join pittdb.patron p on p.patron_id = cs.patron_id
	left join pittdb.bib_text bt on bt.bib_id = cs.bib_id
	where
		cs.print_group_id = '20' 
		and cs.status in (1,3) 
	order by i.spine_label";	

    $stid = oci_parse($GLOBALS['conn'],$sql);
    oci_execute($stid);

	$labelcount = 0;

	print "<div style=\"overflow-x:auto;\">\n";
	print "<table border=1 style=\"border-collapse: collapse; 1px solid black;\">\n";
	print "<tr><th></th><th>Callslip ID</th><th>Barcode</th><th>Call No.</th><th>Patron Barcode</th><th>Patron ID</th><th>Title</th>\n";
    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS))
    {

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
			($row['PERM_ITEM_TYPE_CODE'] == 'Microform') ) {
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
		
	    #print "<table border=1 width=400px>\n";
		if ( $labelcount % 2 == 0) {
			print "<tr>\n";
		} else {
			print "<tr style=\"background-color: #f1f1f1\">\n";
		}
		print "<td style=\"paddiong:3px;text-align:right;\">$labelcount</td>\n";
		#print "<tr><td width=125px>Tray:</td><td style=\"text-align: right;\">$traynodate</td></tr>\n";
		#print "<tr><td>Barcode:</td><td>$barcode</td></tr>\n";
		#print "<tr><td>Pickup:</td><td>$pickuploc</td></tr>\n";
		print "<td style=\"padding:3px;text-align: right;\">$callslipid</td>\n";
		print "<td style=\"padding:3px;\">$ibarcode</td>\n";
		#print "<tr><td>Pull Date:</td><td>$today</td></tr>\n";
		print "<td style=\"padding:3px;\">$callno</td>\n";
		print "<td style=\"padding:3px;\">$pbarcode</td>\n";
		print "<td style=\"padding:3px;text-align: right;\">$patron_id</td>\n";
		print "<td style=\"padding:3px;\">$title_brief</td>\n";
		#print "<tr><td>Wrapper:</td><td>$wrapper</td></tr>\n";
		#print "<td style=\"padding:3px;\">$patname</td>\n";
		#print "<tr><td>Patron Group:</td><td>$pgroup</td></tr>\n";
		#print "<tr><td>Request Date:</td><td>$reqdate</td></tr>\n";
		#print "<tr><td>Note:</td><td>$note1<br>$note2 </td></tr>\n";
		print "</tr>\n";
		#print "</table>\n";

		/*
		print "Tray: $traynodate<br>\n";
		print "Barcode: $ibarcode<br>\n";
		print "Wrapper: $wrapper<br>\n";
		print "Pickup Location: $pickuploc<br>\n";
		print "Pull Date: $today<br>\n";
		print "Request ID: $callslipid<br>\n";
		print "Call No: $callno<br>\n";
		print "Title: $title_brief<br>\n";
		print "Patron: $patname<br>\n";
		print "Patron Group: $pgroup<br>\n";
		print "Req. Date: $reqdate<br>\n";
		print "Note: $note1<br>\n";
		print "      $note2<br>\n";
		print "Patron Barcode: $pbarcode<br>\n";
		print "---------------------------------------------------------------<br>\n";
		*/

		# Print Label

		# Beting Label Production 
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
		$satolabel = $q01.$q02.$q03.$q04.$q05.$q06.$q07.$q08.$q09.$q10.$q11.$q12.$q13.$q14.$q15.$q16.$q17.$q18.$q19.$q20.$q21.$q22;
		# End Label Production 

		# Do the actual printing.	
		if ( $print_opt == '1' ) {	
			$lpr = new PhpNetworkLprPrinter($printer, $port, $queue);
   			if ($lpr->getErrStr()) {
        		print "<td>Error: ".$lpr->getErrStr()."</td>\n";
   			} else {
				#print "<td>OK\n";
			}
			if($lpr) {
				#$lpr->printText($satolabel);
   			}
		}

		# End-For Loop
	}
	print "</table>\n";
	print "</div>\n";

	print "<h2>Number of Labels: $labelcount</h2>\n";

	if ( $print_opt == '1' ) {
		print "<h3>$labelcount Labels sent to printer.</h3>\n";
	} else {
		print "<h3>Only printed to screen.</h3>\n";
	}

    oci_free_statement($stid);
	exit;
}
oci_close($conn);
exit;

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

?>
        </body>
</html>

