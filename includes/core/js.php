<?php
	include(DOCROOT . 'modules/smartoptimizer/minifiers/js.php');
	/**
	 *
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
		public static $outputted = false;
		/**
		 * @var bool
		 */
		private static $_openWindow = false;

		/**
		 *
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
		public static function open_window($width, $height) {
			if (static::$_openWindow || !Config::get('ui_windows_popups')) {
				return;
			}
			$js = <<<JS
		Adv.o.wrapper.off('click.open mouseenter.open').on('click.open mouseenter.open mouseleave.open','td .openWindow',
			function(e) {
				if (e.type=='click') {
					Adv.openWindow(this.href, this.target,{$width},{$height});
					return false;
				}
				if (e.type=='mouseenter') {

				if (Adv.o.popupCurrent) window.clearTimeout(Adv.o.popupCurrent);
				Adv.o.popupEl= this;
				Adv.o.popupParent= $(this).parent();
				Adv.o.popupCurrent = window.setTimeout(Adv.popupWindow,750);
				}if (e.type=='mouseleave') {
window.clearTimeout(Adv.o.popupCurrent);
}

			});
			Adv.popupWindow = function() {
					if (Adv.o.order_details) Adv.o.order_details.remove();
								Adv.o.order_details = $("<iframe>", {src:Adv.o.popupEl.href+"&popup=1", width: {$width}, height: {$height}})
									.css({position:'fixed', background:'white'})
									.appendTo(Adv.o.wrapper)
									.position({my:"left center",at:"right top", of:Adv.o.popupParent}).css({top:20})
									.on('mouseleave',function() { $(this).remove();
								});
			}
JS;
			static::onload($js);
			static::$_openWindow = true;
		}

		/**
		 * @static
		 *
		 * @param			 $id
		 * @param			 $callback
		 * @param bool	$url
		 * @param array $options
		 */
		public static function autocomplete($id, $callback, $url = false, $options = array()) {
			if (!$url) {
				$url = $_SERVER['PHP_SELF'];
			}
			self::$_onload[] = <<<JS
Adv.Forms.autocomplete('$id','$url',$callback);
JS;
			if (isset($options['focus'])) {
				self::setFocus("autocomplete.$id", true);
			}
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
			$apikey = Config::get('js.maps_api_key');
			$js = <<<JS

				Adv.maps = { api_key: '$apikey'}
JS;
			JS::beforeload($js);
			$js = <<<JS
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
		 *
		 */
		public static function png_fix() {
			$js = "function fixPNG(myImage)\n{\n var arVersion = navigator.appVersion.split(\"MSIE\")\n var version = parseFloat(arVersion[1])\n if ((version >= 5.5) && (version < 7) && (document.body.filters))\n {\n" . " var imgID = (myImage.id) ? \"id='\" + myImage.id + \"' \" : \"\"\n var imgClass = (myImage.className) ? \"class='\" + myImage.className + \"' \" : \"\"\n var imgTitle = (myImage.title) ?\n" . " \"title='\" + myImage.title + \"' \" : \"title='\" + myImage.alt + \"' \"\n var imgStyle = \"display:inline-block;\" + myImage.style.cssText\n var strNewHTML = \"<span \" + imgID + imgClass + imgTitle\n + \" style=\\\"\" + \"width:\" + myImage.width\n" . " + \"px; height:\" + myImage.height\n + \"px;\" + imgStyle + \";\"\n + \"filter:progid:DXImageTransform.Microsoft.AlphaImageLoader\"\n + \"(src=\'\" + myImage.src + \"\', sizingMethod='scale');\\\"></span>\"\n myImage.outerHTML = strNewHTML\n }\n" . "}\n";
			JS::beforeload($js);
		}

		/**
		 * @static
		 *
		 */
		public static function get_websales() {
			static $inserted;
			if ($_SERVER['SERVER_NAME'] == 'advaccounts' && !$inserted && !isset($_SESSION['getWebsales'])) {
				$_SESSION['getWebsales'] = true;
				echo "<script>";
				echo <<<JS
$(function() {
if ($("#websaleGet").length>0) return;
$('<iframe\>').attr({'id':'websaleGet',src:'//{$_SERVER['SERVER_NAME']}/jobsboard/websales/'}).css({width:0,height:0}).appendTo('body');
$('<iframe\>').attr({'id':'customerGet',src:'//{$_SERVER['SERVER_NAME']}/modules/advanced/web.php'}).css({width:0,height:0}).appendTo('body')});
JS;
				echo "</script>";
				$inserted = true;
			}
		}

		/**
		 * @static
		 *
		 * @param null $name
		 *
		 * @return null|string
		 *
		 * Set default focus on first field $name if not set yet
		 * Returns unique name if $name=null
		 *
		 */
		public static function default_focus($name = null) {
			if ($name == null) {
				$name = uniqid('_el', true);
			}
			if (!isset($_POST['_focus'])) {
				JS::set_focus($name);
			}
			return $name;
		}

		/**
		 * @static
		 *
		 */
		public static function reset_focus() {
			unset($_POST['_focus']);
		}

		/**
		 * @static
		 *
		 * @param			 $id
		 * @param array $options
		 * @param			 $page
		 */
		public static function tabs($id, $options = array(), $page) {
			$defaults = array('noajax' => false);
			extract(array_merge($defaults, $options));
			$content = "$('" . $id . "').tabs().toggleClass('tabs')";
			if ($page) {
				$content .= ".tabs('select'," . $page . ")";
			}
			$content .= ";";
			self::onload($content);
		}

		/**
		 * @static
		 *
		 */
		public static function renderHeader() {
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			HTML::script(null, "document.documentElement.className = document.documentElement.className +' js'", false);
			foreach (self::$_headerFiles as $dir => $files) {
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				HTML::script(array('src' => $dir . '/' . implode(',', $files)), false);
			}
		}

		/**
		 * @static
		 *
		 */
		public static function render() {
			$files = $content = $onReady = '';
			if (!AJAX_REFERRER) {
				foreach (self::$_footerFiles as $dir => $file) {
					$files .= HTML::setReturn(true)->script(array('src' => $dir . '/' . implode(',', $file)), false)->setReturn(false);
				}
				echo $files;
			} else {
				self::$_beforeload = array_merge(self::$_beforeload, self::$_onlive, self::$_onload);
				self::$_onlive = self::$_onload = array();
			}
			if (self::$_beforeload) {
				$content .= implode(";\n", self::$_beforeload);
			}
			if (self::$_onlive) {
				$onReady .= 'Adv.Events.onload(function() {' . implode(";", self::$_onlive) . '}';
				if (count(self::$_toclean)) {
					$onReady .= ',function() {' . implode(";", self::$_toclean) . '}';
				}
				$onReady .= ');';
			}
			if (self::$_onload) {
				$onReady .= implode(";\n\n", self::$_onload);
			}
			if (!empty(self::$_focus)) {
				$onReady .= self::$_focus . '.focus();';
			}
			if ($onReady != '') {
				$content .= ";\n$(function() { " . $onReady . '});';
			}
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			HTML::script(array('content' => $content))->script;
		}

		/**
		 * @static
		 *
		 */
		public static function renderJSON($data) {
			if (!isset($data['status']) && count(Errors::$messages) > 0) {
				$data = (array)$data;
				$message = array_pop(Errors::$messages);
				$status['status'] = false;
				$status['message'] = $message['message'];
				$status['var'] = basename($message['file']) . $message['line'];
				$status['process'] = '';
				$data['status'] = $status;
			} elseif (isset($data['status']) && $data['status']['status'] == false && count(Errors::$dberrors) > 0) {
				$message = array_pop(Errors::$dberrors);
				$status['status'] = false;
				$status['message'] = $message['message'];
			}
ob_end_clean();
			echo	 json_encode($data);
			JS::$outputted = true;
			exit();
		}

		/**
		 * @static
		 *
		 * @param			$selector
		 * @param bool $cached
		 */
		public static function setFocus($selector, $cached = false) {
			$_POST['_focus'] = self::$_focus = ($selector) ? (!$cached) ? "$('$selector')" : 'Adv.o.' . $selector : false;
		}

		/**
		 * @static
		 *
		 * @param array $options
		 *
		 * @return string
		 */
		public static function arrayToOptions($options = array()) {
			$options = (object)$options;
			return json_encode($options);
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
		 * @param			$selector
		 * @param			$type
		 * @param			$action
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
		 * @param			$action
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
		 * @param						$var
		 */
		protected static function register($js = false, &$var) {
			if (is_array($js)) {
				foreach ($js as $j) {
					self::register($j, $var);
				}
			} else {
				array_unshift($var, str_replace(array('<script>', '</script>'), '', $js));
			}
		}

		/**
		 * @static
		 *
		 * @param array|bool $file
		 * @param						$var
		 */
		protected static function registerFile($file, &$var) {
			if (is_array($file)) {
				foreach ($file as $f) {
					self::registerFile($f, $var);
				}
			} else {
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
		public static function onUnload($message = false) {
			if ($message) {
				self::addLiveEvent(':input', 'change', "Adv.Events.onLeave('$message')", 'wrapper', true);
				self::addLiveEvent('form', 'submit', "Adv.Events.onLeave()", 'wrapper', true);
			}
		}

		/***
		 * @static
		 *
		 * @param $name
		 * Setting focus on element $name in $form.
		 * If $form<0 $name is element id.
		 */
		public static function set_focus($name) {
			$Ajax = Ajax::i();
			$Ajax->addFocus(true, $name);
			$_POST['_focus'] = $name;
		}
	}
