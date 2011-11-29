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
		/**
		 * @var Memcached
		 */
		protected static $instance = null;

		/**
		 * @static
		 * @return Memcached
		 */
		protected static function _i()
		{
			if (static::$instance === null) {
				static::$instance = new Memcached('ADV');
				static::$instance->addServer('127.0.0.1', 11211);
				if (isset($_GET['reload_config'])) static::$instance->flush(0);
			}
			return static::$instance;
		}

		/**
		 * @static
		 *
		 * @param $key
		 * @param $value
		 *
		 * @return mixed
		 */public static function set($key, $value)
		{
			static::_i()->set($key, $value,time()+(60*60+24));
			return $value;
		}

		/**
		 * @static
		 * @param $key
		 * @return mixed
		 */public static function get($key)
		{
			return static::_i()->get($key);
		}

		/**
		 * @static
		 * @return mixed
		 */public static function getStats()
		{
			return static::_i()->getStats();
		}

		/**
		 * @static
		 * @param $key
		 * @param $value
		 */public static function renew($key, $value)
		{
			static::_i()->set($key, $value);
		}









		public static function flush($time=0)
			{
				static::_i()->flush($time);
			}
	}
