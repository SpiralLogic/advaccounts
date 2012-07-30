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
  /**
   * @method JS i
   * @method JS openWindow($width, $height)
   * @method JS setFocus($selector, $cached = false)
   * @method JS headerFile($file)
   * @method JS footerFile($file)
   * @method JS addLiveEvent($selector, $type, $action, $delegate = false, $cached = false)
   */
  class JS
  {
    use Traits\StaticAccess;

    /**
     * @var array
     */
    private $_beforeload = [];
    /**
     * @var array
     */
    private $_onload = [];
    /**
     * @var array
     */
    private $_onlive = [];
    /**
     * @var array
     */
    private $_toclean = [];
    /**
     * @var array
     */
    private $_headerFiles = [];
    /**
     * @var array
     */
    private $_footerFiles = [];
    /**
     * @var bool
     */
    private $_focus = false;
    /**
     * @var bool
     */
    public $outputted = false;
    /**
     * @var bool
     */
    private $_openWindow = false;
    public function __construct(Config $config = null) {
      $this->Config = $config ? : Config::i();
      $this->_footerFile($this->Config->_get('assets.footer'));
    }
    /**
     * @static
     *
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    public function _openWindow($width, $height) {
      if ($this->_openWindow || !$this->Config->_get('ui_windows_popups')) {
        return;
      }
      $js = "Adv.hoverWindow.init($width,$height);";
      $this->_onload($js);
      $this->_openWindow = true;
    }
    /**
     * @static
     *
     * @param       $id
     * @param       $callback
     * @param bool  $url
     * @param array $options
     */
    public function _autocomplete($id, $callback, $url = false) {
      if (!$url) {
        $url = $_SERVER['REQUEST_URI'];
      }
      $js    = "Adv.Forms.autocomplete('$id','$url',$callback);";
      $clean = "Adv.o.autocomplete['$id'].autocomplete('destroy');";
      $this->_addLive($js, $clean);
    }
    /**
     * @static
     *
     * @param $selector
     * @param $address
     * @param $title
     */
    public function _gmap($selector, $address, $title) {
      $address = str_replace(array("\r", "\t", "\n", "\v"), ", ", $address);
      $apikey  = $this->Config->_get('js.maps_api_key');
      $js      = "Adv.maps = { api_key: '$apikey'}";
      $this->_beforeload($js);
      $js = <<<JS
var map = $("<div/>").gMap({
  address:"__address_",
  markers:[{ address:"__address_", html:"_address", popup:true}],
  zoom:10}).appendTo('body').dialog({title:"__title_", autoOpen:false, show:"slide", hide:"slide", height:450, width:1000, modal:true});
$("__selector_").click(function () {  map.dialog("open"); return false; });
$(".ui-widget-overlay").click(function () { map.dialog("close");  return false; });
JS;
      $this->_addLive($js);
      $this->_footerFile('/js/libs/jquery.gmap-1.1.0-min.js');
    }
    /**
     * @static
     *
     * @param null $name
     *
     * @return null|string
     * Set default focus on first field $name if not set yet
     * Returns unique name if $name=null

     */
    public function _defaultFocus($name = null) {
      if ($name == null) {
        $name = uniqid('_el', true);
      }
      if (!isset($_POST['_focus'])) {
        $this->_setFocus($name);
      }
      return $name;
    }
    /**
     * @static

     */
    public function _resetFocus() {
      unset($_POST['_focus']);
    }
    /**
     * @static
     *
     * @param       $id
     * @param array $options
     * @param       $page
     */
    public function _tabs($id, $options = [], $page = null) {
      $defaults = ['noajax'=> false, 'haslinks'=> false];
      $options  = array_merge($defaults, $options);
      $noajax   = $options['noajax'] ? 'true' : 'false';
      $haslinks = $options['haslinks'] ? 'true' : 'false';
      $this->_onload("Adv.tabmenu.init('$id',$noajax,$haslinks,$page)");
    }
    /**
     * @static

     */
    public function _renderHeader() {
      $scripts = [];
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      foreach ($this->_headerFiles as $dir => $files) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $scripts[] = $dir . '/' . implode(',', $files);
      }
      return $scripts;
    }
    /**
     * @static

     */
    public function _render($return = false) {
      if ($return) {
        ob_start();
      }
      $files = $content = $onReady = '';
      if (!AJAX_REFERRER) {
        foreach ($this->_footerFiles as $dir => $file) {
          $files .= HTML::setReturn(true)->script(array('src' => $dir . '/' . implode(',', $file)), false)->setReturn(false);
        }
        echo $files;
      } else {
        $this->_beforeload = array_merge($this->_beforeload, $this->_onlive, $this->_onload);
        $this->_onlive     = $this->_onload = [];
      }
      if ($this->_beforeload) {
        $content .= implode("", $this->_beforeload);
      }
      if ($this->_onlive) {
        $onReady .= 'Adv.Events.onload(function(){' . implode("", $this->_onlive) . '}';
        if (count($this->_toclean)) {
          $onReady .= ',function(){' . implode("", $this->_toclean) . '}';
        }
        $onReady .= ');';
      }
      if ($this->_onload) {
        $onReady .= implode("", $this->_onload);
      }
      if (!empty($this->_focus)) {
        $onReady .= $this->_focus . '.focus();';
      }
      if ($onReady != '') {
        $content .= "\n$(function(){ " . $onReady . '});';
      }
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      HTML::script(array('content' => $content))->script;
      if ($return) {
        return ob_get_clean();
      }
      return true;
    }
    /**
     * @static
     *
     * @param $data
     */
    public function _renderJSON($data) {
      $data  = (array) $data;
      $error = Errors::JSONError();
      if (isset($data['status']) && $data['status'] && Errors::dbErrorCount()) {
        $data['status'] = $error;
      } elseif (!isset($data['status']) && Errors::messageCount()) {
        $data['status'] = $error;
      }
      ob_end_clean();
      echo   json_encode($data);
      exit();
    }
    /**
     * @static
     *
     * @param      $selector
     * @param bool $cached
     * @param bool $cached
     */
    public function _setFocus($selector, $cached = false) {
      $this->_focus = ($selector) ? (!$cached) ? "$('$selector')" : 'Adv.o.' . $selector : false;
      Ajax::addFocus(true, $selector);
      $_POST['_focus'] = $selector;
    }
    /**
     * @static
     *
     * @param array $options
     * @param array $funcs
     * @param int   $level
     *
     * @return string
     * @return array|mixed|string
     */
    public function _arrayToOptions($options = [], $funcs = [], $level = 0) {
      foreach ($options as $key => $value) {
        if (is_array($value)) {
          $ret           = $this->_arrayToOptions($value, $funcs, 1);
          $options[$key] = $ret[0];
          $funcs         = $ret[1];
        } else {
          if (substr($value, 0, 9) == 'function(') {
            $func_key         = "#" . uniqid() . "#";
            $funcs[$func_key] = $value;
            $options[$key]    = $func_key;
          }
        }
      }
      if ($level == 1) {
        return array($options, $funcs);
      } else {
        $input_json = json_encode($options);
        foreach ($funcs as $key => $value) {
          $input_json = str_replace('"' . $key . '"', $value, $input_json);
        }
        return $input_json;
      }
    }
    /**
     * @static
     *
     * @param $selector
     * @param $type
     * @param $action
     */
    public function _addEvent($selector, $type, $action) {
      $this->_onload("$('$selector').bind('$type',function(e){ {$action} }).css('cursor','pointer');");
    }
    /**
     * @static
     *
     * @param      $selector
     * @param      $type
     * @param      $action
     * @param bool $delegate
     * @param bool $cached
     */
    public function _addLiveEvent($selector, $type, $action, $delegate = false, $cached = false) {
      if (!$delegate) {
        $this->_addLive("$('$selector').bind('$type',function(e){ {$action} });");
      } else {
        $cached = (!$cached) ? "$('$delegate')" : 'Adv.o.' . $delegate;
        $this->register($cached . ".delegate('$selector','$type',function(e){ {$action} } )", $this->_onload);
      }
    }
    /**
     * @static
     *
     * @param      $action
     * @param bool $clean
     */
    public function _addLive($action, $clean = false) {
      $this->register($action, $this->_onlive);
      if ($clean) {
        $this->register($clean, $this->_toclean);
      }
    }
    /**
     * @static
     *
     * @param array $events
     */
    public function _addEvents($events = []) {
      if (is_array($events)) {
        foreach ($events as $event) {
          if (count($event == 3)) {
            call_user_func_array(array($this, '_addEvent'), $event);
          }
        }
      }
    }
    /**
     * @static
     *
     * @param bool $js
     */
    public function _onload($js = false) {
      if ($js) {
        $this->register($js, $this->_onload);
      }
    }
    /**
     * @static
     *
     * @param bool $js
     */
    public function _beforeload($js = false) {
      if ($js) {
        $this->register($js, $this->_beforeload);
      }
    }
    /**
     * @static
     *
     * @param $file
     */
    public function _headerFile($file) {
      $this->registerFile($file, $this->_headerFiles);
    }
    /**
     * @static
     *
     * @param $file
     */
    public function _footerFile($file) {
      $this->registerFile($file, $this->_footerFiles);
    }
    /**
     * @static
     *
     * @param bool $message
     */
    public function _onUnload($message = false) {
      if ($message) {
        $this->_addLiveEvent(':input', 'change', "Adv.Events.onLeave('$message')", 'wrapper', true);
        $this->_addLiveEvent('form', 'submit', "Adv.Events.onLeave()", 'wrapper', true);
      }
    }
    /**
     * @static
     *
     * @param $url
     */
    public function _redirect($url) {
      $data['status'] = array('status' => 'redirect', 'message' => $url);
      $this->_renderJSON($data);
    }
    /**
     * @static
     *
     * @param array|bool $js
     * @param            $var
     */
    protected function register($js = false, &$var) {
      if (is_array($js)) {
        foreach ($js as $j) {
          $this->register($j, $var);
        }
      } else {
        $js = rtrim($js, ';') . ';';
        array_push($var, str_replace(array('<script>', '</script>'), '', $js));
      }
    }
    /**
     * @static
     *
     * @param array|bool $file
     * @param            $var
     */
    protected function registerFile($file, &$var) {
      if (is_array($file)) {
        foreach ($file as $f) {
          $this->registerFile($f, $var);
        }
      } else {
        $dir  = dirname($file);
        $file = basename($file);
        isset($var[$dir]) or $var[$dir] = [];
        $var[$dir][$file] = $file;
      }
    }
  }

