<?php
	/**
	 * PHP version 5.4
	 * @category  PHP
	 * @package   ADVAccounts
	 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
	 * @copyright 2010 - 2012
	 * @link      http://www.advancedgroup.com.au
	 **/

  trait HookTrait {

		/** @var Hook */
		public static $hooks = NULL;
		public static function _register($hook, $object, $function, $arguments = array()) {
			if (self::$hooks === NULL) {
				self::$hooks = new Hook();
			}
			$callback = $object . '::' . $function;
			if (!is_callable($callback)) {
				throw new HookException("Class $object doesn't have a callable function $function");
			}
			self::$hooks->add($hook, $callback, $arguments);
		}
	}
