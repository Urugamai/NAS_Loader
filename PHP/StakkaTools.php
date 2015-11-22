<?php
// *************************************************************************************************************************************************************
//! Storage Manager Class
//!
//! Functions to interact with the storage devices
// *************************************************************************************************************************************************************

//class StorageManager {

//private $serialList;

function __construct() {
	$this->serialList = _raw_getSerialList();
}

function __destruct() {
}

// *************************************************************************************************************************************************************
//! Runs the command to interact with the storage devices and returns the output and status code
//!
//! Command output is returned via a parameter passed in ($CmdOutput) as an array of lines
//! The return value of this function is that returned by the executed command
// *************************************************************************************************************************************************************
function manager( $Serial, $Slot, $Command, &$CmdOutput) {
	$CmdOutput = array();
	if ( $Serial === EJECTED_SERIAL && $Slot <= 0 ) return -1;	// Fail, no such option

	if ( $Serial === "--------" ) {	// Command does not require Serial NOR Slot number
		exec("stakka_mgr --" . $Command, $CmdOutput, $rt );
	} elseif ( $Slot < 0 ) {	// Command does not require Slot number
		exec("stakka_mgr --serial='". $Serial . "' --" . $Command, $CmdOutput, $rt );
	} else {
		exec("stakka_mgr --serial='". $Serial . "' --slot=" . $Slot . " --" . $Command, $CmdOutput, $rt );
	}

	return $rt;
}

// *************************************************************************************************************************************************************
//! Determines if the slot is occupied or not
//!
//! Answer is in the returned code
// *************************************************************************************************************************************************************
function getSlotState( $Serial, $Slot_Start, $Slot_End = -1 ) {
	if ( $Serial === EJECTED_SERIAL ) { return SLOT_UNDEF; }
	if ( $Slot == 0 ) { return SLOT_UNDEF; }

	$rt = manager( $Serial, $Slot, "testslot", $CmdOutput );

	$Reply = preg_split( "/[\s,]+/", $CmdOutput[0] );
	if ( $Reply[0] !== $Serial ) { trigger_error( "ERROR: Response from stakka_mgr not understood - Serial number " . $Reply[0] . " does not match " . $Serial ); return SLOT_UNDEF; }
	if ( $Reply[1] != $Slot ) { trigger_error( "ERROR: Response from stakka_mgr not understood - Slot number " . $Reply[1] . " does not match " . $Slot ); return SLOT_UNDEF; }

	return $Reply[2];
}

// *************************************************************************************************************************************************************
//! Move to provided slot number in the unit indicated
// *************************************************************************************************************************************************************
function MoveToSlot( $Serial, $Slot ) {
	if ( $Serial === EJECTED_SERIAL ) { return -1; }		// Stupid request
	if ( $Slot > MAX_SLOT ) { return -2; }

	$rt = manager( $Serial, $Slot, "moveto", $CmdOutput );

	return $rt;
}

function loadDisc( $Serial, $Slot ) {
	if ( $Serial === EJECTED_SERIAL ) { return -1; }		// Stupid request
	if ( $Slot > MAX_SLOT ) { return -2; }

	exec("stakka_mgr --serial='". $Serial . "' --slot=" . $Slot . " --adddisk", $CmdOutput, $rt );

	return $rt;
}

function unloadDisc( $Serial, $Slot ) {
	if ( $Serial === EJECTED_SERIAL ) { return -1; }		// Stupid request
	if ( $Slot > MAX_SLOT ) { return -2; }

	exec("stakka_mgr --serial='". $Serial . "' --slot=" . $Slot . " --ejectslot", $CmdOutput, $rt );

	return $rt;
}

function returnDisc( $Serial, $Slot ) {
	if ( $Serial === EJECTED_SERIAL ) { return -1; }		// Stupid request
	if ( $Slot > MAX_SLOT ) { return -2; }

	exec("stakka_mgr --serial='". $Serial . "' --slot=" . $Slot . " --returndisk", $CmdOutput, $rt );

	return $rt;
}

function StakkaClearError( $Serial ) {
	if ( $Serial === EJECTED_SERIAL ) { return -1; }		// Stupid request

	exec("stakka_mgr --serial='". $Serial . "' --clearerror", $CmdOutput, $rt );

	return $rt;
}

function getSerialList() {
	return $this->serialList;
}

function _raw_getSerialList() {
	$Cmd = '/usr/local/bin/stakka_mgr --getserials | /bin/grep ^0 | /usr/bin/cut -c1-8';

	//		$rt = manager( "--------", -1, "getserials", $CmdOutput );
	// now need to manually do the grep and cut using php code here

	exec( $Cmd, $SerialList, $rt );
	if ( $rt ) {
		$SerialList = array('Failed');
	}
	return $SerialList;
}
// }		// End of class definition

?>
