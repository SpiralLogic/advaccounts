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
    protected $Cachr = null;
    /**
     * @var array
     */
    public $loaded = array();
    /** @var Cache */
    public $Cache;
    /**

     */
    public function __construct()
    {
      spl_autoload_register(array($this, 'load'), true);
    }
    /**
     * @param Cache $cache
     */
    public function registerCache(Cache $cache)
    {
      $this->Cache   = $cache;
      $cachedClasses = $cache->get('autoload', array());
      if ($cachedClasses) {
        $this->global_classes = $cachedClasses['global_classes'];
        $this->classes        = $cachedClasses['classes'];
        $this->loaded         = $cachedClasses['paths'];
      } else {
        $core = include(DOCROOT . 'config' . DS . 'core.php');
        $this->importNamespaces((array) $core);
        $vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
        $this->add_classes((array) $vendor, VENDORPATH);
      }
      spl_autoload_register(array($this, 'loadFromCache'), true, true);
    }
    /**
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
     * @param $namespace
     * @param $classes
     */
    protected function importNamespace($namespace, $classes)
    {
      $this->global_classes = array_merge($this->global_classes, array_fill_keys($classes, $namespace));
    }
    /**
     * @param array $namespaces
     */
    protected function importNamespaces(array $namespaces)
    {
      foreach ($namespaces as $namespace => $classes) {
        $this->importNamespace($namespace, $classes);
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
        $fullclass                = $this->global_classes[$classname] . '\\' . $classname;
        $this->loaded[$fullclass] = $this->loaded[$requested_class];
        $this->loaded[$classname] = $this->loaded[$requested_class];
        class_alias($fullclass, $classname);
      }
      return $result;
    }
    public function _shutdown()
    {
      if ($this->Cache) {
        $this->Cache->set('autoload', array(
                                           'classes'        => $this->classes, //
                                           'global_classes' => $this->global_classes, //
                                           'paths'          => $this->loaded
                                      ));
      }
    }
  }

  return new Autoloader();
