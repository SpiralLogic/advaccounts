/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
$(function() {
	$("#shortmenu").accordion({
		                          autoHeight: false,
		                          event: "mouseover"
	                          }).fadeTo("slow", .75);
	$("#shortmenu").draggable().hover(function() {
		$(this).fadeTo("fast", 1);
	},
	                                  function() {
		                                  $(this).fadeTo("fast", .75);
	                                  });
});