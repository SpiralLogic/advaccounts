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
 static protected $i = null;
 /**
 * @var bool
 */
 static protected $connected = false;

 /**
 * @static
 * @return Memcached
 */
 static protected function i()
 {
 if (static::$i === null) {
 if (class_exists('Memcached', false)) {
 $i = new Memcached(__DIR__);
 if (!count($i->getServerList())) {
 $i->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
 $i->setOption(Memcached::OPT_SEND_TIMEOUT, 3000);
 $i->setOption(Memcached::OPT_TCP_NODELAY, true);
 $i->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
 $i->setOption(Memcached::OPT_PREFIX_KEY, __DIR__);
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
 * @param $key
 * @param $value
 * @param int $expires
 *
 * @return mixed
 */
 static public function set($key, $value, $expires = 86400)
 {
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
 static public function get($key)
 {
 if (static::i() !== false) {
 $result = static::i()->get($key);
 $result = (static::$i->getResultCode() === Memcached::RES_NOTFOUND) ? false : $result;
 }
 elseif (class_exists('Session', false)) {
 if (!isset($_SESSION['cache'])) {
 $_SESSION['cache'] = array();
 }
 $result = (!isset($_SESSION['cache'][$key])) ? false : $_SESSION['cache'][$key];
 }
 else {
 $result = false;
 }
 return $result;
 }

 /**
 * @static
 * @return mixed
 */
 static public function getStats()
 {
 return (static::$connected) ? static::i()->getStats() : false;
 }

 /**
 * @static
 * @return mixed
 */
 static public function getVersion()
 {
 return (static::$connected) ? static::i()->getVersion() : false;
 }

 /**
 * @static
 * @return mixed
 */
 static public function getServerList()
 {
 return (static::$connected) ? static::i()->getServerList() : false;
 }

 /**
 * @static
 *
 * @param int $time
 */
 static public function flush($time = 0)
 {
 if (static::i()) {
 static::i()->flush($time);
 }
 else {
 $_SESSION['cache'] = array();
 }
 }
}
