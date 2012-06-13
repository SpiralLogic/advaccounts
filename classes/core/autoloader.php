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
    protected $time = 0;
    /**
     * @var array
     */
    protected $classes = array();
    /**
     * @var array
     */
    protected $global_classes = array();
    /**
     * @var array
     */
    public $loaded = array();
    static $i;
    /**
     * @static

     */
    public static function i()
    {
      class_alias(__CLASS__, 'Autoloader');
      static::$i=$autoloader=new self;
      spl_autoload_register(array($autoloader,'load'), true);

    }
    /**

     */
    public function __construct() {

    }

public static function loadCache() {
  $cachedClasses = \ADV\Core\Cache::get('autoload', array());
  if ($cachedClasses) {
    static::$i->global_classes = $cachedClasses['global_classes'];
    static::$i->classes        = $cachedClasses['classes'];
    static::$i->loaded         = $cachedClasses['paths'];
  } else {
    $core = include(DOCROOT . 'config' . DS . 'core.php');
    static::$i->import_namespaces((array) $core);
    $vendor= include(DOCROOT . 'config' . DS . 'vendor.php');
    static::$i->add_classes((array) $vendor, VENDORPATH);
  }
  spl_autoload_register(array(static::$i,'loadFromCache'), true, true);

}
    /**
     * @static
     *
     * @param array $classes
     * @param       $type
     */
    protected function add_classes(array $classes, $type)
    {
      foreach ($classes as $dir => $class) {
        if (!is_string($dir)) {
          $dir = '';
        }
        $this->classes[$class] = $type . $dir;
      }
    }
    /**
     * @static
     *
     * @param $namespace
     * @param $classes
     */
    protected function import_namespace($namespace, $classes)
    {
      $this->global_classes = array_merge($this->global_classes, array_fill_keys($classes, $namespace));
    }
    /**
     * @static
     *
     * @param array $namespaces
     */
    protected function import_namespaces(array $namespaces)
    {
      foreach ($namespaces as $namespace => $classes) {
        $this->import_namespace($namespace, $classes);
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
    protected function tryPath($paths, $required_class)
    {
      $paths = (array) $paths;
      while ($path = array_shift($paths)) {
        $filepath = realpath($path);
        if (is_readable($filepath)) {
          return $this->includeFile($filepath, $required_class);
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
    protected function includeFile($filepath, $required_class)
    {
      if (empty($filepath)) {
        throw new Autoload_Exception('File for class ' . $required_class . ' cannot be found!');
      }
      if (!include_once($filepath)) {
        throw new Autoload_Exception('File for class ' . $required_class . ' cannot be	loaded from : ' . $filepath);
      }
      if (!isset($this->loaded[$required_class])) {
        $this->loaded[$required_class] = $filepath;
        if (is_callable('Event::register_shutdown')) {
          Event::register_shutdown($this);
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
    public function loadFromCache($required_class)
    {
      $result = false;
      if (isset($this->loaded[$required_class])) {
        try {
          $result = $this->includeFile($this->loaded[$required_class], $required_class);
        }
        catch (Autoload_Exception $e) {
          Event::register_shutdown($this);
        }
        if ($result && isset($this->global_classes[$required_class])) {
          class_alias($this->global_classes[$required_class] . '\\' . $required_class, '\\' . $required_class);
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
    public function load($requested_class)
    {
      $classname = ltrim($requested_class, '\\');
      $namespace = '';
      if ($lastNsPos = strripos($classname, '\\')) {
        $namespace = substr($classname, 0, $lastNsPos);
        $classname = substr($classname, $lastNsPos + 1);
      }
      $alias      = false;
      $class_file = str_replace('_', DS, $classname);
      if (isset($this->global_classes[$classname]) && (!$namespace || $this->global_classes[$classname] == $namespace)) {
        $namespace = $this->global_classes[$classname];
        $alias     = true;
      }
      if ($namespace) {
        $namespacepath = str_replace(['\\', 'ADV'], [DS, 'classes'], $namespace);
        $dir           = DOCROOT . strtolower($namespacepath);
      } elseif (isset($this->classes[$classname])) {
        $dir = $this->classes[$classname] . DS . $class_file;
      } else {
        $dir = APPPATH . strtolower($class_file);
      }
      $class_file = strtolower($class_file);
      $paths[]    = $dir . '.php';
      $paths[]    = $dir . DS . $class_file . '.php';
      $paths[]    = $dir . DS . $class_file . DS . $class_file . '.php';
      $paths[]    = $dir . DS . 'classes' . DS . $class_file . '.php';
      $result     = $this->trypath($paths, $requested_class);
      if ($result && $alias) {
        $fullclass                  = $this->global_classes[$classname] . '\\' . $classname;
        $this->loaded[$fullclass] = $this->loaded[$requested_class];
        $this->loaded[$classname] = $this->loaded[$requested_class];
        class_alias($fullclass, $classname);
      }

      return $result;
    }
    /**
     * @static

     */
    public function _shutdown()
    {
      Cache::set('autoload', array(
                                  'classes'        => $this->classes,
                                  'global_classes' => $this->global_classes,
                                  'paths'          => $this->loaded
                             ));
    }
  }

  Autoloader::i();
