<?php
include(APP_PATH . 'modules/smartoptimizer/minifiers/js.php');
	class JS {

		private static $_beforeload = array();
		private static $_onload = array();
		private static $_onlive = array();

		private static $_headerFiles = array();
		private static $_footerFiles = array();

		private function __construct() {
		}

		public static function autocomplete($id, $callback, $url = false, $options = array()) {
			$afterSource = (isset($options['afterSource'])) ? $options['afterSource'] . '(data,request);' : '';
			if (!$url) {
				$url = $_SERVER['PHP_SELF'];
			}
			self::$_onload[] = <<< JS
      var autocomplete{$id},\${$id} =$('#{$id}').autocomplete({
         autoFocus:true,
	      source: function(request, response) {
		      var lastXhr = $.getJSON('{$url}', request, function(data, status, xhr) {
			   	if (xhr === lastXhr) {
				   	response(data);

				   	{$afterSource}
				   }
			   });
		   },
		   select: function(event, ui) {
	   	 if ({$callback}(ui.item)===false) return false;
   	   }
      }).css({'z-index' : '2'});
JS;
		}

		public static function setfocus($id = false) {
			if (!$id) {
				return;
			}
			self::$_onload[] = <<< JS

$('{$id}').focus();

JS;

		}

		public static function gmap($selector, $address, $title) {
			$address = str_replace("\r\t\n\v", ", ", $address);
			self::$_onload[] = <<<JS

	var map = $("<div/>").gMap({
	address:"{$address}",
	markers: [{ address:"{$address}", html: "_address", popup: true}],
	zoom: 10}).appendTo('body').dialog({title: "{$title}", autoOpen: false, show: "slide", hide: "slide", height: 450, width: 1000, modal: true});
${$selector} = $("{$selector}").click(function() {
    map.dialog("open");
    return false; });
    $(".ui-widget-overlay").click(function() {
    map.dialog("close");
    return false; });
JS;

		}

		public static function tabs($id, $options = array(), $page) {
			$defaults = array('noajax' => false);
			extract(array_merge($defaults, $options));
			$content = "$('" . $id . "').tabs()";
			if ($page) {
				$content .= ".tabs('select'," . $page . ")";
			}
			$content .= ";";

			self::onload($content);
		}

		public static function renderHeader() {

			foreach (self::$_headerFiles as $dir => $files) {
				HTML::script(array('src' => $dir . '/' . implode(',', $files)), false);
			}

		}

		public static function render() {
			if (AJAX_REFERRER) {
				HTML::script(null, 'Adv.Events.rebind()', false);
			} else {
				$files = $content = '';
				foreach (self::$_footerFiles as $dir => $files) {
					$files .= HTML::setReturn(true)->script(array('src' => $dir . '/' . implode(',', $files)), false)->setReturn(false);
				}
				if (count(self::$_beforeload)) {
					$content .= implode(";\n", self::$_beforeload);
				}
				$content .= ";\n$(function() { ";
				if (count(self::$_onlive)) {
					$content .= 'Adv.Events.onload([function() {' . implode("}, function() {", self::$_onlive) . '}]);';
				}
				if (count(self::$_onload)) {
					$content .= implode(";\n\n", self::$_onload);
				}
				$content .= '});
	';
				echo $files;
				HTML::script(array('content' => $content))->script;
			}
		}

		public static function arrayToOptions($options = array()) {
			$options = (object)$options;
			return json_encode($options);
		}

		public static function addEvent($selector, $type, $action) {

			self::onload("$('$selector').bind('$type',function(e){ {$action} });");
		}

		public static function addLiveEvent($selector, $type, $action, $delegate = false) {

			if (!$delegate) {
				return self::addLive("$('$selector').bind('$type',function(e){ {$action} });");
			}
			self::register("$('$delegate').delegate('$selector','$type',function(e){ {$action} } )", self::$_onload);
		}

		public static function addLive($action) {
			self::register($action, self::$_onlive);
		}

		public static function addEvents($events = array()) {
			if (is_array($events)) {
				foreach ($events as $event) {
					if (count($event == 3)) {
						call_user_func_array('JS::addEvent', $event);
					}
				}
			}
		}

		public static function onload($js = false) {
			if ($js) {
				self::register($js, self::$_onload);
			}
		}

		public static function beforeload($js = false) {
			if ($js) {
				self::register($js, self::$_beforeload);
			}
		}

		public static function headerFile($file) {
			self::registerFile($file, self::$_headerFiles);

		}

		public static function footerFile($file) {
			self::registerFile($file, self::$_footerFiles);
		}

		protected static function register($js = false, &$var) {
			if (is_array($js)) {
				foreach ($js as $j) self::register($j, $var);
			} else {
				array_unshift($var, str_replace(array('<script>', '</script>'), '', $js));
			}
		}

		protected static function registerFile($file, &$var) {
			if (is_array($file)) {
				foreach ($file as $f) self::registerFile($f, $var);
			} else {
				$dir = dirname($file);
				$file = basename($file);
				if (!isset($var[$dir])) {
					$var[$dir] = array();
				}
				array_push($var[$dir], $file);
			}
		}

	}
