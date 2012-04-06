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

    static protected $time = 0;
    /**
     * @var array
     */
    static protected $classes = array();
    static protected $global_classes = array();
    static protected $loaded = array();
    /**
     * @static

     */
    static public function i() {
      class_alias(__CLASS__, 'Autoloader');
      spl_autoload_register('\\ADV\\Core\\Autoloader::load', TRUE);
      static::$global_classes = Cache::get('autoload.global_classes',array());
      static::$classes = Cache::get('autoload.classes',array());
      static::$loaded = Cache::get('autoload.paths',array());
      if (!static::$global_classes) {
        $core = include(DOCROOT . 'config' . DS . 'core.php');
        static::import_namespaces((array) $core);
        Event::register_shutdown(__CLASS__);
      }
      if (!static::$classes) {
        $vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
        static::add_classes((array) $vendor, VENDORPATH);
        Event::register_shutdown(__CLASS__);
      }
      spl_autoload_register('\\ADV\\Core\\Autoloader::loadFromCache', TRUE,TRUE);
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
      static::$global_classes = array_merge(static::$global_classes, array_fill_keys($classes, $namespace));
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
     * @param $classname
     *
     * @internal param $path
     * @return string
     */
    static protected function tryPath($paths, $required_class) {
      $paths = (array) $paths;
      while ($path = array_shift($paths)) {
        $filepath = realpath($path);
        if (is_readable($filepath)) {
          return static::includeFile($filepath, $required_class);
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
    static protected function includeFile($filepath, $required_class) {
      if (empty($filepath)) {
        throw new Autoload_Exception('File for class ' . $required_class . ' cannot be found!');
      }
      if (!is_readable($filepath)) {
        throw new Autoload_Exception('File for class ' . $required_class . ' cannot be	read at: ' . $filepath);
      }
      /** @noinspection PhpIncludeInspection */
      if (!include_once($filepath)) {
        throw new Autoload_Exception('File for class ' . $required_class . ' cannot be	loaded from : ' . $filepath);
      }
      static::$loaded[$required_class] = $filepath;
      return TRUE;
    }
    /**
     * @static
     *
     * @param $required_class
     *
     * @return bool|string
     */
    static public function loadFromCache($required_class) {
      $result = $alias = FALSE;
      if (isset(static::$global_classes[$required_class])) {
        $required_class = static::$global_classes[$required_class] . $required_class;
        $alias = TRUE;
      }
      if (isset(static::$loaded[$required_class])) {
        try {
          $result = static::includeFile(static::$loaded[$required_class], $required_class);
        }
        catch (Autoload_Exception $e) {
          unset(static::$loaded[$required_class]);
          Event::register_shutdown(__CLASS__);
        }
        if ($alias) {
          $class = substr($required_class, strripos($required_class, '\\') + 1);
          class_alias(static::$global_classes[$required_class] . $class, $class);
        }
      }
      return $result;
    }
    /**
     * @static
     *
     * @param $required_class
     *
     * @internal param $required_class
     * @return bool|string
     */
    static public function load($required_class) {
      if (isset(static::$global_classes[$required_class])) {
        $required_class = static::$global_classes[$required_class] . $required_class;
      }
      $alias = FALSE;
      $classpath = ltrim($required_class, '\\');
      $class_file = str_replace('_', DS, $classpath);
      if ($lastNsPos = strripos($classpath, '\\')) {
        $namespace = substr($classpath, 0, $lastNsPos);
        $class_file = substr($class_file, $lastNsPos + 1);
        $class = substr($classpath, $lastNsPos + 1);
        if (isset(static::$global_classes[$class]) && static::$global_classes[$class] == $namespace . '\\') {
          $alias = TRUE;
        }
        $namespacepath = str_replace(['\\', 'ADV'], [DS, 'includes'], $namespace);
        $dir = DOCROOT . strtolower($namespacepath);
      }
      elseif (isset(static::$classes[$required_class])) {
        $dir = rtrim(static::$classes[$required_class], '/') . DS . $class_file;
      }
      else {
        $dir = APPPATH . strtolower($class_file);
      }
      $class_file = strtolower($class_file);
      $paths[] = $dir . '.php';
      $paths[] = $dir . DS . $class_file . '.php';
      $paths[] = $dir . DS . $class_file . DS . $class_file . '.php';
      $paths[] = $dir . DS . 'classes' . DS . $class_file . '.php';
      $result = static::trypath($paths, $required_class);
      if ($alias && $class) {
        class_alias(static::$global_classes[$class] . $class, $class);
      }
      return $result;
    }
    /**
     * @static

     */
    static public function _shutdown() {
      Cache::set('autoload.classes', static::$classes);
      Cache::set('autoload.global_classes', static::$global_classes);
      Cache::set('autoload.paths', static::$loaded);
    }
  }

  Autoloader::i();
