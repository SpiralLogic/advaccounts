<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 13/11/11
	 * Time: 4:45 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Cache
	{
		protected static $instance = null;

		protected static function _get()
		{
			if (static::$instance === null) {
				static::$instance = new Memcached('fa');
				static::$instance->addServer('127.0.0.1', 11211);print_r(static::$instance->getStats());

			}

		}

		public static function set($key, $value)
		{
			static::_get();
			static::$instance->set($key, $value);
			return $value;
		}

		public static function get($key)
		{
			static::_get();
			return static::$instance->get($key);
		}
		public static function getStats()
			{
				static::_get();
				return static::$instance->getStats();
			}
		public static function renew($key, $value)
		{
			static::_get();
			static::$instance->set($key, $value);
		}
	}
