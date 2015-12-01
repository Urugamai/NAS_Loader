<?php
	$postdata = file_get_contents("php://input");
	$request = json_decode($postdata);

	// header('Content-Type: application/json');
	// $data1 = array('Name' => 'testing', 'Selected' => 'selected');
    echo('test');  // '{[{"Name":"Device", "Selected":"selected"}]}';
?>