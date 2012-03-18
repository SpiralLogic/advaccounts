<?php
	/**
	 * PHP version 5.4
	 *
	 * @category  PHP
	 * @package   ADVAccounts
	 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
	 * @copyright 2010 - 2012
	 * @link      http://www.advancedgroup.com.au
	 *
	 **/
	class Cache
	{
		/**
		 * @var Memcached
		 */
		static protected $i = null;
		/**
		 * @var bool
		 */
		static protected $connected = false;
		/**
		 * @static
		 * @return Memcached
		 */
		static protected function i() {
			if (static::$i === null) {
				if (class_exists('Memcached', false)) {
					$i = new Memcached($_SERVER["SERVER_NAME"] . '.');
					if (!count($i->getServerList())) {
						$i->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
						$i->setOption(Memcached::OPT_SEND_TIMEOUT, 3000);
						$i->setOption(Memcached::OPT_TCP_NODELAY, true);
						$i->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
						$i->setOption(Memcached::OPT_PREFIX_KEY, $_SERVER["SERVER_NAME"] . '.');
						(Memcached::HAVE_IGBINARY) and $i->setOption(Memcached::SERIALIZER_IGBINARY, true);
						$i->addServer('127.0.0.1', 11211);
					}
					static::$connected = ($i->getVersion() !== false);
					if (static::$connected && isset($_GET['reload_cache'])) {
						$i->flush(0);
					}
					static::$i = $i;
				}
			}
			return (static::$connected) ? static::$i : false;
		}
		/**
		 * @static
		 *
		 * @param     $key
		 * @param     $value
		 * @param int $expires
		 *
		 * @return mixed
		 */
		static public function set($key, $value, $expires = 86400) {
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
		 */
		static public function delete($key) {
			if (static::i() !== false) {
				static::i()->delete($key);
			}
			elseif (class_exists('Session', false)) {
				unset($_SESSION['cache'][$key]);
			}
		}
		/**
		 * @static
		 *
		 * @param $key
		 *
		 * @return mixed
		 */
		static public function get($key, $default = false) {
			if (static::i() !== false) {
				$result = static::i()->get($key);
				$result = (static::$i->getResultCode() === Memcached::RES_NOTFOUND) ? $default : $result;
			}
			elseif (class_exists('Session', false)) {
				if (!isset($_SESSION['cache'])) {
					$_SESSION['cache'] = array();
				}
				$result = (!isset($_SESSION['cache'][$key])) ? $default : $_SESSION['cache'][$key];
			}
			else {
				$result = $default;
			}
			return $result;
		}
		/**
		 * @static
		 * @return mixed
		 */
		static public function getStats() {
			return (static::$connected) ? static::i()->getStats() : false;
		}
		/**
		 * @static
		 * @return mixed
		 */
		static public function getVersion() {
			return (static::$connected) ? static::i()->getVersion() : false;
		}
		/**
		 * @static
		 * @return mixed
		 */
		static public function getServerList() {
			return (static::$connected) ? static::i()->getServerList() : false;
		}
		/**
		 * @static
		 *
		 * @param int $time
		 */
		static public function flush($time = 0) {
			if (static::i()) {
				static::i()->flush($time);
			}
			else {
				$_SESSION['cache'] = array();
			}
		}
	}
