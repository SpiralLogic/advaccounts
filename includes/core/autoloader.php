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
  /**

   */
  class Autoload_Exception extends \Exception {

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
    static protected $classes2 = array();
    /**
     * @static

     */
    static public function i() {
      class_alias(__CLASS__, 'Autoloader');
      spl_autoload_register('\\ADV\\Core\\Autoloader::load', TRUE);
      static::$classes2 = Cache::get('autoload.classes2');
      static::$classes = Cache::get('autoload.classes');
      static::$loaded = Cache::get('autoload.paths');
      static::$loaded = array();
      static::$classes = array();
      static::$classes2 = array();
      if (!static::$classes2) {
        $core = include(DOCROOT . 'config' . DS . 'core.php');
        $vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
        static::import_namespaces((array) $core);
        static::add_classes((array) $vendor, VENDORPATH);
      }

      //spl_autoload_register('\\ADV\\Core\\Autoloader::loadFromCache', TRUE, TRUE);
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
     * @param $namespace
     * @param $classes
     */
    static protected function import_namespace($namespace, $classes) {
      static::$classes2 = array_merge(static::$classes2, array_fill_keys($classes, $namespace));
    }
    /**
     * @static
     *
     * @param array $namespaces
     */
    static protected function import_namespaces(array $namespaces) {
      foreach ($namespaces as $namespace => $classes) {
        static::import_namespace($namespace, $classes);
      }
    }
    /**
     * @static
     *
     * @param $paths
     * @param $namespace
     * @param $classname
     *
     * @internal param $path
     * @return string
     */
    static protected function tryPath($paths, $namespace, $classname) {
      $paths = (array) $paths;
      while ($path = array_shift($paths)) {
        $filepath = realpath($path);
        if ($filepath) {
          return static::includeFile($filepath, $namespace, $classname);
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
    static protected function includeFile($filepath, $namespace, $class) {
      if (empty($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be found!');
      }
      if (!is_readable($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be	read at: ' . $filepath);
      }
      /** @noinspection PhpIncludeInspection */
      if (!include_once($filepath)) {
        throw new Autoload_Exception('File for class ' . $class . ' cannot be	loaded from : ' . $filepath);
      }
      static::$loaded[$namespace][$class] = $filepath;
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
    static public function loadFromCache($required_class) {
      $alias = FALSE;
      $classpath = ltrim($required_class, '\\');
      if ($lastNsPos = strripos($classpath, '\\')) {
        $namespace = substr($classpath, 0, $lastNsPos);
        $class = substr($classpath, $lastNsPos + 1);
      }
      $result = FALSE;
      if (isset(static::$loaded[$namespace][$class])) {
        $result = static::tryPath(static::$loaded[$namespace][$class], $namespace, $class);
      }
      elseif (isset(static::$classes[$required_class])) {
        $result = static::tryPath(static::$classes[$class], '/', $class);
      }
      if (!$result) {
        \ADV\Core\Event::register_shutdown(__CLASS__);
      }
      return $result;
    }
    /**
     * @static
     *
     * @param $required_class
     *
     * @internal param $classname
     * @return bool|string
     */
    static public function load($required_class) {
      if (isset(static::$classes2[$required_class])) {
        $required_class = static::$classes2[$required_class] . $required_class;
      }
      $alias = FALSE;
      $classpath = ltrim($required_class, '\\');
      $filename = str_replace('_', DS, $classpath);
      if ($lastNsPos = strripos($classpath, '\\')) {
        $namespace = substr($classpath, 0, $lastNsPos);
        $filename = substr($filename, $lastNsPos + 1);
        $class = substr($classpath, $lastNsPos + 1);
        if (isset(static::$classes2[$class]) && static::$classes2[$class] == $namespace . '\\') {
          $alias = TRUE;
        }
        $namespacepath = str_replace(['\\', 'ADV'], [DS, 'includes'], $namespace);
        $dir = DOCROOT . strtolower($namespacepath);
      }
      elseif (isset(static::$classes[$required_class])) {
        $dir = rtrim(static::$classes[$required_class], '/') . DS . $filename;
      }
      else {
        $dir = APPPATH . strtolower($filename);
      }

      $filename = strtolower($filename);
      $paths[] = $dir . '.php';
      $paths[] = $dir . DS . $filename . '.php';
      $paths[] = $dir . DS . $filename . DS . $filename . '.php';
      $paths[] = $dir . DS . 'classes' . DS . $filename . '.php';
      $result = static::trypath($paths, $namespace, $required_class);
      if ($alias && $class) {
        class_alias(static::$classes2[$class] . $class, $class);
      }
      return $result;
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
        Cache::set('autoload.classes', static::$classes);
      }
      Cache::set('autoload.paths', static::$loaded);
      Cache::set('autoload.classes2', static::$classes2);
    }
  }

  Autoloader::i();
