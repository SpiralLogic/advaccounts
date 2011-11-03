<?php
	/**
	 * Fuel is a fast, lightweight, community driven PHP5 framework.
	 *
	 * @package		Fuel
	 * @version		1.0
	 * @author		 Fuel Development Team
	 * @license		MIT License
	 * @copyright	2010 - 2011 Fuel Development Team
	 * @link			 http://fuelphp.com
	 */
	class Adv_Exception extends Exception
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