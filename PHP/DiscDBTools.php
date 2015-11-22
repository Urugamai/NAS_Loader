<?php

function connectToStakkaDB() {
	try {
		$dbh = new PDO( 'mysql:host=192.168.2.2;dbname=StakkaDB', 'stakka', 'stakka', array(PDO::ATTR_PERSISTENT => true) );
        return $dbh;
    } catch (PDOException $ex) {
        trigger_error( $ex->getMessage(), E_USER_ERROR);
        return null;
    }
}

function lookupSerialToName( $Serial ) {
    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'lookupSerialToName failed.');
        return null;
    }

    $SerialName = $Serial;
    $SQLStr = "SELECT DeviceShortName FROM DeviceList WHERE Serial = '" . $Serial . "' OR DeviceShortName LIKE '" . $Serial . "';";
    foreach ( $dbh->query( $SQLStr ) as $row ) {
        if ( $row['DeviceShortName'] != NULL ) { $SerialName = $row['DeviceShortName']; }
    }

    $dbh = NULL;
    return $SerialName;
}

function lookupNameToSerial( $SerialName ) {
    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'lookupNameToSerial failed.');
        return null;
    }

    $Serial = $SerialName;
    $SQLStr = 'SELECT Serial FROM DeviceList WHERE DeviceShortName LIKE \'' . $SerialName . '\' OR Serial = \'' . $SerialName . '\';';
    $RowList = $dbh->query( $SQLStr );
    if ( ! $RowList ) return -2;
        foreach ( $RowList as $row ) {
    if ( $row['Serial'] != NULL ) { $Serial = $row['Serial']; }
    }

    $dbh = NULL;
    return $Serial;
}

function updateStakkaName( $Serial, $NewStakkaName ) {
    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'updateStakkaName failed.');
        return null;
    }

    $SQLStr = "INSERT INTO DeviceList";
    $SQLStr .= " SET UpdateDate=NOW()";
    $SQLStr .= ", Serial = '" . $Serial . "'";
    $SQLStr .= ", DeviceShortName = '" . $NewStakkaName . "'";
    $SQLStr .= " ON DUPLICATE KEY UPDATE UpdateDate=NOW()";
    $SQLStr .= ", DeviceShortName = '" . $NewStakkaName . "'";
    $SQLStr .= ";";

    try {
        $sth = $dbh->prepare( $SQLStr );
        $rt = $sth->execute();
    } catch (exception $ex) {
        trigger_error( 'ERROR: DB Update (updateStakkaName) failed.' . $ex->getMessage(), E_USER_ERROR );
    }

    $sth = NULL;
    $dbh = NULL;
    return $rt;
}

function findDiscByName( $DiscLabel, $MaxRows ) {
    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'findDiscByNames failed.');
        return null;
    }

    $SQLStr = 'select Serial, Slot, DiscLabel, UserDiscName, Location from DiscList where 0=0 AND ( 0=1';
    if (strlen($DiscLabel) > 0) $SQLStr .= ' OR DiscLabel like \'%' . $DiscLabel . '%\'';
    if (strlen($DiscLabel) > 0) $SQLStr .= ' OR UserDiscName like \'%' . $DiscLabel . '%\'';
    $SQLStr .= ')';

    if ( $MaxRows > 0) $SQLStr .= ' LIMIT ' . $MaxRows;
    $SQLStr .= ';';

    $RowList = $dbh->query( $SQLStr );

    $dbh = NULL;
    return $RowList;
}

function findDiscByMD5( $DiscMD5, $MaxRows ) {
    if (strlen($DiscMD5) < 32) return NULL;

    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'findDiscByNames failed.');
        return null;
    }

    $SQLStr = 'select Serial, Slot, DiscLabel, UserDiscName, Location from DiscList where 0=0 AND ( 0=1';
    $SQLStr .= ' OR DiscMD5 = \'' . $DiscMD5 . '\'';
    $SQLStr .= ')';

    if ( $MaxRows > 0) $SQLStr .= ' LIMIT ' . $MaxRows;
    $SQLStr .= ';';

    $RowList = $dbh->query( $SQLStr );

    $dbh = NULL;
    return $RowList;
}

// If selected serial does not contain a free slot, returns the next available free slot in any unit
// Smart enough to find holes in the list of free slots
// - necessary to find slots freed by EXTRACTING discs
// -> EJECTED disc entries NOT HANDLED HERE - see findNextFreeExternalSlot()
function findNextFreeSlot( &$Serial, $MinSlot ) {

    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'findNextFreeSlot failed.');
        return null;
    }

    $FreeSlot = ($MinSlot == 0 ? 1 : $MinSlot);

    if ( $Serial !== EJECTED_SERIAL ) { // Use the users provided device first
        $SQLStr = "SELECT Slot, Occupied FROM DiscList WHERE Occupied = 1 AND Serial = '" . $Serial . "' AND Slot >= " . $MinSlot . " ORDER BY Slot;";

        $RowList = $dbh->query($SQLStr);
        if ( $RowList ) {
            foreach ( $RowList as $Row ) {
                if ($FreeSlot >= $Row['Slot']) $FreeSlot = $Row['Slot'] + 1;
                else break;
            }
        } else $FreeSlot = 0;  // Possibly all slots free but let the next query decide that...

    if ( $FreeSlot > MAX_SLOT ) { $FreeSlot = 0; }
    } else $FreeSlot = 0;

    if ( $FreeSlot == 0 ) {		// Try Harder...
        $prefix = '';
        $SerialList = '';
        foreach ( getSerialList() as $SerialNumber)
        {
            $SerialList .= $prefix . "'" . $SerialNumber . "'";
            $prefix = ', ';
        }

// Designed to ensure we use the stakka with the least number of discs in it - helps share the load...
    $SQLStr = "
    SELECT dl0.Serial AS Serial, IFNULL(dl1.Slot, " . MAX_SLOT . ") AS Slot, IFNULL(dl2.DiscCount, 0) AS DiscCount
    FROM DeviceList dl0
    LEFT OUTER JOIN DiscList dl1 ON dl0.Serial = dl1.Serial AND dl1.Occupied = 1
    LEFT OUTER JOIN (
        SELECT Serial, Count(*) AS DiscCount
        FROM DiscList
        WHERE Occupied = 1
        GROUP BY Serial ) dl2 ON dl0.Serial = dl2.Serial
    WHERE dl0.Serial in (" . $SerialList . ")
    AND IFNULL(dl2.DiscCount, 0) < " . MAX_SLOT . "
    ORDER BY IFNULL(DiscCount, 0), dl0.Serial, IFNULL(dl1.Slot, 1);
    ";

//		logMsg(E_USER_NOTICE, 'SQL for free list is: ' . $SQLStr );

    $FreeSlot = 1;				// MinSlot now irrelevant, we want ANY free slot...
    $Serial = EJECTED_SERIAL;
    $RowList = $dbh->query($SQLStr);
    if ( $RowList ) {
        foreach ( $RowList as $Row ) {	// Use the data from the first row!
            if ( $Serial != EJECTED_SERIAL && $Serial != $Row['Serial'] && $FreeSlot <= MAX_SLOT ) {
                break;
            }

            $Serial = $Row['Serial'];

            if ( $FreeSlot < $Row['Slot']) {
                break;
            } else $FreeSlot = $Row['Slot'] + 1;
        }

        if ( $FreeSlot > MAX_SLOT ) { $FreeSlot = 0; }
        } else $FreeSlot = 0;	// NO Free Slots!
    }

    $dbh = NULL;
    return $FreeSlot;
}

// Get next free slot in the Ejected Discs List
function findNextFreeExternalSlot( $MinSlot ) {
    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'findNextFreeExternalSlot failed.');
        return null;
    }

    $FreeSlot = ($MinSlot == 0 ? 1 : $MinSlot);

    $SQLStr = "SELECT Slot, Occupied FROM DiscList WHERE Occupied = 1 AND Serial = '" . EJECTED_SERIAL . "' AND Slot >= " . $MinSlot . " ORDER BY Slot;";

    $RowList = $dbh->query($SQLStr);
    if ( $RowList ) {
        foreach ( $RowList as $Row ) {
            if ($FreeSlot >= $Row['Slot']) $FreeSlot = $Row['Slot'] + 1;
            if ($FreeSlot < $Row['Slot']) break;
        }
    } else $FreeSlot = 0;  // Nothing found

    $dbh = NULL;
    return $FreeSlot;
}

function getDiscEntry( $Serial, $Slot ) {
    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'getDiscEntry failed.');
        return null;
    }

    $SQLStr = 'SELECT disc.*, dev.DeviceShortName FROM DiscList disc LEFT OUTER JOIN DeviceList dev ON disc.Serial = dev.Serial WHERE '
            . '     disc.Serial = \'' . $Serial . '\''
            . ' AND disc.Slot = ' . $Slot
            . ';';

    $RowList = $dbh->query( $SQLStr );

    $dbh = NULL;
    return $RowList;
}

function deleteDBEntry( $Serial, $Slot ) {
    if ( strlen($Serial) < 8 ) {
        logMsg( E_USER_ERROR, 'ERROR: Invalid serial number provided: ' . $Serial );
        return false;
    }

    if ( $Slot == 0) {
        logMsg( E_USER_ERROR, 'ERROR: Must provide a valid slot number for ' . $Serial );
        return false;
    }

    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'getDiscEntry failed.');
        return false;
    }

    $SQLStr = 'DELETE FROM DiscList WHERE '
            . '     Serial = \'' . $Serial . '\''
            . ' AND Slot = ' . $Slot
            . ';';

    try {
        $sth = $dbh->prepare( $SQLStr );
        $rt = $sth->execute();
        logMsg( E_USER_NOTICE, "DEBUG: Delete disc entry at " . $Serial . "_" . $Slot );
    } catch (exception $ex) {
        logMsg( E_USER_ERROR, 'ERROR: DB Delete (deleteDBEntry) failed.' . $ex->getMessage() );
    }

    $sth = NULL;
    $dbh = NULL;
    return $rt;
}

function updateDiscLocation( $Serial, $Slot, $NewSerial, $NewSlot ) {

    if ( $Slot == 0 || $NewSlot == 0 ) {
        trigger_error( 'ERROR: Must provide a valid source and target details for move: source=' . $Serial . ':' . $Slot . ' Target=' . $NewSerial . ':' . $NewSlot, E_USER_ERROR );
        return null;
    }

    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'updateDiscLocation failed.');
        return null;
    }

    $SQLStr = "UPDATE DiscList";
    $SQLStr .= " SET Occupied=1";
    $SQLStr .= ", Serial = '" . $NewSerial . "'";
    $SQLStr .= ", Slot = " . $NewSlot;
    $SQLStr .= " WHERE ";
    $SQLStr .= " Serial = '" . $Serial . "'";
    $SQLStr .= " AND Slot = " . $Slot;
    $SQLStr .= ";";

    try {
        $sth = $dbh->prepare( $SQLStr );
        $rt = $sth->execute();
        //trigger_error( "DEBUG: Updated disc at " . $Serial . "_" . $Slot . " to be at " . $NewSerial . "_" . $NewSlot, E_USER_NOTICE );
    } catch (exception $ex) {
        trigger_error( 'ERROR: DB Update (updateDiscLocation) failed.' . $ex->getMessage(), E_USER_ERROR );
    }

    $sth = NULL;
    $dbh = NULL;
    return $rt;
}

function updateDiscEntryField( $Serial, $Slot, $Column, $Value, $isString = true ) {

    if ( strlen($Serial) < 8 ) {
        trigger_error( 'ERROR: Invalid serial number provided: ' . $Serial, E_USER_ERROR );
        return null;
    }

    if ( $Slot == 0) {
        trigger_error( 'ERROR: Must provide a valid slot number for ' . $Serial, E_USER_ERROR );
        return null;
    }

    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'updateDiscEntryField failed.');
        return null;
    }

    $SQLStr  = "INSERT INTO DiscList SET";
    $SQLStr .= "  Serial=" . "'" . $Serial . "'";
    $SQLStr .= ", Slot=" . $Slot;

    $SQLStr .= ", UpdateDate=NOW()";
    $SQLStr .= ", " . $Column . "=";
    if ( $isString ) $SQLStr .= "'";
    $SQLStr .= $Value;
    if ( $isString ) $SQLStr .= "'";

    $SQLStr .= " ON DUPLICATE KEY UPDATE";

    $SQLStr .= " UpdateDate=NOW()";
    $SQLStr .= ", " . $Column . "=";
    if ( $isString ) $SQLStr .= "'";
    $SQLStr .= $Value;
    if ( $isString ) $SQLStr .= "'";

    $SQLStr .= ";";

    try {
        $sth = $dbh->prepare( $SQLStr );
        $rt = $sth->execute();
        //		$dbh->commit();	// must start a transaction before you can use this ;-)
    } catch (exception $ex) {
        error_msg( 'ERROR: DB Update failed. Command: ' . $SQLStr . ' Message: ' . $ex->getMessage(), E_USER_ERROR );
    }

    $sth = NULL;
    $dbh = NULL;
    return $rt;
}

function getOccupancyList($Serial) {

    $dbh = connectToStakkaDB();
    if ($dbh == null) {
        logMsg(E_USER_ERROR, 'getOccupancyList failed.');
        return null;
    }

    if ( $Serial == EJECTED_SERIAL ) { // Not sensible to do the list of ejected discs, could be EXTREMELY large and does not represent anything physical...
        return null;
    }

    $Occupied = array();
    $Slot = 1;

    $SQLStr = "SELECT Slot,
                    CASE WHEN DiscLabel is NULL AND UserDiscName is NULL THEN 6
                         WHEN DiscLabel is NULL THEN 2
                         WHEN UserDiscName is NULL THEN 4
                    ELSE 1
                    END AS Occupied
                FROM DiscList WHERE Occupied = 1 AND Serial = '" . $Serial . "' ORDER BY Slot;";

    $RowList = $dbh->query($SQLStr);
    if ( $RowList ) {
        foreach ( $RowList as $Row ) {
            while ($Slot < $Row['Slot']) $Occupied[$Slot++] = 0;
            $Occupied[$Row['Slot']] = $Row['Occupied'];
            $Slot++;
        }
    }
    while ($Slot <= 100) $Occupied[$Slot++] = 0;

    $dbh = NULL;

    return $Occupied;
}
?>
