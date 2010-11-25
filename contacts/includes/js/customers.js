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
			$(".ui-state-highlight").each(function() {
				this.value = $(this).data('init');
			}).removeClass("ui-state-highlight");
			window.onbeforeunload = function () {
			return null
		}
		btnCancel.hide();
	}
	var getCustomer = function(id) {
		$.getJSON("search.php",
		{id: id}, function(data) {
			customer = data;
			$.each(data, function(i, data) {
				if (i == 'accounts') {
					$.each(data, function(key, value) {
						$("input[name=\'acc_" + key + "\'],textarea[name=\'acc_" + key + "\']").val(value).data('init', value);
					})
				} else {
					if (i == 'branches') {
						var string = '';
						$.each(data, function(key, value) {
							string += '<option value="' + value.branch_code + '">' + value.br_name + '</option>';
						})
						$("#branchList").html(string)
					} else {
						$("input[name=\'" + i + "\'],textarea[name=\'" + i + "\']").val(data).data('init', data);

					}
				}
			});

			var btnCustomerCaption = (customer.id == 0) ? 'Save New Customer' : 'New Customer';
			btnCustomer.button("option", "label", btnCustomerCaption).show();
		});
	}

	$("#customers").autocomplete({
		source: "search.php",
		minLength: 2,
		select: function(event, ui) {
			getCustomer(ui.item.id);
		}}).css({"z-index" : "2", "margin" : "10px"});

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
	var feildsChanged = 0;
	$("#tabs").delegate(".tablestyle_inner td :nth-child(1)", "change", function(event) {
		feildsChanged++;
		if ($(this).data('init') == $(this).val()) {
			$(this).removeClass("ui-state-highlight");
			feildsChanged--;
			if (feildsChanged == 0) { btnCustomer.hide() }
			return;
		}
		$(this).addClass("ui-state-highlight");
		btnCancel.show();
		btnCustomer.button("option","label","Save Changes");
		window.onbeforeunload = function() {
			return "Continue without saving changes?"
		}
		if (customer.id == null || customer.id == 0) {
			customer.id = 0;
			$("#btnCustomer").button("option", "label", "Save New Customer").show();
		}
	});
	var btnCancel = $("#btnCancel").button().click(function() {
			resetState()
	});
	var btnCustomer = $("#btnCustomer").button().click(function() {
		resetState();
	}).hide();

});