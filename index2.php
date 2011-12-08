<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 7/12/11
	 * Time: 12:31 PM
	 * To change this template use File | Settings | File Templates.
	 */

	include('bootstrap.php');
	$_POST['amount']='3';
	var_dump(!Validation::input_num('amount'));