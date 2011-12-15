<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include('bootstrap.php');
	echo '<pre>';
	var_dump(Cache::getStats());
	var_dump(Cache::getVersion());
