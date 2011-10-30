<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 27/09/11
	 * Time: 1:55 AM
	 * To change this template use File | Settings | File Templates.
	 */

	class Input {

		const NUMERIC = 1;
		const OBJECT = 2;


		public static function post($var, $default = false, $type = null) {
			return static::_isset($_POST, $var, $default, $type);
		}

		public static function get($var, $default = false, $type = null) {
			return static::_isset($_GET, $var, $default, $type);
		}

		public static function request($var, $default = false, $type = null) {
			return static::_isset($_REQUEST, $var, $default, $type);
		}

		public static function get_post($var, $default = false, $type = null) {
			return static::_get_post($_GET, $_POST, $var, $default, $type);
		}

		public static function post_get($var, $default = false, $type = null) {
			return static::_get_post($_POST, $_GET, $var, $default, $type);
		}

		public static function session($var = array(), $default = false, $type = null) {
			return (!isset($_SESSION)) ? false : static::_isset($_SESSION, $var, $default, $type);
		}

		public static function has_post($vars) {
			$vars = func_get_args();
			foreach ($vars as $var) {
				if (static::post($var) === false) return false;
			}
			return true;
		}

		public static function has_get($vars) {
			$vars = func_get_args();
			foreach ($vars as $var) {
				if (static::get($var) === false) return false;
			}
			return true;
		}

		public static function has($vars) {
			$vars = func_get_args();
			foreach ($vars as $var) {
				if (static::_get_post($_GET, $_POST, $var, false) === false) return false;
			}
			return true;
		}

		protected static function _get_post($first, $second, $var, $default, $type = null) {
			$value = static::_isset($first, $var, $default, $type);
			if ($value === false) $value = static::_isset($second, $var, $type, $default, $type);
			return $value;
		}

		protected static function _isset($array, $var, $default, $type) {

			if (!isset($array[$var])) return $default;
			$value = $array[$var];
			switch ($type) {
				case self::NUMERIC:
					if (!is_numeric($value)) return false;
					return ($value === 0) ? true : $value;
					break;
			}
			return $value;
		}
	}
