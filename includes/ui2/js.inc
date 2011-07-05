<?php
include(APP_PATH . 'modules/smartoptimizer/minifiers/js.php');
   class JS {

      private static $_javascript = array();
      private static $_beforeload = array();
      private static $_onload = array();

      private function __construct() {
      }

      public static function autocomplete($id, $callback, $url = false, $options = array()) {
         global $js_lib;
         $afterSource = (isset($options['afterSource'])) ? $options['afterSource'] : '';
         if (!$url)
            $url = $_SERVER['PHP_SELF'];
         self::$_onload[] = <<< JS

       $('#{$id}').autocomplete({
       autoFocus:true,
		source: function(request, response) {
			lastXhr = $.getJSON('{$url}', request, function(data, status, xhr) {
				if (xhr === lastXhr) {
					response(data);
					{$afterSource}(data,request);
				}
			});

		},
		minLength: 2,
		select: function(event, ui) {
		/*if (!window['{$callback}']) {
		{$callback}=ui.item;
} else*/

		{$callback}(ui.item.id);

	}
}).css({'z-index' : '2', 'margin' : '10px'});
JS;
      }

      public static function setfocus($id = false) {
         if (!$id)
            return;
         self::$on_load[] = <<< JS

$('{$id}').focus();

JS;

      }

      public static function gmap($selector, $address, $title) {
         $address = ereg_replace("[\r\t\n\v]", ", ", $address);
         self::$_onload[] = <<<JS

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

      }

      public static function tabs($id, $options = array()) {
         $defaults = array('noajax' => false);
         extract(array_merge($defaults, $options));
         $content = '';

         if ($noajax)
            $content .= <<<JS
{ select: function(event, ui) {
         url = $.data(ui.tab, 'load.tabs');
        if( url ) {
            location.href = url;
            return false;
        }
        return false;
    },
    selected:-1
    }
JS;
         self::$_onload[] = "$('" . $id . "').tabs(" . $content . ");";
      }

      public static function render() {
         $content = "\n<script language=\"javascript\"  type=\"text/javascript\"><!--\n";
         if (!empty(self::$_beforeload))
            $content .= implode(';', self::$_beforeload);
         if (!empty(self::$_onload))
            $content .= '$(function() {' . implode(';', self::$_onload) . '});';
         echo minify_js($content) . "\n--></script>\n";
      }

      public static function onLoad($js = false) {
         if (!$js)
            return;
         self::register($js, self::$_onload);
      }

      public static function register($js = false, &$var = array()) {
         if (!$js)
            return;
         if (empty($var))
            $var = &self::$_beforeload;
         if (!is_array($js) && is_string($js))
            $js = array($js);
         $js = array_map(function($v) {
               return str_replace(array('<script>', '</script>'), '', $v);
            }, $js);
         $var = array_merge($var, $js);
      }
   }
