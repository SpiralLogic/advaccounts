<?php  /**
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
   *
   */
  class HookException extends \Exception {
  }

  /**

   */
  class Hook {
    protected $hooks = array();
    /**
     * @param                              $name
     * @param callable                     $callback
     * @param array                        $arguments
     * @return bool
     */
    public function add($name, $callback, $arguments = array()) {
      $callback_id = (is_string($callback)) ? $callback : count($this->hooks);
      if (!isset($this->hooks[$name][$callback_id])) {
        return $this->hooks[$name][$callback_id] = [$callback, (array) $arguments];
      }
      return FALSE;
    }
    /**
     * @param $name
     *
     * @return array
     */
    public function getCallbacks($name) {
      return isset($this->hooks[$name]) ? $this->hooks[$name] : array();
    }
    /**
     * @param $name
     */
    public function fire($name) {
      foreach ($this->getCallbacks($name) as $callback) {
        if (!is_callable($callback[0])) {
          continue;
        }
        call_user_func_array($callback[0], $callback[1]);
      }
    }
  }
