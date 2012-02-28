<?php

function resellerclubhosting_ConfigOptions() {

	# Should return an array of the module options for each product - maximum of 24

    $configarray = array(
     "Hosting Plan" => array( "Type" => "dropdown", "Options" => "Dummy Plan 1, Dummy Plan 2", "Description" => "Select a hosting plan to associate with this product"),
	);

	return $configarray;

}


?>