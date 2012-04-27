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

   */
  class JS {

    /**
     * @var array
     */
    static private $_beforeload = array();
    /**
     * @var array
     */
    static private $_onload = array();
    /**
     * @var array
     */
    static private $_onlive = array();
    /**
     * @var array
     */
    static private $_toclean = array();
    /**
     * @var array
     */
    static private $_headerFiles = array();
    /**
     * @var array
     */
    static private $_footerFiles = array();
    /**
     * @var bool
     */
    static private $_focus = FALSE;
    /**
     * @var bool
     */
    static public $outputted = FALSE;
    /**
     * @var bool
     */
    static private $_openWindow = FALSE;
    /**

     */
    private function __construct() {
    }
    /**
     * @static
     *
     * @param $width
     * @param $height
     *
     * @return mixed
     */
    static public function open_window($width, $height) {
      if (static::$_openWindow || !Config::get('ui_windows_popups')) {
        return;
      }
      $js = "Adv.hoverWindow.init($width,$height);";
      static::onload($js);
      static::$_openWindow = TRUE;
    }
    /**
     * @static
     *
     * @param       $id
     * @param       $callback
     * @param bool  $url
     * @param array $options
     */
    static public function autocomplete($id, $callback, $url = FALSE, $options = array()) {
      if (!$url) {
        $url = $_SERVER['REQUEST_URI'];
      }
      $js = "Adv.Forms.autocomplete('$id','$url',$callback);";
      $clean = "Adv.o.autocomplete['$id'].autocomplete('destroy');";
      static::addLive($js, $clean);
    }
    /**
     * @static
     *
     * @param $selector
     * @param $address
     * @param $title
     */
    static public function gmap($selector, $address, $title) {
      $address = str_replace(array("\r", "\t", "\n", "\v"), ", ", $address);
      $apikey = Config::get('js.maps_api_key');
      $js
        = <<<JS

				Adv.maps = { api_key: '$apikey'}
JS;
      JS::beforeload($js);
      $js
        = <<<JS
var map = $("<div/>").gMap({
	address:"{$address}",
	markers: [{ address:"{$address}", html: "_address", popup: true}],
	zoom: 10}).appendTo('body').dialog({title: "{$title}", autoOpen: false, show: "slide", hide: "slide", height: 450, width: 1000, modal: true});
	$("{$selector}").click(function() {
 map.dialog("open");
 return false; });
 $(".ui-widget-overlay").click(function() {
 map.dialog("close");
 return false; });
JS;
      self::addLive($js);
      JS::footerFile('/js/libs/jquery.gmap-1.1.0-min.js');
    }
    /**
     * @static

     */
    static public function png_fix() {
      $js = "function fixPNG(myImage)\n{\n var arVersion = navigator.appVersion.split(\"MSIE\")\n var version = parseFloat(arVersion[1])\n if ((version >= 5.5) && (version < 7) && (document.body.filters))\n {\n" . " var imgID = (myImage.id) ? \"id='\" + myImage.id + \"' \" : \"\"\n var imgClass = (myImage.className) ? \"class='\" + myImage.className + \"' \" : \"\"\n var imgTitle = (myImage.title) ?\n" . " \"title='\" + myImage.title + \"' \" : \"title='\" + myImage.alt + \"' \"\n var imgStyle = \"display:inline-block;\" + myImage.style.cssText\n var strNewHTML = \"<span \" + imgID + imgClass + imgTitle\n + \" style=\\\"\" + \"width:\" + myImage.width\n" . " + \"px; height:\" + myImage.height\n + \"px;\" + imgStyle + \";\"\n + \"filter:progid:DXImageTransform.Microsoft.AlphaImageLoader\"\n + \"(src=\'\" + myImage.src + \"\', sizingMethod='scale');\\\"></span>\"\n myImage.outerHTML = strNewHTML\n }\n" . "}\n";
      JS::beforeload($js);
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
    static public function default_focus($name = NULL) {
      if ($name == NULL) {
        $name = uniqid('_el', TRUE);
      }
      if (!isset($_POST['_focus'])) {
        JS::set_focus($name);
      }
      return $name;
    }
    /**
     * @static

     */
    static public function reset_focus() {
      unset($_POST['_focus']);
    }
    /**
     * @static
     *
     * @param       $id
     * @param array $options
     * @param       $page
     */
    static public function tabs($id, $options = array(), $page = NULL) {
      $defaults = array('noajax' => FALSE, 'hasLinks' => FALSE);
      $hasLinks = FALSE;
      extract(array_merge($defaults, $options));
      $content = "Adv.o.tabs.$id = $('#" . $id . "').tabs(";
      if ($hasLinks) {
        $content
          .= <<<JSS
    {
    select: function(event, ui) {
    var \$tab = $(ui.tab);

        var param = $('#'+\$tab.data('paramel')).val();
        var url = $.data(ui.tab, 'load.tabs')+param;
        var target = \$tab.data('target');
        if( url ) {
            if (target) {
            Adv.openWindow(url,'Test');
            }else{
            location.href = url;
            }
            return false;
        }
        return true;
    }
    }
JSS;
      }
      $content .= ").toggleClass('tabs')";
      if ($page) {
        $content .= ".tabs('select'," . $page . ")";
      }
      $content .= ";";
      self::onload($content);
    }
    /**
     * @static

     */
    static public function renderHeader() {
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      HTML::script(NULL, "document.documentElement.className = document.documentElement.className +' js'", FALSE);
      foreach (self::$_headerFiles as $dir => $files) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        HTML::script(array('src' => $dir . '/' . implode(',', $files)), FALSE);
      }
    }
    /**
     * @static

     */
    static public function render() {
      $files = $content = $onReady = '';
      if (!AJAX_REFERRER) {
        foreach (self::$_footerFiles as $dir => $file) {
          $files .= HTML::setReturn(TRUE)->script(array('src' => $dir . '/' . implode(',', $file)),
            FALSE)->setReturn(FALSE);
        }
        echo $files;
      }
      else {
        self::$_beforeload = array_merge(self::$_beforeload, self::$_onlive, self::$_onload);
        self::$_onlive = self::$_onload = array();
      }
      if (self::$_beforeload) {
        $content .= implode("", self::$_beforeload);
      }
      if (self::$_onlive) {
        $onReady .= 'Adv.Events.onload(function(){' . implode("", self::$_onlive) . '}';
        if (count(self::$_toclean)) {
          $onReady .= ',function(){' . implode("", self::$_toclean) . '}';
        }
        $onReady .= ');';
      }
      if (self::$_onload) {
        $onReady .= implode("", self::$_onload);
      }
      if (!empty(self::$_focus)) {
        $onReady .= self::$_focus . '.focus();';
      }
      if ($onReady != '') {
        $content .= "\n$(function(){ " . $onReady . '});';
      }
      //	$cachekey = 'js_min.' . md5($content);
      //	$cachecontent = Cache::get($cachekey);
      //	if (!$cachecontent) {
      //			$cachecontent = Cache::set($cachekey, JSMin::minify($content));
      //		}
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      HTML::script(array('content' => $content))->script;
    }
    /**
     * @static
     *
     * @param $data
     */
    static public function renderJSON($data) {
      $data = (array) $data;
      $error = Errors::JSONError();
      if (isset($data['status']) && $data['status'] && Errors::dbErrorCount()) {
        $data['status'] = $error;
      }
      elseif (!isset($data['status']) && Errors::messageCount()) {
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
     */
    static public function setFocus($selector, $cached = FALSE) {
      $_POST['_focus'] = self::$_focus = ($selector) ? (!$cached) ? "$('$selector')" : 'Adv.o.' . $selector : FALSE;
    }
    /**
     * @static
     *
     * @param array $options
     * @param array $funcs
     * @param int   $level
     *
     * @return string
     */
    static public function arrayToOptions($options = array(), $funcs = array(), $level = 0) {
      foreach ($options as $key => $value) {
        if (is_array($value)) {
          $ret = static::arrayToOptions($value, $funcs, 1);
          $options[$key] = $ret[0];
          $funcs = $ret[1];
        }
        else {
          if (substr($value, 0, 9) == 'function(') {
            $func_key = "#" . uniqid() . "#";
            $funcs[$func_key] = $value;
            $options[$key] = $func_key;
          }
        }
      }
      if ($level == 1) {
        return array($options, $funcs);
      }
      else {
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
    static public function addEvent($selector, $type, $action) {
      self::onload("$('$selector').bind('$type',function(e){ {$action} }).css('cursor','pointer');");
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
    static public function addLiveEvent($selector, $type, $action, $delegate = FALSE, $cached = FALSE) {
      if (!$delegate) {
        return self::addLive("$('$selector').bind('$type',function(e){ {$action} });");
      }
      $cached = (!$cached) ? "$('$delegate')" : 'Adv.o.' . $delegate;
      return self::register($cached . ".delegate('$selector','$type',function(e){ {$action} } )", self::$_onload);
    }
    /**
     * @static
     *
     * @param      $action
     * @param bool $clean
     */
    static public function addLive($action, $clean = FALSE) {
      self::register($action, self::$_onlive);
      if ($clean) {
        self::register($clean, self::$_toclean);
      }
    }
    /**
     * @static
     *
     * @param array $events
     */
    static public function addEvents($events = array()) {
      if (is_array($events)) {
        foreach ($events as $event) {
          if (count($event == 3)) {
            call_user_func_array('JS::addEvent', $event);
          }
        }
      }
    }
    /**
     * @static
     *
     * @param bool $js
     */
    static public function onload($js = FALSE) {
      if ($js) {
        self::register($js, self::$_onload);
      }
    }
    /**
     * @static
     *
     * @param bool $js
     */
    static public function beforeload($js = FALSE) {
      if ($js) {
        self::register($js, self::$_beforeload);
      }
    }
    /**
     * @static
     *
     * @param $file
     */
    static public function headerFile($file) {
      self::registerFile($file, self::$_headerFiles);
    }
    /**
     * @static
     *
     * @param $file
     */
    static public function footerFile($file) {
      self::registerFile($file, self::$_footerFiles);
    }
    /**
     * @static
     *
     * @param array|bool $js
     * @param            $var
     */
    static protected function register($js = FALSE, &$var) {
      if (is_array($js)) {
        foreach ($js as $j) {
          self::register($j, $var);
        }
      }
      else {
        $js = rtrim($js, ';') . ';';
        array_unshift($var, str_replace(array('<script>', '</script>'), '', $js));
      }
    }
    /**
     * @static
     *
     * @param array|bool $file
     * @param            $var
     */
    static protected function registerFile($file, &$var) {
      if (is_array($file)) {
        foreach ($file as $f) {
          self::registerFile($f, $var);
        }
      }
      else {
        $dir = dirname($file);
        $file = basename($file);
        isset($var[$dir]) or $var[$dir] = array();
        $var[$dir][$file] = $file;
      }
    }
    /**
     * @static
     *
     * @param bool $message
     */
    static public function onUnload($message = FALSE) {
      if ($message) {
        self::addLiveEvent(':input', 'change', "Adv.Events.onLeave('$message')", 'wrapper', TRUE);
        self::addLiveEvent('form', 'submit', "Adv.Events.onLeave()", 'wrapper', TRUE);
      }
    }
    /**
     * @static
     *
     * @param $url
     */
    static public function redirect($url) {
      $data['status'] = array('status' => 'redirect', 'message' => $url);
      static::renderJSON($data);
    }
    /***
     * @static
     *
     * @param $name
     * Setting focus on element $name in $form.
     * If $form<0 $name is element id.
     */
    static public function set_focus($name) {
      Ajax::i()->addFocus(TRUE, $name);
      $_POST['_focus'] = $name;
    }
  }

  JS::footerFile(Config::get('assets.footer'));
