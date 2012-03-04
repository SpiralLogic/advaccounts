<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Maidenii
 * Date: 29/02/12
 * Time: 10:21 PM
 * To change this template use File | Settings | File Templates.
 */
return array(	
	'enabled' => false, //
	'sql' => false, //
	'pdf' => false, //
	'email' => true, //
	'query_log' => false,
	'select_log' => false, //
	'throttling' => 10, // Log file for error/warning messages. Should be set to any location
	'log_file'=>DOCROOT . 'tmp/errors.log',
);
