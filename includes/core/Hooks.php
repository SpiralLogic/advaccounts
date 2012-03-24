<?php  /**
 * PHP version 5.4
 * @category  PHP
 * @package   ADVAccounts
 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
 * @copyright 2010 - 2012
 * @link      http://www.advancedgroup.com.au
 **/
	class HookException extends \Exception {

	}


	class Hooks {

		protected $hooks = array();
		public function add($name, callable $callback, $arguments = array()) {
			$this->hooks[$name][] = [$callback, (array) $arguments];
		}
		public function getCallbacks($name) {
			return isset($this->hooks[$name]) ? $this->hooks[$name] : array();
		}
		public function fire($name) {
				foreach ($this->getCallbacks($name) as $callback) {
				if (!is_callable($callback[0])) {
					continue;
				}
				call_user_func_array($callback[0], $callback[1]);
			}
		}
	}
