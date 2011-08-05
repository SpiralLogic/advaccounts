<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 5/08/11
 * Time: 1:53 PM
 * To change this template use File | Settings | File Templates.
 */
 

class Config {


		protected static $_vars = array();

		public static function set($var, $value) {
			static::$_vars[$var] = $value;
			return $value;
		}

		public static function get($var) {
			return static::$_vars[$var];
		}
	}