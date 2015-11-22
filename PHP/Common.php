<?php

function setStatus( $text ) {

	$myfile = fopen("tmp/DiscLibrary_status.txt", "wb");
	fwrite($myfile, $text);
	fclose( $myfile );

}

function getStatus( ) {

	if ( $myfile = @fopen("tmp/DiscLibrary_status.txt", "rb") ) {
		$text = fread($myfile, 500);
		fclose( $myfile );
	} else
		$text = "no current status...";

	return $text;
}

function setStatusCode( $value ) {

	$currentRunner = getStatusCode();
	$myPid = getmypid();

	if ( $currentRunner && $currentRunner > 0 && $currentRunner != $myPid && posix_getpgid($currentRunner) ) return false;		// Still running and not me

    $myfile = fopen("tmp/DiscLibrary_status_code.txt", "wb");
    fwrite($myfile, $value === 0 ? 0 : $myPid );
    fclose( $myfile );

    return true;
}

function getStatusCode( ) {

    if ( $myfile = @fopen("tmp/DiscLibrary_status_code.txt", "rb") ) {
        $value = fread($myfile, 500);
        fclose( $myfile );
    } else
        $value = -1;

    return $value;
}

function logMsg( $Priority, $Msg ) {
    trigger_error( $Msg, $Priority );
}

function StatusMsg( $Msg ) {
    logMsg( E_USER_NOTICE, $Msg);
    $_SESSION['StatusMessage'] = $Msg;
    session_commit();
    setStatus( $Msg );
}

function error_msg($message, $level=E_USER_NOTICE) {
    $caller = next(debug_backtrace());
    trigger_error($message
        . ' in <strong>' . $caller['function'].'</strong>'
        . ' called from <strong>'.$caller['file'].'</strong>'
        . ' on line <strong>'.$caller['line'].'</strong>'
        , $level);
}

function cleanFilename( $fname ) {
    $fname = preg_replace( '/\&/', 'and', $fname );
    $newFname = preg_replace( '/\W+/', '_', $fname );
//	StatusMsg("Potential fname '" . $fname . "' became '" . $newFname . "'" );
    return $newFname;
}

function getSessionValue( $id, $htmldecode = true ) {
    if ( isset( $_POST[$id]) ) {
        return $htmldecode ? htmlspecialchars_decode($_POST[$id]) : $_POST[$id];
    } elseif ( isset( $_REQUEST[$id]) ) {
        return $htmldecode ? htmlspecialchars_decode($_REQUEST[$id]) : $_REQUEST[$id];
    } elseif ( isset( $_SESSION[$id] ) ) {
        return $_SESSION[$id];
    } else
        return false;
}

function getPathType($Path) {
    if ( ! $Path || strlen($Path) < 1 ) return 'NoPath';

    $cmdLines = array();
    exec('file -L ' . $Path, $cmdLines, $rt);

    if ( strpos( strtolower( $cmdLines[0] ), 'block special' ) !== false ) return 'device';
    elseif ( strpos( strtolower( $cmdLines[0] ), 'directory' ) !== false ) return 'directory';
    elseif ( strpos( strtolower( $cmdLines[0] ), 'special' ) !== false ) return 'special';
    else return 'file';
}

/**
* Calculate the size of a directory by iterating its contents
*
* author      Aidan Lister &LT;aidan@php.net&GT;
* version     1.2.0
* link        http://aidanlister.com/2004/04/calculating-a-directories-size-in-php/
* param       string   $path    		Path to directory
* param       INT		 $scaleFactor	Units to return value in (eg. 1024*1024*1024 to return in Gigabytes (32-bit ints, dont go higher!) )
*/
//MWW	Added scaling to handle total sizes > 2G (32-bit), for example, when sizing a DVD
//MWW	scaleFactor > 1 will introduce errors in total size that will get worse the more files there are in the folder
function dirsize($path, $scaleFactor = 1) {
    // Init
    $size = 0;

    // Trailing slash
    if (substr($path, -1, 1) !== DIRECTORY_SEPARATOR) {
        $path .= DIRECTORY_SEPARATOR;
    }

    // Sanity check
    if (is_file($path)) {
        return ceil(filesize($path) / $scaleFactor, 1);
    } elseif (!is_dir($path)) {
        return false;
    }

    // Iterate queue
    $queue = array($path);
    for ($i = 0, $j = count($queue); $i < $j; ++$i)
    {
        // Open directory
        $parent = $i;
        if (is_dir($queue[$i]) && $dir = @dir($queue[$i])) {
            $subdirs = array();
            while (false !== ($entry = $dir->read())) {
                // Skip pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                // Get list of directories or filesizes
                $path = $queue[$i] . $entry;
                if (is_dir($path)) {
                    $path .= DIRECTORY_SEPARATOR;
                    $subdirs[] = $path;
                } elseif (is_file($path)) {
                    $size += ceil(filesize($path) / $scaleFactor, 1);
                }
            }

            // Add subdirectories to start of queue
            unset($queue[0]);
            $queue = array_merge($subdirs, $queue);

            // Recalculate stack size
            $i = -1;
            $j = count($queue);

            // Clean up
            $dir->close();
            unset($dir);
        }
    }

    return $size;
}
?>
