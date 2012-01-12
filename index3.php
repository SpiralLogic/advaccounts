<?php

	require 'bootstrap.php';
	echo '<pre>';

$webstore = new Modules\Volusion();
$webstore->doWebsales();
