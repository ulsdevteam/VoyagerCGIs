<?php

/**
 * @file tools/lcsu/callslip.inc.php
 *
 * Copyright (c) University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the LICENSE file.
 *
 * @class Callslip
 *
 * @brief This class builds a representation of a Callslip, including cleaned
 * fields.
 */

class Callslip {
	
	/*
	 * String
	 * Stores the label string for the callslip.
	 */
	private $_label = null;
	
	/*
	 * Array of components of the callslip.
	 */
	private $_components = Array();
	
	/**
	 * Constructor
	 * @param $components An array containing the various components of a callslip,
	 * typically as a row returned from a database.
	 * 
	 * Calls the loadNewCallslip method on the passed array.
	 */
	function __construct($components) {
		$this->loadNewCallslip($components);
	}
	
	/**
	 * @param $components An array containing the various components of a callslip,
	 * typically as a row returned from a database.
	 */
	function loadNewCallslip($components) {
		$this->_components['today'] = date("Y/m/d H:i");

		// Set variables.
		$this->_components['item_barcode'] = $components['ITEM_BARCODE'];
		$this->_components['pickup_location'] = $components['PICKUP_LOCATION_NAME'];
		$this->_components['callslip_id'] = $components['CALL_SLIP_ID'];
		$this->_components['call_number'] = $components['DISPLAY_CALL_NO'];
		$this->_components['patron_group_code'] = $components['PATRON_GROUP_CODE'];
		$this->_components['request_date'] = $components['DATE_REQUESTED'];
		$this->_components['patron_barcode'] = $components['PATRON_BARCODE'];

		// set variables needing basic cleanup
		$this->_components['title'] = $this->_clean($components['TITLE']);
		$this->_components['title_brief'] = $this->_clean($components['TITLE_BRIEF']);
		$this->_components['author'] = $this->_clean($components['AUTHOR']);
		$this->_components['note'] = $this->_clean($components['NOTE']);
		$this->_components['tray'] = $this->_clean($components['SPINE_LABEL']);
		$this->_components['itemyr'] = $this->_clean($components['ITEM_YEAR']);
		$this->_components['itemchron'] = $this->_clean($components['ITEM_CHRON']);
		$this->_components['patron_name_first'] = $this->_clean($components['FIRST_NAME']);
		$this->_components['patron_name_middle'] = $this->_clean($components['MIDDLE_NAME']);
		$this->_components['patron_name_last'] = $this->_clean($components['LAST_NAME']);
		$this->_components['patron_id'] = $this->_clean($components['PATRON_ID']);

		// determine wrapper color if any
		if (( $components['PERM_ITEM_TYPE_CODE'] == 'Nocirc' ) || 
			($components['PERM_ITEM_TYPE_CODE'] == 'PeriodicaA') || 
			($components['PERM_ITEM_TYPE_CODE'] == 'PeriodicaB') || 
			($components['PERM_ITEM_TYPE_CODE'] == 'Microform') ||
			($components['PERM_ITEM_TYPE_CODE'] == 'Map') ) {
			$this->_components['wrapper'] = 'RED';
		} else {
			$this->_components['wrapper'] = '';
		}
		
		// Fix nulls
		foreach($this->_components as $key => $component) {
			if(is_null($component)) {
				$this->_components[$key] = '';
			}
		}

		// build patron full name
		$this->_components['patron_name_full'] = $this->_components['patron_name_last'].", ".$this->_components['patron_name_first']." ".$this->_components['patron_name_middle'];
		// Alex note: probably want to convert strlen to mb_strlen but I'm unsure how any of this affects the printer
		if (strlen($this->_components['patron_name_full'] > 30)) {
			$this->_components['patron_name_full'] = substr($this->_components['patron_name_full'],0,30);
		}

		// build note (in two parts)
		$this->_components['note_part_1'] = '';
		$this->_components['note_part_2'] = '';
		if (strlen($this->_components['note']) > 40 ) {
			$this->_components['note_part_1'] = substr($this->_components['note'],0,40);
			$this->_components['note_part_2'] = substr($this->_components['note'],40,40);
		} else {
			$this->_components['note_part_1'] = $this->_components['note'];
		}

		// build tray
		if (( substr($this->_components['tray'],0,1) == 'R') && ( substr($this->_components['tray'],4,1) == 'M')) {
			$this->_components['tray_no_date'] = substr($this->_components['tray'],0,15);
		} else {
			$this->_components['tray_no_date'] = 'Invalid Tray';
		}
		
		// build a callslip label
		$this->_buildCallslipLabel();
		
	}
	
	/**
	 * Builds a label based on the existing callslip configuration, and stores
	 * it as a string in $this->_label 
	 */
	function _buildCallslipLabel() {
		$q01 = "{^A^PS^WKCall Slip 4.lbl^%0";
		$q02 = "^H0080^V0007^L0101^P02^WL0".$this->_components['tray_no_date']."^%0";
		$q03 = "^H0008^V0224^FW02H0595^%0";
		$q04 = "^H0005^V0509^FW02H0595^%0";
		$q05 = "^H0009^V0408^FW02H0595^%0";
		$q06 = "^H0009^V0179^FW02H0595^%0";
		$q07 = "^H0008^V0064^FW02H0595^%0";
		$q08 = "^H0095^V0075^L0101^P02^WL0".$this->_components['item_barcode']."^%0";
		$q09 = "^H0034^V0127^B102044*".$this->_components['item_barcode']."*^%0";
		$q10 = "^H0428^V0383^L0202^P02^XU".$this->_components['wrapper']."^%0";
		$q11 = "^H0022^V0185^L0202^P02^XS".$this->_components['pickup_location']."^%0";
		$q12 = "^H0009^V0257^L0202^P02^XUPull Date:  ".$this->_components['today']."^%0";
		$q13 = "^H0009^V0233^L0202^P02^XURequest ID: ".$this->_components['callslip_id']."^%0";
		$q14 = "^H0009^V0281^L0202^P02^XUCall No.:   ".$this->_components['call_number']."^%0";
		$q15 = "^H0009^V0306^L0202^P02^XUTitle: ".$this->_components['title_brief']."^%0";
		$q16 = "^H0009^V0416^L0202^P02^XU".$this->_components['patron_name_full']."^%0";
		$q17 = "^H0009^V0439^L0202^P02^XU".$this->_components['patron_group_code']."^%0";
		$q18 = "^H0009^V0486^L0202^P02^XUReq. Date: ".$this->_components['request_date']."^%0";
		$q19 = "^H0008^V0536^L0202^P02^XU".$this->_components['note_part_2']."^%0";
		$q20 = "^H0008^V0515^L0202^P02^XU".$this->_components['note_part_1']."^%0";
		$q21 = "^H0009^V0462^L0202^P02^XU".$this->_components['patron_barcode'];
		$q22 = "^~A0^Q1^Z}";	

		$this->_label = $q01.$q02.$q03.$q04.$q05.$q06.$q07.$q08.$q09.$q10.$q11.$q12.$q13.$q14.$q15.$q16.$q17.$q18.$q19.$q20.$q21.$q22;
	}


	/*
	 * @param $str string (string to be cleaned)
	 * As defined here: https://www.php.net/manual/en/filter.filters.sanitize.php
	 */
	private function _clean($str) {
		$filter_flag = array( 
				'flags' => FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_LOW,
			);
		#$ret = filter_var($str,FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_STRIP_HIGH);
		$ret = filter_var($str,FILTER_SANITIZE_SPECIAL_CHARS,$filter_flag);
		$ret = preg_replace("/\!/", "", $ret);
		return $ret;
	}
	
	function getJSON() {
		return json_encode($this->_components);
	}
	
	function getLabel() {
		return $this->_label;
	}
	
	function getComponent($componentName) {
		return $this->_components[$componentName];
	}
	
	function getComponentList() {
		return array_keys($this->_components);
	}
	
	function getAllComponents() {
		return $this->_components;
	}
}
