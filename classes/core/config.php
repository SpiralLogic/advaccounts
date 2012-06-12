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

  /**
   * @method get($var, $default = false)
   * @method removeAll()
   * @method Config i();
   */
  class Config
  {
    use Traits\StaticAccess;

    /***
     * @var array|null
     */
    protected $_vars = null;
    /**
     * @static
     *
     * @param        $var
     * @param        $value
     * @param string $group
     *
     * @return mixed
     */
    public function _set($var, $value, $group = 'config')
    {
      $this->_vars[$group][$var] = $value;
      return $value;
    }
    /***
     * @static
     *
     * @param string $var
     * @param bool   $default
     *
     * @internal param null $array_key
     * @return Array|mixed
     */
    public function _get($var, $default = false)
    {
      if (!strstr($var, '.')) {
        $var = 'config.' . $var;
      }
      $group_array = explode('.', $var);
      $var         = array_pop($group_array);
      $group       = implode('.', $group_array);
      (isset($this->_vars[$group], $this->_vars[$group][$var])) or $this->load($group_array);
      if (!isset($this->_vars[$group][$var])) {
        return $default;
      }
      return $this->_vars[$group][$var];
    }
    /**
     * @static
     *
     * @param        $var
     * @param string $group
     */
    public function _remove($var, $group = 'config')
    {
      if (array_key_exists($var, $this->_vars[$group])) {
        unset($this->_vars[$group][$var]);
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
    public function _get_all($group = 'config', $default = array())
    {
      if (!isset($this->_vars[$group]) && $this->load($group) === false) {
        return $default;
      }
      return $this->_vars[$group];
    }
    /**
     * @static

     */
    public function _removeAll()
    {
      Cache::delete('config');
    }
    /**
     * @static

     */
    public function _reset()
    {
      $this->_removeAll();
      $this->load();
    }
    public static function _shutdown()
    {
      Cache::set('config', static::i()->_vars);
    }
    /**

     */
    public function __construct(Cachable $cache = null)
    {
      if (isset($_GET['reload_config'])) {
        Cache::delete('config');
        header('Location: /');
      } elseif ($this->_vars === null) {
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
    protected function load($group = 'config')
    {
      if (is_array($group)) {
        $group_name = implode('.', $group);
        $group_file = array_pop($group) . '.php';
        $group_path = implode(DS, $group);
        $file       = DOCROOT . "config" . $group_path . DS . $group_file;
      } else {
        $file       = DOCROOT . "config" . DS . $group . '.php';
        $group_name = $group;
      }
      if ($this->_vars && array_key_exists($group_name, $this->_vars)) {
        return true;
      }
      if (!file_exists($file)) {
        throw new \RuntimeException("There is no file for config: " . $file);
      }
      /** @noinspection PhpIncludeInspection */
      $this->_vars[$group_name] = include($file);
      Event::register_shutdown(__CLASS__);
      return true;
    }
  }
