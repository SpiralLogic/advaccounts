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
     * @static
     *
     * @param        $var
     * @param        $value
     * @param string $group
     *
     * @return mixed
     */
    public static function set($var, $value, $group = 'config') {
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
    public static function get($var, $default = FALSE) {
      $i = static::i();
      if (!strstr($var, '.')) {
        $var = 'config.' . $var;
      }
      $group_array = explode('.', $var);
      $var         = array_pop($group_array);
      $group       = implode('.', $group_array);
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
     */
    public static function remove($var, $group = 'config') {

      if (array_key_exists($var, static::i()->_vars[$group])) {
        unset(static::i()->_vars[$group][$var]);
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
    public static function get_all($group = 'config', $default = array()) {

      if (!isset(static::i()->_vars[$group]) && static::i()->load($group) === FALSE) {
        return $default;
      }
      return static::i()->_vars[$group];
    }
    /**
     * @static

     */
    public static function removeAll() {
      Cache::delete('config');
    }
    /**
     * @static

     */
    public static function reset() {
      static::removeAll();
      static::i()->load();
    }

    public static function _shutdown() {
      Cache::set('config', static::i()->_vars);
    }
    /**

     */
    protected function __construct() {
      if (isset($_GET['reload_config'])) {
        Cache::delete('config');
        header('Location: /');
      }
      elseif ($this->_vars === NULL) {
        $this->_vars = Cache::get('config');
      }
      if (!$this->_vars) {
        $this->load();
      }
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
        $file       = DOCROOT . "config" . $group_path . DS . $group_file;
      }
      else {
        $file       = DOCROOT . "config" . DS . $group . '.php';
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
  }
