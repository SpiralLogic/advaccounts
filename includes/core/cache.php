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
		protected static $i = null;
		protected static $connected = false;

		/**
		 * @static
		 * @return Memcached
		 */
		protected static function _i() {
			if (static::$i === null) {
				static::$i = new Memcached('ADV');
				static::$connected = static::$i->addServer('127.0.0.1', 11211);
				static::$i->setOption(Memcached::OPT_PREFIX_KEY, DOCROOT);

				if (static::$connected && isset($_GET['reload_config'])) {
					static::$i->flush(0);
				}
			}
			return (static::$connected) ? static::$i:false;
		}

		/**
		 * @static
		 *
		 * @param		 $key
		 * @param		 $value
		 * @param int $expires
		 *
		 * @return mixed
		 */
		public static function set($key, $value, $expires = 86400) {
			if (static::_i()!==false) {
				static::_i()->set($key, $value, time() + $expires);
			}
			elseif (class_exists('Session',false)) {
				Session::i()->Cache[$key] = $value;
			}
			return $value;
		}

		/**
		 * @static
		 *
		 * @param $key
		 *
		 * @return mixed
		 */
		public static function get($key) {
			if (static::_i()!==false) {
				$result = static::_i()->get($key);
				$result = (static::$i->getResultCode() === Memcached::RES_NOTFOUND) ? false : $result;
			}
			elseif (class_exists('Session',false)) {
				$result = Session::i()->Cache[$key];
			}
			return $result;
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function getStats() {
			return (static::$connected) ? static::_i()->getStats() : false;
		}

		public static function flush($time = 0) {
			if (static::i())	{
				static::_i()->flush($time);
			} else {
				Session::i()->Cache = array();
			}
		}
	}
