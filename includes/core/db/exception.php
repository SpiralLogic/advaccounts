<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 31/10/11
	 * Time: 3:23 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB_Exception extends Exception
	{
		public function __construct($message, $code = 0, Exception $previous = null)
			{
				parent::__construct($message, $code, $previous);
			}

		public function __toString()
			{
				return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
			}
	}
