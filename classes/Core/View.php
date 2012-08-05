<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 21/06/12
   * Time: 10:15 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core;
  /**

   */
  class View implements \ArrayAccess
  {
    protected $_viewdata = [];
    protected $_template = null;
    /** @var Cache */
    static $Cache;
    /**
     * @param $template
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($template) {
      $template = VIEWPATH . $template . '.tpl';
      if (!file_exists($template)) {
        throw new \InvalidArgumentException("There is no view $template !");
      }
      $this->_template = $template;
    }
    /**
     * @param bool $return
     *
     * @throws \RuntimeException
     * @throws \Exception
     * @return string
     */
    public function render($return = false) {
      if (!$this->_template) {
        throw new \RuntimeException("There is nothing to render!");
      }
      // The contents of each view file is cached in an array for the
      // request since partial views may be rendered inside of for
      // loops which could incur performance penalties.
      $__contents = null; // static::$Cache->_get('template.' . $this->_template);
      //  $__contents = static::$Cache->_get('template.' . $this->_template);
      if (!$__contents || !is_array($__contents)) {
        $__contents = file_get_contents($this->_template);
        $__contents = $this->compile($__contents);
        static::$Cache->_set('template.' . $this->_template, [$__contents, filemtime($this->_template)]);
      } else {
        Event::registerShutdown($this, 'checkCache', [$this->_template, $__contents[1]]);
        $this->checkCache($this->_template, $__contents[1]);
        $__contents = $__contents[0];
      }
//var_dump($this->_template,$__contents);
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
        Errors::log($_contents);
        throw $e;
      }
      if ($return) {
        return ob_get_clean();
      }
      echo ob_get_clean();
    }
    /**
     * @param $template
     * @param $lastmodified
     */
    public function checkCache($template, $lastmodified) {
      if ($lastmodified < filemtime($template)) {
        static::$Cache->_delete('template.' . $this->_template);
      }
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected function compile_echos($value) {
      $value = preg_replace_callback('/\{\{([^#\/].+?)\}\}/', function($input) {
        $var = trim($input[1]);
        if (defined($var)) {
          return '<?php echo ' . $var . '; ?>';
        }
        return '<?php echo isset($' . $var . ')?$' . $var . ':""; ?>';
      }, $value);
      return $value;
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected static function compile_dot_notation($value) {
      return preg_replace('/(\$[a-zA-Z_0-9]*?)\.([a-zA-Z_0-9-]+)/', '$1["$2"]', $value);
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected static function compile_functions($value) {
      return preg_replace('/\{\{\>(.+?)\}\}/', '<?php $1; ?>', $value);
    }
    /**
     * Rewrites Blade structure openings into PHP structure openings.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function complie_hashes($value) {
      $pattern = '/\{\{#([^?]+?)\}\}(.*?)\{\{\/\1}\}/s';
      $return  = preg_replace_callback($pattern, function($input) {
        $var      = $input[1];
        $contents = $input[2];
        if (strpos($contents, '{{.}}') !== false) {
          $return   = '<?php if (isset($' . $var . ') && is_array($' . $var . ') && isset($' . $var . '[0])): foreach($' . $var . ' as $_' . $var . '): ?>';
          $implicit = $this->compile_implicit($contents, $var);
          $return .= $this->compile($implicit);
          $return .= '<?php endforeach; endif; ?>';
        } else {
          $return   = '<?php if (isset($' . $var . ') && is_array($' . $var . ')): foreach($' . $var . ' as $_' . $var . '): ?>';
          $implicit = $this->compile_context($contents, '_'.$var);
          $return .= $this->compile($implicit);
          $return .= '<?php endforeach; endif; ?>';
        }
        return $return;
      }, $value);
      return $return;
    }
    /**
     * Rewrites Blade else statements into PHP else statements.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function compile_truthy($value) {
      $pattern = '/\{\{#([^?]+?)\?\}\}(.*?)\{\{\/\1\?\}\}/s';
      $value   = preg_replace_callback($pattern, function($input) {
        $var = $input[1];
        if (!defined($var)) {
          $var = '$' . $var;
        }
        $return = '<?php if (' . $var . '): ?>';
        $return .= $this->compile($input[2]);
        $return .= '<?php endif; ?>';
        return $return;
      }, $value);
      return $value;
    }
    /**
     * Rewrites Blade else statements into PHP else statements.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function compile_falsey($value) {
      $pattern = '/\{\{\^(.+?)\}\}(.*?)\{\{\/\1\}\}/s';
      $value   = preg_replace_callback($pattern, function($input) {
        $var = $input[1];
        if (!defined($var)) {
          $var = '$' . $var;
        }
        $return = '<?php if (!' . $var . '): ?>';
        $return .= $this->compile($input[2]);
        $return .= '<?php endif; ?>';
        return $return;
      }, $value);
      return $value;
    }
    /**
     * @param      $offset
     * @param      $value
     * @param bool $escape
     *
     * @return \ADV\Core\View
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
    private function compile($__contents, $context = null) {
      if ($context) {
        $__contents = $this->compile_context($__contents, $context);
      }
      $__contents = $this->compile_functions($__contents);
      $__contents = $this->complie_hashes($__contents);
      $__contents = $this->compile_truthy($__contents);
      $__contents = $this->compile_falsey($__contents);
      $__contents = $this->compile_echos($__contents, $context);
      $__contents = $this->compile_dot_notation($__contents);
      return $__contents;
    }
    /**
     * @param $__contents
     * @param $context
     *
     * @return mixed
     */
    private function compile_context($__contents, $context) {
      return preg_replace('/\{\{([#!^\/]?)([a-zA-Z_0-9]*?)/', '{{$1' . $context . '.$2', $__contents);
    }
    /**
     * @param $contents
     * @param $var
     *
     * @return mixed
     */
    private function compile_implicit($__contents, $var) {
      return preg_replace('/\{\{\.\}\}/', '<?php echo \$_' . $var . '; ?>', $__contents);
    }
  }

  View::$Cache = Cache::i();
