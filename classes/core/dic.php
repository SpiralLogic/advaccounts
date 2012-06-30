<?php

  namespace ADV\Core;
  /**

   */
  class DIC implements \ArrayAccess
  {
    protected $_objects = array();
    protected $_callbacks = array();
    /**
     * @param       $name
     * @param array $arguments
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __call($name, $arguments = array())
    {
      // Parse function name
      preg_match_all('/_?([A-Z][a-z0-9]*|[a-z0-9]+)/', $name, $parts);
      $parts = $parts[1];
      // Determine method
      $method = array_shift($parts);
      if ('new' == $method) {
        $method = 'fresh';
      }
      // Determine object key
      $key = strtolower(implode('_', $parts));
      array_unshift($arguments, $key);
      // Call method if exists
      if (method_exists($this, $method)) {
        return call_user_func_array(array($this, $method), $arguments);
      }
      // Throw exception on miss
      throw new \InvalidArgumentException(sprintf('Methood "%s" does not exist.', $method));
    }
    /**
     * @param         $name
     * @param Closure $callable
     */
    public function set($name, \Closure $callable)
    {
      $this->_callbacks[$name] = $callable;
    }
    /**
     * Sets a parameter or an object.
     * Objects must be defined as Closures.
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same a name as an existing parameter would break your container).
     *
     * @param string $name   The unique identifier for the parameter or object
     * @param mixed  $value  The value of the parameter or a closure to defined an object
     */
    function offsetSet($name, $value)
    {
      $this->set($name, $value);
    }
    /**
     * @param $name
     * @param $param
     */
    public function setParam($name, $param)
    {
      $this->set($name, function() use ($param)
      {
        return $param;
      });
    }
    /**
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
      return isset($this->_callbacks[$name]);
    }
    /**
     * Checks if a parameter or an object is set.
     *
     * @param  string $name The unique identifier for the parameter or object
     *
     * @return Boolean
     */
    function offsetExists($name)
    {
      return $this->has($name);
    }
    /**
     * @param $name
     *
     * @return mixed
     */
    public function get($name)
    {
      // Return object if it's already instantiated
      if (isset($this->_objects[$name])) {
        $args = func_get_args();
        array_shift($args);
        $key = $this->_keyForArguments($args);
        if ('_no_arguments' == $key && !isset($this->_objects[$name][$key]) && !empty($this->_objects[$name])) {
          $key = key($this->_objects[$name]);
        }
        if (isset($this->_objects[$name][$key])) {
          return $this->_objects[$name][$key];
        }
      }
      // Otherwise create a new one
      return call_user_func_array(array($this, 'fresh'), func_get_args());
    }
    /**
     * Gets a parameter or an object.
     *
     * @param  string $name The unique identifier for the parameter or object
     *
     * @return mixed  The value of the parameter or an object
     * @throws \InvalidArgumentException if the identifier is not defined
     */
    function offsetGet($name)
    {
      return $this->get($name);
    }
    /**
     * @param $name
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function fresh($name)
    {
      if (!isset($this->_callbacks[$name])) {
        throw new \InvalidArgumentException(sprintf('Callback for "%s" does not exist.', $name));
      }
      $arguments                   = func_get_args();
      $arguments[0]                = $this;
      $key                         = $this->_keyForArguments($arguments);
      $this->_objects[$name][$key] = call_user_func_array($this->_callbacks[$name], $arguments);
      return $this->_objects[$name][$key];
    }
    /**
     * @param $name
     *
     * @return bool
     */
    public function delete($name)
    {
      // TODO: Should this also delete the callback?
      if (isset($this->_objects[$name])) {
        unset($this->_objects[$name]);
        return true;
      }
      return false;
    }
    /**
     * Unsets a parameter or an object.
     *
     * @param  string $name The unique identifier for the parameter or object
     */
    function offsetUnset($name)
    {
      $this->delete($name);
    }
    /**
     * @param array $arguments
     *
     * @return string
     */
    protected function _keyForArguments(Array $arguments)
    {
      if (count($arguments) && $this === $arguments[0]) {
        array_shift($arguments);
      }
      if (0 == count($arguments)) {
        return '_no_arguments';
      }
      return md5(serialize($arguments));
    }
  }

