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
  class Load_Exception extends \Exception
  {
  }
  /**

   */
  class Loader
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
    /** @var Cache */
    protected $Cache = null;
    /**
     * @var array
     */
    public $loaded = array();
    /**

     */
    public function __construct() {
      $core = include(DOCROOT . 'config' . DS . 'core.php');
      $this->importNamespaces((array) $core);
      spl_autoload_register(array($this, 'load'), true);
    }
    /**
     * @param Cache $cache
     */
    public function registerCache(Cache $cache) {
      $this->Cache   = $cache;
      $cachedClasses = $cache->get('Loader', array());
      if ($cachedClasses) {
        $this->classes = $cachedClasses['classes'];
        $this->loaded  = $cachedClasses['paths'];
      } else {
        $vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
        $this->addClasses((array) $vendor, VENDORPATH);
      }
    }
    /**
     * @param array $classes
     * @param       $type
     */
    protected function addClasses(array $classes, $type) {
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
    protected function importNamespace($namespace, $classes) {
      $this->global_classes = array_merge($this->global_classes, array_fill_keys($classes, $namespace));
    }
    /**
     * @param array $namespaces
     */
    protected function importNamespaces(array $namespaces) {
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
    protected function tryPath($paths, $required_class, $classname, $global = false) {
      $paths = (array) $paths;
      while ($path = array_shift($paths)) {
        $filepath = realpath($path);
        if (is_readable($filepath)) {
          return $this->includeFile($filepath, $required_class, $classname, $global);
        }
      }
      return false;
    }
    /**
     * @param $filepath
     * @param $required_class
     *
     * @throws Load_Exception
     * @internal param $class
     * @return bool
     */
    protected function includeFile($filepath, $requested_class, $classname, $global) {
      if (empty($filepath)) {
        throw new Load_Exception('File for class ' . $requested_class . ' cannot be found!');
      }
      if (!include($filepath)) {
        throw new Load_Exception('File for class ' . $requested_class . ' cannot be	loaded from : ' . $filepath);
      }
      if ($global) {
        $fullclass = $this->global_classes[$classname] . '\\' . $classname;
        class_alias($fullclass, $classname);
      }
      return true;
    }
    /**
     * @param $requested_class
     *
     * @internal param $required_class
     * @internal param $required_class
     * @return bool|string
     */
    public function load($requested_class) {
      $classname = ltrim($requested_class, '\\');
      $namespace = $global = '';
      if ($lastNsPos = strripos($classname, '\\')) {
        $namespace = substr($classname, 0, $lastNsPos);
        $classname = substr($classname, $lastNsPos + 1);
      }
      $class_file = str_replace('_', DS, $classname);
      if (isset($this->global_classes[$classname]) && (!$namespace || $this->global_classes[$classname] == $namespace)) {
        $namespace = $this->global_classes[$classname];
        $global    = true;
      }
      if ($namespace) {
        $namespacepath = str_replace(['ADV\\', '\\'], ['', DS], $namespace);
        $dir           = DOCROOT . 'classes' . DS . $namespacepath . DS;
      } elseif (isset($this->classes[$classname])) {
        $dir = $this->classes[$classname];
      } else {
        $dir = APPPATH;
      }
      $paths  = [$dir . $class_file . '.php', $dir . $class_file . DS . $class_file . '.php'];
      $result = $this->trypath($paths, $requested_class, $classname, $global);
      return $result;
    }
    public function _shutdown() {
      if ($this->Cache) {
        $this->Cache->set('Loader', array(
                                         'classes'        => $this->classes, //
                                         'paths'          => $this->loaded
                                    ));
      }
    }
  }

  return new Loader();
