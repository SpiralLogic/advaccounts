var loader;
var lastXhr;
var customer;
var btnCancel;
var btnCustomer;
var btnBranch;
var feildsChanged = 0;
var account = {};
var branch = {};
var tabs;
var Branches = {
	list :function () {

		return	list = $("#branchList");
	},
	add : function (data) {

		$.each(data, function(key, value) {
			Branches.list().append('<option value="' + value.branch_code + '">' + value.br_name + '</option>');
		});
	},
	change:function (data) {
		$.each(data, function(key, value) {
			$("input[name=\'" + key + "\'],textarea[name=\'" + key + "\']").val(value).data('init', value);
			$("select[name=\'" + key + "\']").val(value);
		});
		resetHighlights();
		Branches.list().val(data.branch_code);
		Branches.current = data;
		if (Branches.current.branch_code > 0) {
			Branches.list().find("[value=0]").remove();
			Branches.adding = false;
			Branches.btnBranchAdd();
		}
	},
	adding : false,
	btnBranchAdd : function() {
		var btn = $("#addBranch").button();
		if (!Branches.adding && Branches.current.branch_code > 0 && customer.id > 0) {
			btn.button('option', 'label', 'Add New Branch').click(
			                                                     function() {
				                                                     $.post('search.php', {branch_code: 0},
				                                                           function(data) {
					                                                           Branches.add([data]);
					                                                           Branches.change(data);
					                                                           btn.hide();
				                                                           }, 'json');
				                                                     btn.unbind('click');
				                                                     Branches.adding = false;
			                                                     }).show();
		}
		if (Branches.current.branch_code > 0) {
			btn.show();
		} else {
			btn.hide();
		}
		Branches.adding = true;
		return btn;
	},
	btnBranchSave : function() {
		var btn = $("#addBranch").button();
		btn.button('option', 'label', 'Save New Branch').show().unbind().click(function() {
			var data = $('form').serializeArray();
			$.merge(data, $.makeArray({ name: "submit", value: true}));

			$.post('search.php', data, function(data) {
				//Branches.update(data);
				resetHighlights();
				Branches.adding = false;
				Branches.current = data;
				Branches.add([data]);
				Branches.btnBranchAdd();
				customer.branches[data.branch_code] = data;
				$("#msgbox").prepend("Customer branch updated");
			}, 'json');
		});
		return btn;
	},
	current: {}
};

function unlinkAccounts() {
	tabs.unlink(account);
	tabs.link(branch);
}
;
function unlinkBranches() {
	tabs.unlink(branch);
}
function resetHighlights() {
	$(".ui-state-highlight").removeClass("ui-state-highlight");
	btnCustomer.hide();
	btnCancel.button('option', 'label', 'New Customer');
	Branches.btnBranchAdd();
	feildsChanged = 0;
	window.onbeforeunload = function () {
		return null
	};
}

function revertState() {
	resetHighlights();
	getCustomer(customer.id);
}
function resetState() {
	$("#tabs input, #tabs textarea").empty();
	getCustomer(0);
	resetHighlights();
}
function getCustomer(id) {
	loader.show();
	$.post("search.php",
	{id: id}, function(data) {
		customer = data;
		Branches.list().empty();
		$.each(data, function(i, data) {
			if (i == 'accounts') {
				$.each(data, function(key, value) {
					$("input[name=\'acc_" + key + "\'],textarea[name=\'acc_" + key + "\']").val(value).data('init', value);
					$("select[name=\'" + key + "\']").val(value);
				})
			}
			if (i == 'branches') {
				Branches.add(data);
				Branches.change(customer.branches[customer.defaultBranch]);
			}
			else {
				$("input[name=\'" + i + "\'],textarea[name=\'" + i + "\']").val(data).data('init', data);
				$("select[name=\'" + i + "\']").val(data).data('init', data);
			}
		});
		console.log(customer);
		if (customer.id == 0) {
			tabs.link(account).link(branch);
			tabs.link(account, {
				acc_br_address: { twoWay: false, name:  "address"},
				acc_email: { twoWay: false, name:  "email"}
			});
			tabs.link(branch, {
				br_address: { twoWay: false, name:  "address"},
				email: { twoWay: false, name:  "acc_email"},
				phone: { twoWay: false, name:  "acc_phone"},
				contact_name: { twoWay: false, name:  "acc_contact_name"},
				phone2: { twoWay: false, name:  "acc_phone2"},
				fax:{ twoWay: false, name:  "acc_fax"}
			});

			tabs.find("a").bind('click.linking', function() {
				if ($(this).attr('href') == "#tabs-2") {
					unlinkAccounts();
					$(this).unbind('click.linking');
				}
				if ($(this).attr('href') == "#tabs-3") {
					unlinkBranches();
					$(this).unbind('click.linking');
				}
			});
		}
		else {
			tabs.find("a").each(function() {
				if ($(this).attr('href') == "#tabs-2") {
					unlinkAccounts();
					$(this).unbind('click.linking');
				}
				if ($(this).attr('href') == "#tabs-3") {
			unlinkBranches();
			$(this).unbind('click.linking');
			}})
	}
	loader.hide();
	}, 'json')
}
getCustomers = getCustomer;
function stateModified(feild) {
	btnCancel.button('option', 'label', 'Cancel Changes').show();
	if (customer.id == null || customer.id == 0) {
		btnCustomer.button("option", "label", "Save New Customer").show();
	} else {
		btnCustomer.button("option", "label", "Save Changes").show();
	}
	if (Branches.current.branch_code == 0) {
		Branches.btnBranchSave();
	}
	window.onbeforeunload = function() {
		return "Continue without saving changes?";
	};
}
$(function() {
	tabs = $("#tabs");
	loader = $("#loader").show();
	btnCancel = $("#btnCancel").button().click(function() {
		resetState();
		return false;
	});
	btnCustomer = $("#btnCustomer").button().click(function() {
		resetHighlights();
	});

	tabs.delegate(".tablestyle_inner td :nth-child(1)", "change", function() {
		feildsChanged++;
		if ($(this).data('init') == $(this).val()) {
			$(this).removeClass("ui-state-highlight");
			feildsChanged--;
			if (feildsChanged == 0) {
				resetHighlights();
			}
			return;
		}
		$(this).addClass("ui-state-highlight");
		stateModified();
	});
	resetState();
	$("#addLog").button().click(function() {
		$('#contactLog').dialog("open")
	});
	Branches.list().change(function(event) {
		var data = customer.branches[$(this).val()];
		Branches.change(data);
	});
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
});