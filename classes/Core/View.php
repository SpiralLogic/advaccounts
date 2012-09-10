<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 21/06/12
   * Time: 10:15 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core;

  use ADV\Core\Cache\Cache;

  /**

   */
  class View implements \ArrayAccess
  {
    use \ADV\Core\Traits\HTML;

    protected $_viewdata = [];
    protected $_template = null;
    /** @var \ADV\Core\Cache\Cache */
    public static $Cache;
    protected static $count = 0;
    protected $context;
    protected $_js = [];
    /**
     * @param $template
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($template) {
      $this->_template = VIEWPATH . $template . '.tpl';
      if (!file_exists($this->_template)) {
        throw new \InvalidArgumentException("There is no view $template !");
      }
      $js = 'js' . DS . $template . '.js';
      if (file_exists(WEBROOT . $js)) {
        $this->_js[] = BASE_URL . $js;
      }
    }
    /**
     * @param bool $return
     *
     * @throws \RuntimeException
     * @throws \Exception
     * @return string
     */
    public function render($return = false) {
      $__contents = $this->getCompiled();
      //return var_dump($__contents);
      ob_start() and extract($this->_viewdata, EXTR_SKIP);
      // We'll include the view contents for parsing within a catcher
      // so we can avoid any WSOD errors. If an exception occurs we
      // will throw it out to the exception handler.
      try {
        $exception = eval('?>' . $__contents);
        if ($exception !== null) {
          ob_start();
          var_dump($__contents);
          $contents = ob_get_clean();
          Errors::handler(E_ERROR, 'template ' . $this->_template . " failed to render!<pre>" . $contents);
        }
      } catch (\Exception $e) {
        // If we caught an exception, we'll silently flush the output
        // buffer so that no partially rendered views get thrown out
        // to the client and confuse the user with junk.
        ob_get_clean();
        throw $e;
      }
      if ($return) {
        return ob_get_clean();
      }
      echo ob_get_clean();

      return true;
    }
    /**
     * @return mixed
     * @throws \RuntimeException
     */
    public function getCompiled() {
      if (!$this->_template) {
        throw new \RuntimeException("There is nothing to render!");
      }
      // The contents of each view file is cached in an array for the
      // request since partial views may be rendered inside of for
      // loops which could incur performance penalties.
      //    $__contents = null; // static::$Cache->get('template.' . $this->_template);
      $__contents = static::$Cache->get('template.' . $this->_template);
      if (!$__contents || !is_array($__contents)) {
        $__contents = file_get_contents($this->_template);
        while (strpos($__contents, '  ')) {
          $__contents = str_replace('  ', ' ', $__contents);
        }
        if ($this->context) {
          $__contents = $this->runContext($__contents);
        }
        $__contents = $this->compile($__contents);
        JS::_footerFile($this->_js);

        return $__contents;
      } else {
        Event::registerShutdown($this, 'checkCache', [$this->_template, $__contents[1]]);
        $this->checkCache($this->_template, $__contents[1]);
        JS::_footerFile($__contents[2]);
        $__contents = $__contents[0];

        return $__contents;
      }
    }
    public function addContext($context) {
      $context       = preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '_', $context);
      $this->context = $context;
    }
    public function runContext($contents) {
      $contents = preg_replace('/\$([^\.][a-zA-Z_0-9]+?)/', '\$' . $this->context . '.$1', $contents);

      return $contents;
    }
    /**
     * @param $template
     * @param $lastmodified
     */
    public function checkCache($template, $lastmodified) {
      if ($lastmodified < filemtime($template)) {
        static::$Cache->delete('template.' . $this->_template);
      }
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected static function compileEchos($value) {
      return preg_replace(
        '/\{\{([^!\.].*?)\}\}/',
        '<?php  echo $1; ?>',
        $value
      );
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected function compileMixins($value) {
      return preg_replace_callback(
        '/\{\{\>(.+?)\}\}/',
        function ($input) {
          $view = new View($input[1]);
          $view->addContext($input[1]);
          $this->_js = array_unique(array_merge($this->_js, $view->_js));

          return '<?php if ($' . $input[1] . '!==false): ?>' . $view->getCompiled() . '<?php endif; ?>';
        },
        $value
      );
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected static function compileDotNotation($value) {
      return preg_replace('/(\$[a-zA-Z_0-9]+?)\.([a-zA-Z_0-9-]+)/', '$1["$2"]', $value);
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected static function compileFunctions($value) {
      return preg_replace('/([^{])\{#(.+?)#\}([^}])/', '$1<?php $2; ?>$3', $value);
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return mixed
     */
    protected static function compileNothings($value) {
      $pattern = '/\{\{(\$.+?)\?\}\}(.+?)\{\{\/\1\?\}\}/s';

      return preg_replace($pattern, '<?php if(isset($1) && $1): ?>$2<?php endif; ?>', $value);
    }
    /**
     * Rewrites Blade structure openings into PHP structure openings.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected static function compileStructureOpenings($value) {

      $pattern = '/\{\{#(if|elseif|foreach|for|while)(.*?)\}\}/';

      return preg_replace($pattern, '<?php $1($2): ?>', $value);
    }
    /**
     * Rewrites Blade structure closings into PHP structure closings.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected static function compileStructureClosings($value) {

      $pattern = '/\{\{\/(if|foreach|for|while)\}\}/';

      return preg_replace($pattern, '<?php end$1; ?>', $value);
    }
    /**
     * Rewrites Blade else statements into PHP else statements.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected static function compileElse($value) {
      return preg_replace('/\{\{#(else)\}\}/', '<?php $1: ?>', $value);
    }
    /**
     * Rewrites Blade structure openings into PHP structure openings.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function compileHashes($value) {
      $pattern = '/\{\{#([^?]+?)\}\}(.*?)\{\{\/\1}\}/s';
      $return  = preg_replace_callback(
        $pattern,
        function ($input) {
          $var      = ltrim($input[1], '$');
          $contents = $input[2];
          $tempvar  = uniqid();
          $return   = '<?php if (isset($' . $var . ') && (is_array($' . $var . ') || $' . $var . ' instanceof \Traversable )): foreach($' . $var . ' as $_' . $tempvar . '_name =>
           $_' . $tempvar . '_val): ?>';
          $contents = $this->compile($contents, true);
          $contents = str_replace(['{{!}}', '{{.}}'], ['{{$_' . $tempvar . '_name}}', '{{$_' . $tempvar . '_val}}'], $contents);
          $return .= str_replace('$.', '$_' . $tempvar . '_val.', $contents);
          //$implicit = $this->compileContext($contents, '_' . $var);
          $return .= '<?php endforeach; endif; ?>';

          //        }
          return $return;
        },
        $value
      );

      return $return;
    }
    /**
     * @param      $__contents
     * @param null $context
     *
     * @return mixed
     */
    private function compile($__contents, $context = null) {
      $__contents = $this->compileFunctions($__contents);
      $__contents = $this->compileNothings($__contents);
      $__contents = $this->compileStructureOpenings($__contents);
      $__contents = $this->compileElse($__contents);
      $__contents = $this->compileStructureClosings($__contents);
      $__contents = $this->compileHashes($__contents);
      $__contents = $this->compileMixins($__contents);
      $__contents = $this->compileEchos($__contents, $context);
      $__contents = $this->compileDotNotation($__contents);
      static::$Cache->set('template.' . $this->_template, [$__contents, filemtime($this->_template), $this->_js]);

      return $__contents;
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
      $offset                   = preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '_', $offset);
      $this->_viewdata[$offset] = $value;

      return $this;
    }
    public function getALL() {
      return $this->_viewdata;
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

  View::$Cache = Cache::i();

