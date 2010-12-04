<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:01 PM
 * To change this template use File | Settings | File Templates.
 */
 
class JS {

    private static $_javascript = array();
    private function __construct(){}
    public static function autocomplete($id,$callback) {
        global $js_lib;


        $js_lib[] = "<script>$(function() {
       $('#{$id}').autocomplete({
		source: function(request, response) {
			lastXhr = $.getJSON('search.php', request, function(data, status, xhr) {
				if (xhr === lastXhr) {
					response(data);
				}
			})
		},
		minLength: 2,
		select: function(event, ui) {
		get{$callback}(ui.item.id);
	}
}).css({'z-index' : '2', 'margin' : '10px'}); });</script>";

    }
}
