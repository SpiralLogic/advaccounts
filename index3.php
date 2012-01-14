<?php

	require 'bootstrap.php';
Page::start('Websales',SA_OPEN);
$webstore = new Modules\Volusion();
	$webstore->doWebsales();
Page::end();
