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
var setFormValue = function(id, value) {
	var element = $("input[name=\'" + id + "\'],textarea[name=\'" + id + "\']");
	if (element.length > 0) {
		if (value && value.length > 0) {
			element.val(value).data('init', value);
		} else {
			element.val('');
		}
		return;
	}
	element = $("select[name=\'" + id + "\']");
	if (value && value.length > 0) {
		element.val(value).data('init', value);
	} else {
		value = element.find('option:first').attr('selected', true);
		element.data('init', value);
	}
};
var Branches = function() {
	var current = {}, list = $("#branchList"),adding = false, btn = $("#addBranch").button();
	return {
		list :function () {
			return list;
		},
		add : function (data) {
			$.each(data, function(key, value) {
				list.append('<option value="' + value.branch_code + '">' + value.br_name + '</option>');
			});
		},
		change:function (data) {
			$.each(data, function(key, value) {
				setFormValue(key, value);
			});
			resetHighlights();
			list.val(data.branch_code);
			current = data;
			if (current.branch_code > 0) {
				list.find("[value=0]").remove();
				adding = false;
				Branches.btnBranchAdd();
			}
		},
		New: function() {
			$.post('search.php', {branch_code: 0}, function(data) {
				Branches.add([data]);
				Branches.change(data);
				btn.hide();
			}, 'json');
			adding = false;
		},
		Save: function() {
			btn.unbind('click');
			var data = $('form').serializeArray();
			$.merge(data, $.makeArray({ name: "submit", value: true}));
			$.post('customers.php', data, function(data) {
				resetHighlights();
				adding = false;
				current = data;
				if (adding) {
					Branches.add([data]);
					customer.branches[data.branch_code] = data;
					adding = !adding;
				}
				Branches.btnBranchAdd();
				//$("#msgbox").prepend("Customer branch updated");
			}, 'json');
		},
		btnBranchAdd : function() {
			btn.unbind('click');
			if (!adding && current.branch_code > 0 && customer.id > 0) {
				btn.button('option', 'label', 'Add New Branch').one('click',
				                                                   function(event) {
					                                                   event.stopImmediatePropagation();
					                                                   Branches.New();
					                                                   return false
				                                                   }).show();
			} else {
				if (current.branch_code > 0) {
					btn.show();
				} else {
					btn.hide();
				}
			}
			adding = true;
			return btn;
		},
		btnBranchSave : function() {
			btn.button('option', 'label', 'Save New Branch').show().one('click', function(event) {
				event.stopImmediatePropagation();
				Branches.Save();
				return false
			});
			return btn;
		}
	};
}();
function unlinkAccounts() {
	tabs.unlink(account);
	tabs.link(branch);
}
function unlinkBranches() {
	tabs.unlink(branch);
}
resetHighlights = function() {
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
}
function Customer(item) {
	getCustomer(item.id);
}
function getCustomer(id) {
	loader.show();
	$.post("customers.php", {id: id}, function(data) {
		customer = data;
		Branches.list().empty();
		$.each(data, function(i, data) {
			if (i == 'accounts') {
				$.each(data, function(id, value) {
					setFormValue('acc_' + id, value);
				})
			} else {
				if (i == 'branches') {
					Branches.add(data);
					Branches.change(customer.branches[customer.defaultBranch]);
				} else {
					setFormValue(i, data);
				}
			}
		});
		/* if (customer.id == 0) {
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
		 } else {
		 tabs.find("a").each(function() {
		 if ($(this).attr('href') == "#tabs-2") {
		 unlinkAccounts();
		 $(this).unbind('click.linking');
		 }
		 if ($(this).attr('href') == "#tabs-3") {
		 unlinkBranches();
		 $(this).unbind('click.linking');
		 }
		 })
		 }*/
		resetHighlights();
		loader.hide();
	}, 'json')
}
getCustomers = getCustomer;
function stateModified(feild) {
	btnCancel.button('option', 'label', 'Cancel Changes').show();
	fieldname = feild.addClass("ui-state-highlight").attr('name');
	$("[name='" + fieldname + "']").each(function() {
		$(this).val(feild.val()).addClass("ui-state-highlight");
	});
	if (customer.id == null || customer.id == 0) {
		btnCustomer.button("option", "label", "Save New Customer").show();
	} else {
		btnCustomer.button("option", "label", "Save Changes").show();
	}
	Branches.btnBranchSave();
	window.onbeforeunload = function() {
		return "Continue without saving changes?";
	};
}
$(function() {
	tabs = $("#tabs");
	loader = $("<div></div>").hide().attr('id', 'loader').prependTo('#content');
	btnCancel = $("#btnCancel").button().click(function() {
		resetState();
		return false;
	});
	btnCustomer = $("#btnCustomer").button().click(function(event) {
		event.stopImmediatePropagation();
		Branches.Save();
		return false;
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
		stateModified($(this));
	});
	resetState();
	$("#addLog").button().click(function(event) {
		event.stopImmediatePropagation();
		$('#contactLog').dialog("open");
		return false;
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
				var data= $("#contactLog").serialize();
				$.post('contact_log.php',data,function(data) {
					console.log(data);
				},'json')
			},
			Cancel: function() {
				$(this).dialog("close");
			}
		}
	}).click(function() {
		$(this).dialog("open");
	});
});