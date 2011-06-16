<?php

   class JS {

      private static $_javascript = array();

      private function __construct() {
      }

      public static function autocomplete($id, $callback, $url = false, $options = array()) {
         global $js_lib;
         $afterSource = (isset($options['afterSource'])) ? $options['afterSource'] : '';
         if (!$url)
            $url = $_SERVER['PHP_SELF'];
         $js_lib[] = <<< JS
$(function() {
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
}).css({'z-index' : '2', 'margin' : '10px'}); });
JS;
      }

      public static function setfocus($id = false) {
         if (!$id)
            return;
         global $js_lib;
         $js_lib[] = <<< JS
$(function() {
$('{$id}').focus();
})
JS;

      }

      public static function gmap($selector, $address, $title) {
         $address = ereg_replace("[\r\t\n\v]", ", ", $address);
         $script = <<<JS
$(function() {
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
});
JS;
         JS::$_javascript[] = $script;
      }
   }
