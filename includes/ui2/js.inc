<?php

class JS {

    private static $_javascript = array();

    private function __construct() {
    }

    public static function autocomplete($id, $callback, $url = false) {
        global $js_lib;
        if (!$url) $url = $_SERVER['PHP_SELF'];
        $js_lib[] = <<< JS
$(function() {
       $('#{$id}').autocomplete({
		source: function(request, response) {
			lastXhr = $.getJSON('{$url}', request, function(data, status, xhr) {
				if (xhr === lastXhr) {
					response(data);
				}
			})
		},
		minLength: 2,
		select: function(event, ui) {

		{$callback}(ui.item.id);

	}
}).css({'z-index' : '2', 'margin' : '10px'}); });
JS;
    }

    public static function setfocus($id = false) {
        if (!$id) return;
        global $js_lib;
        $js_lib[] = <<< JS
$(function() {
$('{$id}').focus();
})
JS;

    }
}
