<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Autoload_Exception extends Exception {

  }

  /**

   */
  class Autoloader {

    /**
     * @var array
     */
    static protected $loaded = array();
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
    /**
     * @static

     */
    static public function i() {
      spl_autoload_register('Autoloader::load', TRUE);
      //static::$classes = \Core\Cache::get('autoload.classes');
      //static::$loaded = \Core\Cache::get('autoload.paths');
      if (!static::$classes) {
        $core = include(DOCROOT . 'config' . DS . 'core.php');
        $vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
        static::add_classes((array) $core, COREPATH);
        static::add_classes((array) $vendor, VENDORPATH);
      }
      //   spl_autoload_register('Autoloader::loadFromCache', TRUE);
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
      foreach ($classes as $dir => $class) {
        if (!is_string($dir)) {
          $dir = '';
        }
        static::$classes[$class] = $type . $dir;
      }
    }
    /**
     * @static
     *
     * @param $paths
     * @param $classname
     *
     * @internal param $path
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
      return FALSE;
    }
    /**
     * @static
     *
     * @param $filepath
     * @param $class
     *
     * @return bool
     * @throws Autoload_Exception
     */
    static protected function includeFile($filepath, $class) {
      if (empty($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be found!');
      }
      if (!is_readable($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be	read at: ' . $filepath);
      }
      /** @noinspection PhpIncludeInspection */
      if (!include($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be	loaded from : ' . $filepath);
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
      if (isset(static::$loaded[$classname])) {
        $result = static::tryPath(static::$loaded[$classname], $classname);
      }
      elseif (isset(static::$classes[$classname])) {
        $result = static::tryPath(static::$classes[$classname], $classname);
      }
      if (!$result) {
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
    static public function load($required_class) {
      $classpath = ltrim($required_class, '\\');
      if (isset(static::$classes['\\Core\\' . $classpath]) ) {
        if (class_alias('\\Core\\' . $classpath, $classpath)) {
          return TRUE;
        }
      }

      $filename = str_replace('_', DS, $classpath);
      if ($lastNsPos = strripos($classpath, '\\')) {
        $namespace = substr($classpath, 0, $lastNsPos);
        $filename = substr($filename, $lastNsPos + 1);
        $namespacepath = str_replace('\\', DS, $namespace);
        $dir = DOCROOT . 'includes' . DS . strtolower($namespacepath);
      }
      elseif (isset(static::$classes[$required_class])) {

        $dir = rtrim(static::$classes[$required_class], '/') . DS . strtolower($filename);
      }
      else {
        $dir = APPPATH . strtolower($filename);
      }
      $filename = strtolower($filename);
      if (!is_readable($dir . '.php')) {
        $filename = $dir . DS . $filename . '.php';
      }
      else {
        $filename = $dir . '.php';
      }
      return static::trypath($filename, $required_class);
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
    /**
     * @static
     * @return array
     */
    static public function getPerf() {
      array_walk(static::$loadperf, function(&$v) {
        $v[1] = Files::convert_size($v[1]);
        $v[2] = Dates::getReadableTime($v[2]);
        $v[3] = Dates::getReadableTime($v[3]);
      });
      return static::$loadperf;
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
        \Core\Cache::set('autoload.classes', static::$classes);
      }
      \Core\Cache::set('autoload.paths', static::$loaded);
    }
  }

  Autoloader::i();
