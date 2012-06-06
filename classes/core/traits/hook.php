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
  trait Hook
  {
    /** @var \Hook $hooks */
    protected static $hooks = null;
    /**
     * @static
     *
     * @param       $hook
     * @param       $object
     * @param       $function
     * @param array $arguments
     * @return bool
     */
    public static function registerHook($hook, $object, $function = null, $arguments = array())
    {
      if (static::$hooks === null) {
        static::$hooks = new \ADV\Core\Hook();
      }
      $callback = $object;
      if ($function) {
        $callback = (is_object($object)) ? [$object, $function] : $object . '::' . $function;
      } elseif (!is_callable($callback)) {
        return \Event::error('Hook is not callable!');
      }
      static::$hooks->add($hook, $callback, $arguments);
    }
    /**
     * @static
     *
     * @param $hook
     */
    public static function fireHooks($hook)
    {
      if (static::$hooks) {
        static::$hooks->fire($hook);
      }
    }
  }
