<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
  class Autoload_Exception extends \Exception {

  }

  /**

   */
  class Autoloader {

    /**
     * @var array
     */
    static $loaded = array();
    /**
     * @var array
     */
    static protected $loadperf = array();
    /**
     * @var int
     */
    static protected $time = 0;
    /**
     * @var array
     */
    static protected $classes = array();
    static protected $namespaces = array();
    /**
     * @static

     */
    static public function i() {
      class_alias(__CLASS__, '\Autoloader');
      Autoloader::add_namespace('ADV\\Core', COREPATH);
      Autoloader::add_namespace('\\', COREPATH);
      ini_set('unserialize_callback_func', 'ADV\Core\Autoloader::load'); // set your callback_function
      spl_autoload_register('ADV\Core\Autoloader::loadCore', TRUE);

      static::$classes = Cache::get('autoload.classes', array());
      static::$loaded = Cache::get('autoload.paths', array());
      if (!static::$classes) {
        $core = include(DOCROOT . 'config' . DS . 'core.php');
        $vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
        static::add_classes((array) $core, COREPATH);
        static::add_classes((array) $vendor, VENDORPATH);
        Event::register_shutdown(__CLASS__);
      }
//			spl_autoload_register('ADV\Core\Autoloader::loadVendor', true, true);
      spl_autoload_register('ADV\Core\Autoloader::loadApp', TRUE, TRUE);
      //		spl_autoload_register('ADV\Core\Autoloader::loadInterface', true, true);
      //	spl_autoload_register('ADV\Core\Autoloader::loadModule', true, true);
      //		spl_autoload_register('ADV\Core\Autoloader::loadFromCache', true, true);
    }
    /**
     * @static
     *
     * @param $classname
     */
    static public function load($classname) {
      class_exists($classname);
    }
    /**
     * @static
     *
     * @param array $path
     */
    static public function add_path($path = array()) {
      $path = (array) $path;
      $path[] .= get_include_path();
      set_include_path(implode(PATH_SEPARATOR, $path));
    }
    /**
     * @static
     *
     * @param array $classes
     * @param       $type
     */
    static protected function add_classes(array $classes, $type) {
      $classes = array_flip(array_diff_key(array_flip($classes), (array) static::$loaded));
      foreach ($classes as $dir => $class) {
        if (!is_string($dir)) {
          $dir = '';
        }
        static::$classes[$class] = $type . $dir . str_replace('_', DS, $class) . '.php';
      }
    }
    /**
     * @static
     *
     * @param $path
     *
     * @return string
     */
    static protected function tryPath($paths, $classname) {
      $paths = (array) $paths;
      while ($path = array_shift($paths)) {
        $filepath = realpath($path);
        if ($filepath) {
          return static::includeFile($filepath, $classname);
        }
      }
      if (isset(static::$loaded[$classname])) {
        unset (static::$loaded[$classname]);
      }
      static::$classes = FALSE;
      //	Cache::delete('autoload.classes');
      return FALSE;
    }
    /**
     * @static
     *
     * @param $filepath
     * @param $class
     *
     * @throws Autoload_Exception
     */
    static protected function includeFile($filepath, $class) {
      if (empty($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be found!');
      }
      /** @noinspection PhpIncludeInspection */
      if (!include($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be	loaded from : ' . $filepath);
      }
      if (!isset(static::$loaded[$class])) {
        static::$loaded[$class] = $filepath;
        if ($class != 'Cache' && $class != 'Event') {
          //				Event::register_shutdown(__CLASS__);
        }
      }
      //	static::$loadperf[$class] = array($class, memory_get_usage(true), microtime(true) - static::$time, microtime(true) - ADV_START_TIME);
      return TRUE;
    }
    /**
     * @static
     *
     * @param $classname
     *
     * @return bool|string
     */
    static public function loadFromCache($classname) {
      $result = FALSE;
      if (isset(static::$loaded['ADV\\Core\\' . $classname])) {
        $result = static::tryPath(static::$loaded['ADV\\Core\\' . $classname], 'ADV\\Core\\' . $classname);
        if ($result) {

          class_alias('ADV\\Core\\' . $classname, $classname);
        }
      }
      elseif (isset(static::$loaded[$classname])) {
        $result = static::tryPath(static::$loaded[$classname], $classname);
        if ($result && strstr($classname, 'ADV\\Core\\')) {
          class_alias($classname, str_replace('ADV\\Core\\', '', $classname));
        }
      }
      elseif (isset(static::$classes[$classname])) {
        $result = static::tryPath(static::$classes[$classname], $classname);
      }
      if (!$result && $classname != 'ADV\Core\Cache' && $classname != 'ADV\Core\Event') {
        Event::register_shutdown(__CLASS__);
      }
      return $result;
    }
    /**
     * @static
     *
     * @param $classname
     *
     * @return bool|string
     */
    static public function loadModule($classname) {
      if (strpos($classname, 'Modules') === FALSE) {
        return FALSE;
      }
      $class = explode("\\", $classname);
      $mainclass = array_pop($class);
      $class[] = (count($class) > 1) ? 'classes' : $mainclass;
      $class[] = $mainclass;
      $class = implode(DS, $class);
      return static::trypath(DOCROOT . strtolower($class) . '.php', $classname);
    }
    /**
     * @static
     *
     * @param $classname
     *
     * @return bool|string
     */
    static public function loadInterface($classname) {
      $class = str_replace('_', DS, $classname);
      if (substr($class, 0, 1) != 'I') {
        return FALSE;
      }
      return static::trypath(APPPATH . 'interfaces' . DS . substr($class, 1) . '.php', $classname);
    }
    /**
     * @static
     *
     * @param $classname
     *
     * @return string
     */
    static public function loadApp($classname) {
      return static::makePaths($classname, APPPATH);
    }
    /**
     * @static
     *
     * @param $classname
     *
     * @return string
     */
    static public function loadVendor($classname) {
      return static::makePaths($classname, VENDORPATH);
    }
    /**
     * @static
     *
     * @param $classname
     *
     * @return string
     */
    static public function loadCore($classname) {
      $class = ltrim(strrchr($classname, '\\'), '\\') ? : $classname;
      $namespace = ($classname == $class) ? '\\' : substr($classname, 0, -1 - strlen($class));
      var_dump($class, $namespace);
      if (array_key_exists($namespace, static::$namespaces)) {
        if (substr($classname, -5) == 'Trait') {
          $class = ltrim(strrchr('traits' . DS . substr($classname, 0, -5), '\\'), '\\') ? : $classname;
        }

        $path = static::$namespaces[$namespace] . strtolower($class) . '.php';
        if (static::tryPath([$path], $classname)) {
          class_alias($classname, $class);
          return TRUE;
        }
      }
      return FALSE;
    }

    static protected function makePaths($classname, $path) {
      $class = str_replace('_', DS, $classname);
      $lowerclass = strtolower($class);
      $paths[] = $path . $class . '.php';
      $paths[] = $path . $lowerclass . '.php';
      $paths[] = $path . $class . DS . $class . '.php';
      $paths[] = $path . $lowerclass . DS . $lowerclass . '.php';
      return static::tryPath($paths, $classname);
    }
    static public function import($class, $namespace = '') {
    }
    static public function add_namespace($namespace, $path) {
      static::$namespaces[$namespace] = $path;
    }
    /**
     * @static
     * @return array
     */
    static public function getLoaded() {
      return static::$loaded;
    }
    /**
     * @static

     */
    static public function _shutdown() {
      if (static::$classes) {
        Cache::set('autoload.classes', static::$classes);
      }
      Cache::set('autoload.paths', static::$loaded);
    }
  }

  Autoloader::i();
