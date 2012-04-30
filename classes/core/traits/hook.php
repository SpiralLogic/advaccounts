<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core.traits
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Traits;
  /**

   */
  trait Hook {

    /** @var \Hook $hooks */
    protected static $hooks = NULL;
    /**
     * @static
     *
     * @param       $hook
     * @param       $object
     * @param       $function
     * @param array $arguments
     */
    public static function registerHook($hook, $object, $function, $arguments = array()) {
      if (static::$hooks === NULL) {
        static::$hooks = new \ADV\Core\Hook();
      }
      $callback = $object . '::' . $function;

      static::$hooks->add($hook, $callback, $arguments);
    }
    /**
     * @static
     *
     * @param $hook
     */
    public static function fireHooks($hook) {
      if (static::$hooks)  {
      static::$hooks->fire($hook);}
    }
  }