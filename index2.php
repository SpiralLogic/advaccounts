<?php
/*	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 21/11/11
	 * Time: 6:27 PM
	 * To change this template use File | Settings | File Templates.
	 */
	ini_set('session.gc_maxlifetime', 36000); // 10hrs
			session_name('ADV' . md5($_SERVER['SERVER_NAME']));

	ini_set('session.save_handler','memcached');

	session_start();
	phpinfo();
	/*
	require_once('bootstrap.php');
	$filecsv = fopen(DOCROOT . '/upload/test.csv', 'r');

	echo '<pre>';
	$items = array();
	$feilds = array();
	while ($line = fgetcsv($filecsv)) {
		if (empty($feilds) && empty($items)) {
			$feilds = $line;
			continue;
		}
		$item = array_combine($feilds, $line);
		$item = new Item($item);
		$item->save($item);
		$items[] = $item;
	}

	var_dump($items);*/

	//$item->save($item);*/
