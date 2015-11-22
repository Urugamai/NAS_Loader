<?php
    // The various methods by which a file can be moved around
    $method_Device = array( 'Name' => 'Device', 'Selected' => 'selected' );
    $method_FileSystem = array('Name' => 'FileSystem', 'Selected' => '' );
    $method_FTP = array('Name' => 'FTP', 'Selected' => '' );
    $method_scp = array('Name' => 'scp', 'Selected' => '' );

    $FileMethods = array( $method_Device, $method_FileSystem, $method_FTP, $method_scp);

    //!     Deliver the list of File Methods we want to be able to use to move files around
    echo json_encode( $FileMethods );

//    echo '{"Name":"Device", "Selected":"selected"}';

?>
