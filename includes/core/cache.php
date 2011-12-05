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
		/**
		 * @var bool
		 */
		protected static $connected = false;

		/**
		 * @static
		 * @return Memcached
		 */
		protected static function i() {
			if (static::$i === null) {
				if (class_exists('Memcached', false)) {
					static::$i = new Memcached('ADV');
					static::$connected = static::$i->addServer('127.0.0.1', 11211);
					static::$i->setOption(Memcached::OPT_PREFIX_KEY, DOCROOT);
					if (static::$connected && isset($_GET['reload_config'])) {
						static::$i->flush(0);
					}
				}
			}
			return (static::$connected) ? static::$i : false;
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
			if (static::i() !== false) {
				static::i()->set($key, $value, time() + $expires);
			}
			elseif (class_exists('Session', false)) {
				$_SESSION['cache'][$key] = $value;
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
			if (static::i() !== false) {
				$result = static::i()->get($key);
				$result = (static::$i->getResultCode() === Memcached::RES_NOTFOUND) ? false : $result;
			}
			elseif (class_exists('Session', false)) {
				if (!isset($_SESSION['cache'])) $_SESSION['cache']=array();
				$result = $_SESSION['cache'][$key] ;
			} else {
				$result = false;
			}
			return $result;
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function getStats() {
			return (static::$connected) ? static::i()->getStats() : false;
		}

		/**
		 * @static
		 *
		 * @param int $time
		 */
		public static function flush($time = 0) {
			if (static::i()) {
				static::i()->flush($time);
			} else {
				$_SESSION['cache'] = array();
			}
		}
	}
