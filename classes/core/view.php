<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 21/06/12
   * Time: 10:15 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core;
  class View implements \ArrayAccess
  {
    protected $_viewdata = [];
    protected $_template = null;
    /**
     * @param $template
     */
    public function __construct($template)
    {
      $template = VIEWPATH . $template . '.php';
      if (!file_exists($template)) {
        throw new \InvalidArgumentException("There is no view $template !");
      }
      $this->_template = $template;
    }
    public function render()
    {
      if (!$this->_template) {
        throw new \RuntimeException("There is nothing to render!");
      }
      extract($this->_viewdata);
      include($this->_template);
    }
    public function set($offset, $value, $escape = false)
    {
      $value                    = $escape ? e($value) : $value;
      $this->_viewdata[$offset] = $value;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
      return (array_key_exists($offset, $this->_viewdata));
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
      if (!array_key_exists($offset, $this->_viewdata)) {
        return null;
      }
      return $this->_viewdata[$offset];
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
      $this->set($offset, $value, true);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
      if ($this->offsetExists($offset)) {
        unset($this->_viewdata[$offset]);
      }
    }
  }
