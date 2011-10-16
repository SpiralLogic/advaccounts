<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 15/10/11
	 * Time: 8:56 AM
	 * To change this template use File | Settings | File Templates.
	 */

	$session_save_path = "tcp://127.0.0.1:112211?persistent=1&weight=2&timeout=2&retry_interval=10,  ,tcp://localhost:11211";
	ini_set('session.save_handler', 'memcache');
	ini_set('session.save_path', $session_save_path);
	phpinfo();
?>