<?php

$dbuser="whycloud_lnreade";
$dbpassword="LN@123!";
$database="whycloud_lightnovel";
mysql_connect('localhost',$dbuser,$dbpassword);
@mysql_select_db($database) or die( "Unable to select database");


?>