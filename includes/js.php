<?php
	include(APP_PATH . 'modules/smartoptimizer/minifiers/js.php');
	class JS
	{
		private static $_beforeload = array();
		private static $_onload = array();
		private static $_onlive = array();
		private static $_toclean = array();
		private static $_headerFiles = array();
		private static $_footerFiles = array();
		private static $_focus = false;
		private static $_openWindow = false;

		private function __construct()
		{
		}

		public static function open_window($width, $height)
		{
			if (Config::get('ui_windows_popups')) {
				$js = "Adv.openWindow = function(url, title)\n{\n var left = (screen.width - $width) / 2;\n var top = (screen.height - $height) / 2;\n" . " return window.open(url, title, 'width=$width,height=$height,left='+left+',top='+top+',screenX='+left+',screenY='+top+',status=no,scrollbars=yes');\n}\n";
				static::beforeload($js);
				if (static::$_openWindow) {
					return;
				}
				$js = "$('.openWindow').click(function() { Adv.openWindow(this.href,this.target); return false;});";
				static::addLive($js);
				static::$_openWindow = true;
			}
		}

		public static function autocomplete($id, $callback, $url = false, $options = array())
		{
			$afterSource = (isset($options['afterSource'])) ? $options['afterSource'] . '(data,request);' : '';
			if (!$url) {
				$url = $_SERVER['PHP_SELF'];
			}
			self::$_onload[] = <<<JS
      var \${$id};
      Adv.o.autocomplete{$id} = \${$id} =$('#{$id}').autocomplete({
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
	   	 if ({$callback}(ui.item,event)===false) return false;
   	   }
      }).css({'z-index' : '2'}).bind('paste',function() { console.log(\${$id}.val());\${$id}.autocomplete('search',\${$id}.val())});
JS;
			if (isset($options['focus'])) {
				self::setFocus("autocomplete{$id}", true);
			}
		}

		public static function gmap($selector, $address, $title)
		{
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

		static function png_fix()
		{
			$js = "function fixPNG(myImage)\n{\n var arVersion = navigator.appVersion.split(\"MSIE\")\n var version = parseFloat(arVersion[1])\n if ((version >= 5.5) && (version < 7) && (document.body.filters))\n {\n" . "  var imgID = (myImage.id) ? \"id='\" + myImage.id + \"' \" : \"\"\n  var imgClass = (myImage.className) ? \"class='\" + myImage.className + \"' \" : \"\"\n  var imgTitle = (myImage.title) ?\n" . "    \"title='\" + myImage.title  + \"' \" : \"title='\" + myImage.alt + \"' \"\n  var imgStyle = \"display:inline-block;\" + myImage.style.cssText\n  var strNewHTML = \"<span \" + imgID + imgClass + imgTitle\n    + \" style=\\\"\" + \"width:\" + myImage.width\n" . "    + \"px; height:\" + myImage.height\n    + \"px;\" + imgStyle + \";\"\n    + \"filter:progid:DXImageTransform.Microsoft.AlphaImageLoader\"\n    + \"(src=\'\" + myImage.src + \"\', sizingMethod='scale');\\\"></span>\"\n  myImage.outerHTML = strNewHTML\n }\n" . "}\n";
			JS::beforeload($js);
		}

		static function get_websales()
		{
			static $inserted;
			if ($_SERVER['SERVER_NAME'] == 'advaccounts' && !$inserted && !isset($_SESSION['getWebsales'])) {
				$_SESSION['getWebsales'] = true;
				echo "<script>";
				echo <<<JS
$(function() {
if ($("#websaleGet").length>0) return;
$('<iframe\>').attr({'id':'websaleGet',src:'//{$_SERVER['SERVER_NAME']}/jobsboard/websales/'}).css({width:0,height:0}).appendTo('body')});
JS;
				echo "</script>";
				$inserted = true;
			}
		}

		//
		//	Set default focus on first field $name if not set yet
		//	Returns unique name if $name=null
		//
		static function default_focus($name = null)
		{
			if ($name == null) {
				$name = uniqid('_el', true);
			}
			if (!isset($_POST['_focus'])) {
				JS::set_focus($name);
			}
			return $name;
		}

		/*
							 Reset focus to next control element (e.g. link).
					 */
		static function reset_focus()
		{
			unset($_POST['_focus']);
		}

		public static function tabs($id, $options = array(), $page)
		{
			$defaults = array('noajax' => false);
			extract(array_merge($defaults, $options));
			$content = "$('" . $id . "').tabs().toggleClass('tabs')";
			if ($page) {
				$content .= ".tabs('select'," . $page . ")";
			}
			$content .= ";";
			self::onload($content);
		}

		public static function renderHeader()
		{
			HTML::script(null, "document.documentElement.className = document.documentElement.className +' js'", false);
			foreach (self::$_headerFiles as $dir => $files) {
				HTML::script(array('src' => $dir . '/' . implode(',', $files)), false);
			}
		}

		public static function render()
		{
			$files = $content = $onReady = '';
			if (!AJAX_REFERRER) {
				foreach (self::$_footerFiles as $dir => $file) {
					$files .= HTML::setReturn(true)->script(array('src' => $dir . '/' . implode(',', $file)), false)->setReturn(false);
				}
				echo $files;
			}
			if (count(self::$_beforeload)) {
				$content .= implode(";\n", self::$_beforeload);
			}
			if (count(self::$_onlive)) {
				$onReady .= 'Adv.Events.onload(function() {' . implode(";", self::$_onlive) . '}';
				if (count(self::$_toclean)) {
					$onReady .= ',function() {' . implode(";", self::$_toclean) . '}';
				}
				$onReady .= ');';
			}
			if (count(self::$_onload)) {
				$onReady .= implode(";\n\n", self::$_onload);
			}
			if (!empty(self::$_focus)) {
				$onReady .= self::$_focus . '.focus();';
			}
			if ($onReady != '') {
				$content .= ";\n$(function() { " . $onReady . '});';
			}
			HTML::script(array('content' => $content))->script;
		}

		public static function setFocus($selector, $cached = false)
		{
			$_POST['_focus'] = self::$_focus = ($selector) ? (!$cached) ? "$('$selector')" : 'Adv.o.' . $selector : false;
		}

		public static function arrayToOptions($options = array())
		{
			$options = (object)$options;
			return json_encode($options);
		}

		public static function addEvent($selector, $type, $action)
		{
			self::onload("$('$selector').bind('$type',function(e){ {$action} }).css('cursor','pointer');");
		}

		public static function addLiveEvent($selector, $type, $action, $delegate = false, $cached = false)
		{
			if (!$delegate) {
				return self::addLive("$('$selector').bind('$type',function(e){ {$action} });");
			}
			$cached = (!$cached) ? "$('$delegate')" : 'Adv.o.' . $delegate;
			self::register($cached . ".delegate('$selector','$type',function(e){ {$action} } )", self::$_onload);
		}

		public static function addLive($action, $clean = false)
		{
			self::register($action, self::$_onlive);
			if ($clean) {
				self::register($clean, self::$_toclean);
			}
		}

		public static function addEvents($events = array())
		{
			if (is_array($events)) {
				foreach ($events as $event) {
					if (count($event == 3)) {
						call_user_func_array('JS::addEvent', $event);
					}
				}
			}
		}

		public static function onload($js = false)
		{
			if ($js) {
				self::register($js, self::$_onload);
			}
		}

		public static function beforeload($js = false)
		{
			if ($js) {
				self::register($js, self::$_beforeload);
			}
		}

		public static function headerFile($file)
		{
			self::registerFile($file, self::$_headerFiles);
		}

		public static function footerFile($file)
		{
			self::registerFile($file, self::$_footerFiles);
		}

		protected static function register($js = false, &$var)
		{
			if (is_array($js)) {
				foreach ($js as $j) {
					self::register($j, $var);
				}
			} else {
				array_unshift($var, str_replace(array('<script>', '</script>'), '', $js));
			}
		}

		protected static function registerFile($file, &$var)
		{
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

		static function onUnload($message = false)
		{
			if ($message) {
				self::addLiveEvent(':input', 'change', "Adv.Events.onLeave('$message')", 'wrapper', true);
				self::addLiveEvent('form', 'submit', "Adv.Events.onLeave()", 'wrapper', true);
			}
		} /*
					   Setting focus on element $name in $form.
					   If $form<0 $name is element id.
				   */
		public static function set_focus($name)
		{
			$Ajax = Ajax::instance();
			$Ajax->addFocus(true, $name);
			$_POST['_focus'] = $name;
		}
	}
