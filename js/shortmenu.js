/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
var loader;
$(function() {
    var sidemenu = $("#sidemenu").draggable().accordion({
        autoHeight: false,
        event: "mouseover"
    }).fadeTo("slow", .75).hover(
                                function() {
                                    $(this).fadeTo("fast", 1);
                                },
                                function() {
                                    $(this).fadeTo("fast", .75);
                                });
	var sidemenuOn = function() { sidemenu.accordion("enable");
        sidemenu.find("h3 a").unbind('click');
    };

    var sidemenuOff = function() {
        
        sidemenu.accordion("disable");

        sidemenu.find("h3 a").click(function() {

        $("#results").detach()
        $("#wrapper").show(); }
    )}


	function createInput(url, id) {

		input = "<input type='text' value='' size='14' maxlength='18'"
				+ " id='" + id + "'"
				+ " data-url='" + url + "'"
				+ ">";
		return input;
	}

	var previous;
	var ajaxRequest;
 
	$("#search").delegate("a", "click",
	                     function(event) {
                             sidemenuOff();
		                     event.preventDefault();
		                     $("#search input").trigger('blur');
		                     previous = $(this);
		                     $(this).replaceWith(createInput($(this).attr('href'), $(this).attr('id')));
		                     $('#' + $(this).attr('id')).focus();
		                     return false;
	                     })

	$("#search input").live("change blur keyup", function(event) {
		var term = $(this).val();
		if (event.type != "blur" && term.length > 1 && event.which != 13 && event.which < 123) {
			if (ajaxRequest && event.type == 'keyup') {
				ajaxRequest.abort();
			}
			loader = $("#loader").show();
			ajaxRequest = $.post(
					$(this).data("url"),
			{ ajaxsearch: term, limit: true },
			                    function(data) {
				                    var content = $('#wrapper', data).attr("id","results");
                                    $("#results").remove();
				                    $("#wrapper", document).hide().before(content);
				                    loader = $("#loader").hide();
			                    }
					);
		}
		if (event.type != 'keyup') {
			$(this).replaceWith(previous);
            sidemenuOn();
		}
   /*     $("#SearchOrders").live("click", function(event) {
            event.preventDefault();
            return false;
            })*/

	})

});
