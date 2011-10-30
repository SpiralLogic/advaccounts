<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 27/09/11
	 * Time: 1:55 AM
	 * To change this template use File | Settings | File Templates.
	 */

	class Input
	{

		const NUMERIC = 'number';
		const OBJECT = 'object';
		const STRING = 'string';
		const BOOL = 'boolean';
		protected $default_number = 0;
		protected $default_string = '';
		protected $default_bool = false;

		public static function post($var, $type = null, $default = null)
		{
			return static::_isset($_POST, $var, $type, $default);
		}

		public static function get($var, $type = null, $default = null)
		{
			return static::_isset($_GET, $var, $type, $default);
		}

		public static function request($var, $type = null, $default = null)
		{
			return static::_isset($_REQUEST, $var, $type, $default);
		}

		public static function get_post($var, $type = null, $default = null)
		{
			return static::_get_post($_GET, $_POST, $var, $type, $default);
		}

		public static function post_get($var, $type = null, $default = null)
		{
			return static::_get_post($_POST, $_GET, $var, $type, $default);
		}

		public static function session($var = array(), $type = null, $default = null)
		{
			return (!isset($_SESSION)) ? false : static::_isset($_SESSION, $var, $type, $default);
		}

		public static function has_post($vars)
		{
			$vars = func_get_args();
			foreach (
				$vars as $var
			) {
				if (static::post($var) === false) {
					return false;
				}
			}
			return true;
		}

		public static function has_get($vars)
		{
			$vars = func_get_args();
			foreach (
				$vars as $var
			) {
				if (static::get($var) === false) {
					return false;
				}
			}
			return true;
		}

		public static function has($vars)
		{
			$vars = func_get_args();
			foreach (
				$vars as $var
			) {
				if (static::_get_post($_GET, $_POST, $var) === false) {
					return false;
				}
			}
			return true;
		}

		protected static function _get_post($first, $second, $var, $type = null)
		{
			$value = static::_isset($first, $var, $type);
			if ($value === false) {
				$value = static::_isset($second, $var, $type);
			}
			return $value;
		}

		protected static function _isset($array, $var, $type, $default = null)
		{

			$value = (!isset($array[$var])) ? false : $array[$var];
			switch ($type) {
			case self::NUMERIC:
				if (!$value || !is_numeric($value)) {
					return self::$default_number;
				}
				return ($value === self::$default_number) ? true : $value;
			case self::STRING:
				if (!$value || !is_string($value)) {
					return self::$default_string;
				}
			}
			return $value;
		}
	}
