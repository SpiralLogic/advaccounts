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
   */
  class JS
  {

    /**
     * @var array
     */
    private static $_beforeload = array();
    /**
     * @var array
     */
    private static $_onload = array();
    /**
     * @var array
     */
    private static $_onlive = array();
    /**
     * @var array
     */
    private static $_toclean = array();
    /**
     * @var array
     */
    private static $_headerFiles = array();
    /**
     * @var array
     */
    private static $_footerFiles = array();
    /**
     * @var bool
     */
    private static $_focus = false;
    /**
     * @var bool
     */
    public static $outputted = false;
    /**
     * @var bool
     */
    private static $_openWindow = false;
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
    public static function openWindow($width, $height) {
      if (static::$_openWindow || !Config::get('ui_windows_popups')) {
        return;
      }
      $js = "Adv.hoverWindow.init($width,$height);";
      static::onload($js);
      static::$_openWindow = true;
    }
    /**
     * @static
     *
     * @param       $id
     * @param       $callback
     * @param bool  $url
     * @param array $options
     */
    public static function autocomplete($id, $callback, $url = false, $options = array()) {
      if (!$url) {
        $url = $_SERVER['REQUEST_URI'];
      }
      $js    = "Adv.Forms.autocomplete('$id','$url',$callback);";
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
    public static function gmap($selector, $address, $title) {
      $address = str_replace(array("\r", "\t", "\n", "\v"), ", ", $address);
      $apikey  = Config::get('js.maps_api_key');
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
    public static function pngFix() {
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
    public static function defaultFocus($name = null) {
      if ($name == null) {
        $name = uniqid('_el', true);
      }
      if (!isset($_POST['_focus'])) {
        JS::setFocus($name);
      }
      return $name;
    }
    /**
     * @static

     */
    public static function resetFocus() {
      unset($_POST['_focus']);
    }
    /**
     * @static
     *
     * @param       $id
     * @param array $options
     * @param       $page
     */
    public static function tabs($id, $options = array(), $page = null) {
      $defaults = array('noajax' => false, 'hasLinks' => false);
      $hasLinks = false;
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
        if ( url ) {
            if (target) {
            Adv.openWindow(url,'Test');
            } else {
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
    public static function renderHeader() {
      $scripts = [];
      /** @noinspection PhpDynamicAsStaticMethodCallInspection */
      foreach (self::$_headerFiles as $dir => $files) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $scripts[] = $dir . '/' . implode(',', $files);
      }
      return $scripts;
    }
    /**
     * @static

     */
    public static function render($return = false) {
      if ($return) {
        ob_start();
      }
      $files = $content = $onReady = '';
      if (!AJAX_REFERRER) {
        foreach (self::$_footerFiles as $dir => $file) {
          $files .= HTML::setReturn(true)->script(array('src' => $dir . '/' . implode(',', $file)), false)->setReturn(false);
        }
        echo $files;
      } else {
        self::$_beforeload = array_merge(self::$_beforeload, self::$_onlive, self::$_onload);
        self::$_onlive     = self::$_onload = array();
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
    public static function renderJSON($data) {
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
    public static function setFocus($selector, $cached = false) {
      self::$_focus = ($selector) ? (!$cached) ? "$('$selector')" : 'Adv.o.' . $selector : false;
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
    public static function arrayToOptions($options = array(), $funcs = array(), $level = 0) {
      foreach ($options as $key => $value) {
        if (is_array($value)) {
          $ret           = static::arrayToOptions($value, $funcs, 1);
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
    public static function addEvent($selector, $type, $action) {
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
    public static function addLiveEvent($selector, $type, $action, $delegate = false, $cached = false) {
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
    public static function addLive($action, $clean = false) {
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
    public static function addEvents($events = array()) {
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
    public static function onload($js = false) {
      if ($js) {
        self::register($js, self::$_onload);
      }
    }
    /**
     * @static
     *
     * @param bool $js
     */
    public static function beforeload($js = false) {
      if ($js) {
        self::register($js, self::$_beforeload);
      }
    }
    /**
     * @static
     *
     * @param $file
     */
    public static function headerFile($file) {
      self::registerFile($file, self::$_headerFiles);
    }
    /**
     * @static
     *
     * @param $file
     */
    public static function footerFile($file) {
      self::registerFile($file, self::$_footerFiles);
    }
    /**
     * @static
     *
     * @param array|bool $js
     * @param            $var
     */
    protected static function register($js = false, &$var) {
      if (is_array($js)) {
        foreach ($js as $j) {
          self::register($j, $var);
        }
      } else {
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
    protected static function registerFile($file, &$var) {
      if (is_array($file)) {
        foreach ($file as $f) {
          self::registerFile($f, $var);
        }
      } else {
        $dir  = dirname($file);
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
    public static function onUnload($message = false) {
      if ($message) {
        self::addLiveEvent(':input', 'change', "Adv.Events.onLeave('$message')", 'wrapper', true);
        self::addLiveEvent('form', 'submit', "Adv.Events.onLeave()", 'wrapper', true);
      }
    }
    /**
     * @static
     *
     * @param $url
     */
    public static function redirect($url) {
      $data['status'] = array('status' => 'redirect', 'message' => $url);
      static::renderJSON($data);
    }
  }

  JS::footerFile(Config::get('assets.footer'));
