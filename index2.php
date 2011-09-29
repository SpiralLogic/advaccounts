<?php


		$_POST['test'] = 3;
		error_reporting(E_ALL);
		ini_set('display_errors', 'On');

		session_name('FA' . md5('/var/www/dev.advaccounts/web/includes'));
		echo dirname((__FILE__));
		echo '<pre>';
		session_start();
		var_dump($_SESSION);
