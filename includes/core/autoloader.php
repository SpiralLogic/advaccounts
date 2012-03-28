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
      spl_autoload_register('Autoloader::loadCore', TRUE);
      spl_autoload_register('Autoloader::loadVendor', TRUE, TRUE);
      spl_autoload_register('Autoloader::loadApp', TRUE, TRUE);
      spl_autoload_register('Autoloader::loadInterface', TRUE, TRUE);
      spl_autoload_register('Autoloader::loadModule', TRUE, TRUE);
      spl_autoload_register('Autoloader::loadFromCache', TRUE, TRUE);
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
        //	Event::register_shutdown(__CLASS__);
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
     * @return bool|string
     */
    static public function loadTrait($classname) {
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

      if (substr($classname, -5) == 'Trait') {
        return static::makePaths(substr($classname, 0, -5), COREPATH . 'traits' . DS);
      }

      return static::makePaths($classname, COREPATH);
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
    }
  }

  Autoloader::i();
