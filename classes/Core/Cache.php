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

  use ADV\Core\Cache\Cachable;

  /**
   * @method static mixed _get($key, $default = false)
   * @method static _set($key, $value, $expires = 86400)
   * @method static _defineConstants($name, $constants)
   * @method static _delete($key)
   * @method static Cache i()
   */
  class Cache {
    use \ADV\Core\Traits\StaticAccess2;

    /** @var Cachable **/
    protected $driver = false;
    /**
     * @param Cachable $driver
     *
     * @internal param $Cachable $
     */
    public function __construct(Cachable $driver) {
      $this->driver = $driver;
      $this->driver->init();
      if (isset($_GET['reload_cache'])) {
        $this->driver->flush();
        header('Location: ' . ROOT_URL . '?cache_reloaded');
      }
    }
    /**
     * @static
     *
     * @param     $key
     * @param     $value
     * @param int $expires
     *
     * @return mixed
     */
    public function set($key, $value, $expires = 86400) {
      return $this->driver->set($key, $value, $expires);
    }
    /**
     * @static
     *
     * @param $key
     *
     * @return void
     */
    public function delete($key) {
      $this->driver->delete($key);
    }
    /**
     * @static
     *
     * @param       $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = false) {
      return $this->driver->get($key, $default);
    }
    /**
     * @param     $time
     * @param int $time
     */
    public function flush($time) {
      $this->driver->flush($time);
    }
    /**
     * @param                $name
     * @param \Closure|Array $constants
     *
     * @return Cache|bool
     * @return \ADV\Core\Cache|bool
     */
    public function defineConstants($name, $constants) {
      $loader = $this->driver->getLoadConstantsFunction();
      if (is_callable($loader)) {
        $loader = $loader($name);
      }
      if ($loader === true) {
        return true;
      }
      if (is_callable($constants)) {
        $constants = (array) call_user_func($constants);
      }
      $definer = $this->driver->getDefineConstantsFunction();
      if (is_callable($definer)) {
        $definer = $definer($name, $constants);
      }
      if ($definer === true) {
        return true;
      }
      foreach ($constants as $constant => $value) {
        define($constant, $value);
      }
      return $this;
    }
  }
