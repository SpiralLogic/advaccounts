/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 11:25 PM
 * To change this template use File | Settings | File Templates.
 */
$(function() {
	var customer = new Object();
	var resetState = function () {
		window.onbeforeunload = function () {
			return null
		}
		$(".ui-state-highlight").removeClass("ui-state-highlight");
	}
	var getCustomer = function(id) {

		$.getJSON("search.php",
		          {id: id}, function(data) {
			customer = data;
			$.each(data, function(i, data) {
				$("input[name=\'" + i + "\'],textarea[name=\'" + i + "\']").val(data).data(i,data);

			});
			var btnCustomerCaption = (
			                         data.id == 0) ? 'Save New Customer' : 'Save Customer Updates';
			$('#btnCustomer').button("option","label",btnCustomerCaption);
		});
	}
	 $("#customers").autocomplete({
		                             source: "search.php",
		                             minLength: 2,
		                             select: function(event, ui) {
			                             getCustomer(ui.item.id);
		                             }}).css("z-index", "2");

	$("#addLog").button().click(function() {
		$('#contactLog').dialog("open")
	});
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
	                        }).click(function() {
		$(this).dialog("open");
	});


	$("#tabs").delegate(".tablestyle_inner td :nth-child(1)", "change", function(event) {
		if ($(this).data(event.target.name)==$(this).val()) {
			$(this).removeClass("ui-state-highlight");
			return;
		}
		$(this).addClass("ui-state-highlight");
		$("#btnCancel").show();
		window.onbeforeunload = function() {
			return "Continue without saving changes?"
		}
		if (customer.id == null || customer.id == 0) {
			customer.id = 0;
			$("#btnCustomer").button("option","label","Save New Customer");
		}
	});
	$("#btnCancel").button().click(function() {
		getCustomer(customer);
		resetState();
		$(this).hide();
		return false;
	}).hide();
	$("#btnCustomer").button().click(function() {resetState()});


});