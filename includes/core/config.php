<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
  /**

   */
  class Config_Exception extends \Exception {
  }

  /***

   */
  class Config {
    /***
     * @var array|null
     */
    static $_vars = NULL;
    /**
     * @var bool
     */
    static protected $i = FALSE;
    /**
     * @static
     * @return mixed
     */
    static public function i() {
      if (static::$i === TRUE) {
        return;
      }
      if (static::$_vars === NULL) {
        static::$_vars = Cache::get('config');
      }
      if (static::$_vars === FALSE || isset($_GET['reload_config'])) {
        static::$_vars = array();
        static::load();
        Event::register_shutdown(__CLASS__);
      }
      static::$i = TRUE;
      static::js();
    }
    /**
     * @static
     *
     * @param string $group
     *
     * @throws \ADV\Core\Config_Exception
     * @return mixed
     */
    static protected function load($group = 'config') {
      if (is_array($group)) {
        $group_name = implode('.', $group);
        $group_file = array_pop($group) . '.php';
        $group_path = implode(DS, $group);
        $file = DOCROOT . "config" . $group_path . DS . $group_file;
      }
      else {
        $file = DOCROOT . "config" . DS . $group . '.php';
        $group_name = $group;
      }
      if (static::$_vars && array_key_exists($group_name, static::$_vars)) {
        return true;
      }
      if (!file_exists($file)) {
        throw new Config_Exception("There is no file for config: " . $file);
      }
      /** @noinspection PhpIncludeInspection */
      static::$_vars[$group_name] = include($file);
      Event::register_shutdown(__CLASS__);
    }
    /**
     * @static
     *
     * @param        $var
     * @param        $value
     * @param string $group
     *
     * @return mixed
     */
    static public function set($var, $value, $group = 'config') {
      static::$_vars[$group][$var] = $value;
      return $value;
    }
    /***
     * @static
     *
     * @param    string  $var
     * @param bool       $default
     *
     * @internal param null $array_key
     * @return Array|mixed
     */
    static public function get($var, $default = FALSE) {
      static::i();
      if (!strstr($var, '.')) {
        $var = 'config.' . $var;
      }
      $group_array = explode('.', $var);
      $var = array_pop($group_array);
      $group = implode('.', $group_array);
      (isset(static::$_vars[$group], static::$_vars[$group][$var])) or static::load($group_array);
      if (!isset(static::$_vars[$group][$var])) {
        return $default;
      }
      return static::$_vars[$group][$var];
    }
    /**
     * @static
     *
     * @param        $var
     * @param string $group
     */
    static public function remove($var, $group = 'config') {
      if (array_key_exists($var, static::$_vars[$group])) {
        unset(static::$_vars[$group][$var]);
      }
    }
    /**
     * @static

     */
    static public function _shutdown() {
      Cache::set('config', static::$_vars);
    }
    /**
     * @static
     *
     * @param string $group
     *
     * @param array  $default
     *
     * @return mixed
     */
    static public function get_all($group = 'config',$default=array()) {
      static::i();
      if (!isset(static::$_vars[$group]) &&  static::load($group)===FALSE){
        return $default;
      };
      return static::$_vars[$group];
    }
    /**
     * @static

     */
    static protected function js() {
      \JS::headerFile(static::get('assets.header'));
      \JS::footerFile(static::get('assets.footer'));
    }
  }
