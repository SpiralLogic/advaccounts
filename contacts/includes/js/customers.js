/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 11:25 PM
 * To change this template use File | Settings | File Templates.
 */
$(function() {
	$("#customers").autocomplete({
		source: "search.php",
		minLength: 2,
		select: function(event, ui) {
			$.getJSON("search.php",
			{id: ui.item.id}, function(data) {
				$.each(data, function(i, data) {
					$("input[name=\'" + i + "\'],textarea[name=\'" + i + "\']").val(data);
				});
				var btnCustomerCaption = (data.id == 0) ? 'Confirm new customer.' : 'Update Customer';
				$('#btnCustomer span').text(btnCustomerCaption);
			});
		}}).css("z-index", "2");
	$("#search button").button();
//	 Show  contact log modal dialog

	$("#contactLog").dialog({
		autoOpen: false,
		show: "slide",
		resizable: false,
		hide: "explode",
		modal: true,
		width: 700,
		maxWidth:700,
		buttons: {
			"Ok": function() {
				$(this).dialog("close");
			},
			Cancel: function() {
				$(this).dialog("close");
			}
		}
	}).dialog("open");
$("#")



});