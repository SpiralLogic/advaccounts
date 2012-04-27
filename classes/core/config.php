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
  class Config_Exception extends \Exception {

  }

  /**

   */
  class Config {

  use Traits\Singleton;

    /***
     * @var array|null
     */
    protected $_vars = NULL;
    /**

     */
    protected function __construct() {
      if ($this->_vars === NULL) {
        $this->_vars = Cache::get('config');
      }
      if (isset($_GET['reload_config'])) {
        $this->removeAll();
      }
      if ($this->_vars === FALSE) {
        $this->load();
      }
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
      static::i()->_vars[$group][$var] = $value;
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
      $i = static::i();
      if (!strstr($var, '.')) {
        $var = 'config.' . $var;
      }
      $group_array = explode('.', $var);
      $var = array_pop($group_array);
      $group = implode('.', $group_array);
      (isset($i->_vars[$group], $i->_vars[$group][$var])) or $i->load($group_array);
      if (!isset($i->_vars[$group][$var])) {
        return $default;
      }
      return $i->_vars[$group][$var];
    }
    /**
     * @static
     *
     * @param        $var
     * @param string $group
     * @param string $group
     */
    static public function remove($var, $group = 'config') {
      $i = static::i();
      if (array_key_exists($var, $i->_vars[$group])) {
        unset($i->_vars[$group][$var]);
      }
    }
    /**
     * @static
     *
     * @param string $group
     * @param array  $default
     *
     * @return mixed
     * @return array
     */
    static public function get_all($group = 'config', $default = array()) {
      $i = static::i();
      if (!isset($i->_vars[$group]) && $i->load($group) === FALSE) {
        return $default;
      }
      ;
      return $i->_vars[$group];
    }
    /**
     * @static

     */
    static public function removeAll() {
      static::i()->_vars = array();
      Event::register_shutdown(__CLASS__);
    }
    /**
     * @static

     */
    static public function reset() {
      static::removeAll();
      static::i()->load();
    }
    /**
     * @static
     *
     * @param string $group
     *
     * @throws \ADV\Core\Config_Exception
     * @return mixed
     */
    protected function load($group = 'config') {
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
      if ($this->_vars && array_key_exists($group_name, $this->_vars)) {
        return TRUE;
      }
      if (!file_exists($file)) {
        throw new Config_Exception("There is no file for config: " . $file);
      }
      /** @noinspection PhpIncludeInspection */
      $this->_vars[$group_name] = include($file);
      Event::register_shutdown(__CLASS__);
    }
    /**
     * @static

     */
    static function js() {
    }
    /**
     * @static

     */
    static public function _shutdown() {
      Cache::set('config', static::i()->_vars);
    }
  }
