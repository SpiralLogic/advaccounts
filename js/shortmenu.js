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
	var sidemenuOn = function() {
		sidemenu.accordion("enable");
		sidemenu.find("h3").undelegate("a", "click");
	};
	var sidemenuOff = function() {
		sidemenu.accordion("disable");
		sidemenu.find("h3").delegate("a", "click", function() {
			$("#results").detach();
			$("#wrapper").show();
		}
				)
	};
	function createInput(url, id) {
		input = "<input type='text' value='' size='14' maxlength='18'"
				+ " id='" + id + "'"
				+ " data-url='" + url + "'"
				+ ">";
		return input;
	}
	var previous;
	var ajaxRequest;
	var SearchboxThis = undefined;
	var Searchboxtimeout;
	$("#search").delegate("a", "click",
	                     function(event) {
		                     sidemenuOff();
		                     event.preventDefault();
		                     $("#search input").trigger('blur');
		                     previous = $(this);
		                     $(this).replaceWith(createInput($(this).attr('href'), $(this).attr('id')));
		                     $('#' + $(this).attr('id')).focus();
		                     return false;
	                     });
	$("#search input").live("change blur keyup", function(event) {
		 SearchboxThis = $(this);
		if (ajaxRequest && event.type == 'keyup') {
			ajaxRequest.abort();
		}
		if (event.type != "blur" && SearchboxThis.val().length > 1 && event.which != 13 && event.which < 123) {
			window.clearTimeout(Searchboxtimeout);
			Searchboxtimeout = window.setTimeout(doSearch, 1000); 
		}
		if (event.type != 'keyup') {
			SearchboxThis.replaceWith(previous);
			sidemenuOn();
		}
	});
	function doSearch() {
		term = SearchboxThis.val();

		loader = $("#loader").show();
		ajaxRequest = $.post(
				SearchboxThis.data("url"),
		{ ajaxsearch: term, limit: true },
		                    function(data) {
			                    var content = $('#wrapper', data).attr("id", "results");
			                    $("#results").remove();
			                    $("#wrapper", document).hide().before(content);
			                    loader = $("#loader").hide();
		                    }
				);
	}

});
