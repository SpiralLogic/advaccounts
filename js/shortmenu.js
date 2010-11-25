/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
$(function() {
	$("#sidemenu").accordion({
		autoHeight: false,
		event: "mouseover"
	}).fadeTo("slow", .75);
	$("#sidemenu").draggable().hover(
	                                function() {
		                                $(this).fadeTo("fast", 1);
	                                },
	                                function() {
		                                $(this).fadeTo("fast", .75);
	                                });

	function createInput(url, id) {

		input = "<input type='text' value='' maxlength='18'"
				+ " id='" + id + "'"
				+ " data-url='" + url + "'"
				+ ">";
		return input;
	}

	var previous;
	var ajaxRequest;
	$("#search").delegate("a", "click",
	                     function(event) {
		                     event.preventDefault();
		                     $("#search input").trigger('blur');
		                     previous = $(this);
		                     $(this).replaceWith(createInput($(this).attr('href'), $(this).attr('id')));
		                     $('#' + $(this).attr('id')).focus();

		                     return false;
	                     });

	$("#search input").live('change blur keyup', function(event) {
		var term = $(this).val();
		if (event.type != 'blur' && (term.length > 1)) {
			if (ajaxRequest && event.type == 'keyup') {
				ajaxRequest.abort();
			}
			ajaxRequest = $.post(
					$(this).data("url"),
			{ ajaxsearch: term },
			                    function(data) {
				                    var content = $('#_page_body', data);
				                    $("#_page_body", document).replaceWith(content);
			                    }
					);
		}
		if (event.type != 'keyup') {
			$(this).replaceWith(previous);}});});