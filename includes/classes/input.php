<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 27/09/11
 * Time: 1:55 AM
 * To change this template use File | Settings | File Templates.
 */

	class Input {

		const NUMERIC = '1';


		public static function post($var, $type = null) {
			return static::_isset($_POST, $var, $type);
		}

		public static function get($var, $type = null) {
			return static::_isset($_GET, $var, $type);
		}

		public static function get_post($var, $type = null) {
			return static::_get_post($_GET, $_POST, $var, $type);
		}

		public static function post_get($var, $type = null) {
			return static::_get_post($_POST, $_GET, $var, $type);
		}
public static function session($var,$type=null) {
	return static::_isset($_SESSION,$var,$type);
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
				if (static::_get_post($_GET, $_POST, $var) === false) return false;
			}
			return true;
		}

		protected static function _get_post($first, $second, $var, $type = null) {
			$value = static::_isset($first, $var, $type);
			if ($value === false) $value = static::_isset($second, $var, $type);
			return $value;
		}

		protected static function _isset($array, $var, $type) {
			if (!isset($array[$var])) return false;
			$value = $array[$var];
			switch ($type) {
				case static::NUMERIC:
					if (!is_numeric($value)) return false;
					break;
			}
			return $value;
		}
	}
