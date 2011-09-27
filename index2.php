<?php

	$path_to_root = ".";
	if (!file_exists($path_to_root . '/config.php'))
		header("Location: " . "/install/index.php");

	$page_security = 'SA_OPEN';

$_POST['test']=3;
	include_once("includes/session.inc");
print_r(Input::has('id','test'));