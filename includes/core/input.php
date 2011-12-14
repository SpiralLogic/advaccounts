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
		/**
		 *
		 */
		const NUMERIC = 'number';
		/**
		 *
		 */
		const OBJECT = 'object';
		/**
		 *
		 */
		const STRING = 'string';
		/**
		 *
		 */
		const BOOL = 'boolean';
		/**
		 * @var int
		 */
		protected static $default_number = 0;
		/**
		 * @var string
		 */
		protected static $default_string = '';
		/**
		 * @var bool
		 */
		protected static $default_bool = false;

		/***
		 * @static
		 *
		 * @param mixed $var		 $_POST variable to return
		 * @param Input $type		Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
		 * @param null	$default Default value if there is no current variable
		 *
		 * @return bool|int|string|object
		 */
		public static function post($var, $type = null, $default = null) {
			return static::_isset($_POST, $var, $type, $default);
		}

		/***
		 * @static
		 *
		 * @param			 $var
		 * @param Input $type		Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
		 * @param null	$default Default value if there is no current variable
		 *
		 * @internal param mixed $public $_GET variable to return
		 * @return bool|int|string|object
		 */
		public static function get($var, $type = null, $default = null) {
			return static::_isset($_GET, $var, $type, $default);
		}

		/***
		 * @static
		 *
		 * @param mixed $var		 $_REQUEST variable to return
		 * @param Input $type		Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
		 * @param null	$default Default value if there is no current variable
		 *
		 * @return bool|int|string|object
		 */
		public static function request($var, $type = null, $default = null) {
			return static::_isset($_REQUEST, $var, $type, $default);
		}

		/***
		 * @static
		 *
		 * @param mixed $var		 $_GET variable to return if it doesn't exist $_POST will be tried
		 * @param Input $type		Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
		 * @param null	$default Default value if there is no current variable
		 *
		 * @return bool|int|string|object
		 */
		public static function get_post($var, $type = null, $default = null) {
			return static::get_post($_GET, $_POST, $var, $type, $default);
		}

		/***
		 * @static
		 *
		 * @param mixed $var		 $_POST	variable to return if it doesn't exist $_GET will be returned
		 * @param Input $type		Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
		 * @param null	$default Default value if there is no current variable
		 *
		 * @return bool|int|string|object
		 */
		public static function post_get($var, $type = null, $default = null) {
			return static::get_post($_POST, $_GET, $var, $type, $default);
		}

		/***
		 * @static
		 *
		 * @param mixed $var		 $_SESSION variable to return
		 * @param Input $type		Validate whether variable is of this type (Input::NUMERIC, Input::OBJECT, INPUT::STRING, Input::BOOL
		 * @param null	$default Default value if there is no current variable
		 *
		 * @return bool|int|string|object
		 */
		public static function session($var = array(), $type = null, $default = null) {
			return (!isset($_SESSION)) ? false : static::_isset($_SESSION, $var, $type, $default);
		}

		/***
		 * @static
		 *
		 * @param mixed $vars Test for existance of $_POST variable
		 *
		 * @return bool
		 */
		public static function has_post($vars) {
			if (is_null($vars)) {
				return true;
			}
			return (static::_has($_POST, func_get_args()));
		}

		/***
		 * @static
		 *
		 * @param mixed $vars Test for existance of $_GET variable
		 *
		 * @return bool
		 */
		public static function has_get($vars) {
			if (is_null($vars)) {
				return true;
			}
			return (static::_has($_GET, func_get_args()));
		}

		/***
		 * @static
		 *
		 * @param mixed $vars Test for existance of either $_POST or $_GET variable
		 *
		 * @return bool
		 */
		public static function has($vars) {
			if (is_null($vars)) {
				return true;
			}
			return (static::_has($_REQUEST, func_get_args()));
		}

		/***
		 * @static
		 *
		 * @param mixed $vars Test for existence of either $_POST or $_GET variable
		 *
		 * @return bool
		 */
		public static function has_session($vars) {
			if (is_null($vars)) {
				return true;
			}
			return (static::_has($_SESSION, func_get_args()));
		}

		/**
		 * @static
		 *
		 * @param			$first
		 * @param			$second
		 * @param			$var
		 * @param null $type
		 * @param null $default
		 *
		 * @return bool|int|null|string
		 */
		protected static function _get_post($first, $second, $var, $type = null, $default = null) {
			$array = (static::_has($first, $var)) ? $first : $second;
			return static::_isset($array, $var, $type, $default);
		}

		/**
		 * @static
		 *
		 * @param array $array
		 * @param			 $vars
		 *
		 * @return bool
		 */
		protected static function _has(array $array, $vars) {
			if (is_null($vars)) {
				return true;
			}
			$vars = func_get_args();
			array_shift($vars);
			foreach ($vars as $var) {
				if (static::_isset($array, $var) === false) {
					return false;
				}
			}
			return true;
		}

		/**
		 * @static
		 *
		 * @param array $array
		 * @param			 $var
		 * @param null	$type
		 * @param null	$default
		 *
		 * @return bool|int|null|string
		 */
		protected static function _isset(array $array, $var, $type = null, $default = null) {
			$value = (is_string($var) && isset($array[$var])) ? $array[$var] : $default; //chnage back to null if fuckoutz happen
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
