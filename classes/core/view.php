<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 21/06/12
   * Time: 10:15 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core;
  class View implements \ArrayAccess {
    protected $_viewdata = [];
    protected $_template = null;
    /**
     * @param $template
     */
    public function __construct($template) {
      $template = VIEWPATH . $template . '.php';
      if (!file_exists($template)) {
        throw new \InvalidArgumentException("There is no view $template !");
      }
      $this->_template = $template;
    }
    /**
     * @param bool $return
     *
     * @return string
     * @throws \RuntimeException
     */
    public function render($return = false) {
      if (!$this->_template) {
        throw new \RuntimeException("There is nothing to render!");
      }
      // The contents of each view file is cached in an array for the
      // request since partial views may be rendered inside of for
      // loops which could incur performance penalties.
      $__contents = file_get_contents($this->_template);
      $__contents = $this->compile_structure_openings($__contents);
      $__contents = $this->compile_else($__contents);
      $__contents = $this->compile_structure_closings($__contents);
      $__contents = $this->compile_echos($__contents);
      ob_start() and extract($this->_viewdata, EXTR_SKIP);
      // We'll include the view contents for parsing within a catcher
      // so we can avoid any WSOD errors. If an exception occurs we
      // will throw it out to the exception handler.
      try {
        eval('?>' . $__contents);
      }
        // If we caught an exception, we'll silently flush the output
        // buffer so that no partially rendered views get thrown out
        // to the client and confuse the user with junk.
      catch (\Exception $e) {
        ob_get_clean();
        throw $e;
      }
      if ($return) {
        return ob_get_clean();
      }
      echo ob_get_clean();
    }
    protected static function compile_echos($value) {
      return preg_replace('/\{\{(.+?)\}\}/', '<?php echo $1; ?>', $value);
    }
    /**
     * Rewrites Blade structure openings into PHP structure openings.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected static function compile_structure_openings($value) {
      $pattern = '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))@/';
      return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
    }
    /**
     * Rewrites Blade structure closings into PHP structure closings.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected static function compile_structure_closings($value) {
      $pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)@(\s*)/';
      return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
    }
    /**
     * Rewrites Blade else statements into PHP else statements.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected static function compile_else($value) {
      return preg_replace('/(\s*)@(else)@(\s*)/', '$1<?php $2: ?>$3', $value);
    }
    /**
     * @param      $offset
     * @param      $value
     * @param bool $escape
     */
    public function set($offset, $value, $escape = false) {
      $value                    = $escape ? e($value) : $value;
      $this->_viewdata[$offset] = $value;
      return $this;
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
    public function offsetExists($offset) {
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
    public function offsetGet($offset) {
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
    public function offsetSet($offset, $value) {
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
    public function offsetUnset($offset) {
      if ($this->offsetExists($offset)) {
        unset($this->_viewdata[$offset]);
      }
    }
  }
