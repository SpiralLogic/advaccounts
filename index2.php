<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include 'bootstrap.php';
	$s = new SphinxClient;
	$s->setServer("127.0.0.1", 9312);
	$s->setMatchMode(SPH_MATCH_ANY);
	echo '<pre>';
	$resullt = $s->status();
	var_dump($resullt);