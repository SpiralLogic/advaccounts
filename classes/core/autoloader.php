<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
  /**

   */
  class Autoload_Exception extends \Exception
  {
  }

  /**

   */
  class Autoloader
  {
    /**
     * @var int
     */
    protected static $time = 0;
    /**
     * @var array
     */
    protected static $classes = array();
    /**
     * @var array
     */
    protected static $global_classes = array();
    /**
     * @var array
     */
    public static $loaded = array();
    /**
     * @static

     */
    public static function i()
    {
      class_alias(__CLASS__, 'Autoloader');
      spl_autoload_register('\\ADV\\Core\\Autoloader::load', true);
      $cachedClasses = \ADV\Core\Cache::get('autoload', array());
      if ($cachedClasses) {
        static::$global_classes = $cachedClasses['global_classes'];
        static::$classes        = $cachedClasses['classes'];
        static::$loaded         = $cachedClasses['paths'];
      } else {
        $core = include(DOCROOT . 'config' . DS . 'core.php');
        static::import_namespaces((array) $core);
        $vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
        static::add_classes((array) $vendor, VENDORPATH);
      }
      spl_autoload_register('\\ADV\\Core\\Autoloader::loadFromCache', true, true);
    }
    /**
     * @static
     *
     * @param array $classes
     * @param       $type
     */
    protected static function add_classes(array $classes, $type)
    {
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
    protected static function import_namespace($namespace, $classes)
    {
      static::$global_classes = array_merge(static::$global_classes, array_fill_keys($classes, $namespace));
    }
    /**
     * @static
     *
     * @param array $namespaces
     */
    protected static function import_namespaces(array $namespaces)
    {
      foreach ($namespaces as $namespace => $classes) {
        static::import_namespace($namespace, $classes);
      }
    }
    /**
     * @static
     *
     * @param $paths
     * @param $required_class
     *
     * @internal param $classname
     * @internal param $path
     * @return string
     */
    protected static function tryPath($paths, $required_class)
    {
      $paths = (array) $paths;
      while ($path = array_shift($paths)) {
        $filepath = realpath($path);
        if (is_readable($filepath)) {
          return static::includeFile($filepath, $required_class);
        }
      }

      return false;
    }
    /**
     * @static
     *
     * @param $filepath
     * @param $required_class
     *
     * @throws Autoload_Exception
     * @internal param $class
     * @return bool
     */
    protected static function includeFile($filepath, $required_class)
    {
      if (empty($filepath)) {
        throw new Autoload_Exception('File for class ' . $required_class . ' cannot be found!');
      }
      if (!include_once($filepath)) {
        throw new Autoload_Exception('File for class ' . $required_class . ' cannot be	loaded from : ' . $filepath);
      }
      if (!isset(static::$loaded[$required_class])) {
        static::$loaded[$required_class] = $filepath;
        if (is_callable('Event::register_shutdown')) {
          Event::register_shutdown(__CLASS__);
        }
      }

      return true;
    }
    /**
     * @static
     *
     * @param $required_class
     *
     * @return bool|string
     */
    public static function loadFromCache($required_class)
    {
      $result = false;
      if (isset(static::$loaded[$required_class])) {
        try {
          $result = static::includeFile(static::$loaded[$required_class], $required_class);
        }
        catch (Autoload_Exception $e) {
          Event::register_shutdown(__CLASS__);
        }
        if ($result && isset(static::$global_classes[$required_class])) {
          class_alias(static::$global_classes[$required_class] . '\\' . $required_class, '\\' . $required_class);
        }
      }

      return $result;
    }
    /**
     * @static
     *
     * @param $requested_class
     *
     * @internal param $required_class
     * @internal param $required_class
     * @return bool|string
     */
    public static function load($requested_class)
    {
      $classname = ltrim($requested_class, '\\');
      $namespace = '';
      if ($lastNsPos = strripos($classname, '\\')) {
        $namespace = substr($classname, 0, $lastNsPos);
        $classname = substr($classname, $lastNsPos + 1);
      }
      $alias      = false;
      $class_file = str_replace('_', DS, $classname);
      if (isset(static::$global_classes[$classname]) && (!$namespace || static::$global_classes[$classname] == $namespace)) {
        $namespace = static::$global_classes[$classname];
        $alias     = true;
      }
      if ($namespace) {
        $namespacepath = str_replace(['\\', 'ADV'], [DS, 'classes'], $namespace);
        $dir           = DOCROOT . strtolower($namespacepath);
      } elseif (isset(static::$classes[$classname])) {
        $dir = static::$classes[$classname] . DS . $class_file;
      } else {
        $dir = APPPATH . strtolower($class_file);
      }
      $class_file = strtolower($class_file);
      $paths[]    = $dir . '.php';
      $paths[]    = $dir . DS . $class_file . '.php';
      $paths[]    = $dir . DS . $class_file . DS . $class_file . '.php';
      $paths[]    = $dir . DS . 'classes' . DS . $class_file . '.php';
      $result     = static::trypath($paths, $requested_class);
      if ($result && $alias) {
        $fullclass                  = static::$global_classes[$classname] . '\\' . $classname;
        static::$loaded[$fullclass] = static::$loaded[$requested_class];
        static::$loaded[$classname] = static::$loaded[$requested_class];
        class_alias($fullclass, $classname);
      }

      return $result;
    }
    /**
     * @static

     */
    public static function _shutdown()
    {
      Cache::set('autoload', array(
                                  'classes'        => static::$classes,
                                  'global_classes' => static::$global_classes,
                                  'paths'          => static::$loaded
                             ));
    }
  }

  Autoloader::i();
