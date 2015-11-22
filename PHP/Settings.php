<?php

define('PREFIX_DIR', 'tmp');			// intermediate files will be created here
define('PREFIX', 'DiscLibrary');		// prefix for the scans, can be anything
define('TIME_LIMIT', 1 * 60 * 60);		// in seconds, they need to last long enough to be viewed, printed, etc if your web page uses them.
define('WEBPATH', '/var/www/DiscLibrary');	// Path to our web page source
define('EJECTED_SERIAL', '00000000');		// Serial to store all non-inserted disc details (ejected or never loaded)
define('MAX_SLOT', 100);			// Maximum slot number in the disc storage units

define('SLOT_UNDEF', -1);		// Slot State
define('SLOT_FREE', 0);			// Slot State
define('SLOT_USED', 1);		// Slot State
define('SLOT_UNDEF_STR', 'Unknown_');		// Slot State
define('SLOT_FREE_STR',  '__Free__');		// Slot State  (Yes, cheap and nasty way to keep alignment on screen but width:150px did not seem to work...)
define('SLOT_USED_STR',  'Occupied');		// Slot State

// The various methods by which a file can be moved around
$FileMethods = array( 'Device', 'FileSystem', 'FTP', 'scp');

// The various file formats that we support and (will) convert betweeen
// Note: Audio means raw audio recording on optical disc - i.e. no file structure on disc
$FileFormats = array('DVD-Movie', 'AudioCD', 'Mountable', 'ISO', 'Folder', 'VOB', 'MKV', 'AVI', 'MPG', 'MP4', 'MP3');

// The various file formats that we support and (will) convert betweeen
// Note: Audio means raw audio recording on optical disc - i.e. no file structure on disc
$SaveOperations = array('Copy', 'Move', 'DBUpdateOnly', 'Skip');

// The various file formats that we support and (will) convert betweeen
// Note: Audio means raw audio recording on optical disc - i.e. no file structure on disc
$StorageOperations = array('Insert', 'Return', 'Skip');

$Cmd = '/usr/local/bin/stakka_mgr --getserials | /bin/grep ^0 | /usr/bin/cut -c1-8';

exec( $Cmd, $SerialList, $rt );
if ( $rt ) {
	$SerialList = array('Failed');
}

?>
