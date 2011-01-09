var loader;
var btnCancel;
var btnCustomer;
var feildsChanged = 0;
//var account = {};
//var branch = {};
var tabs;
var setFormValue = function(id, value) {
	var element = $("[name=\'" + id + "\']");
	if (element.find('option').length > 0) {
		if (element.val() == null || String(value).length == 0) {
			element.find('option:first').attr('selected', true);
			element.data('init', element.val());
		} else {
			element.val(value).data('init', value);
		}
		return;
	}
	if (String(value).length > 0) {
		element.val(value).data('init', value);
	}
	else {
		element.val(value).data('init', '');
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
		setval: function (key, value) {
			current[key] = value;
			Customer.get().branches[current.branch_code][key] = value;
		},
		change:function (data) {
			$.each(data, function(key, value) {
				setFormValue('br_' + key, value);
			});
			resetHighlights();
			list.val(data.branch_code);
			current = data;
			if (current.branch_code > 0) {
				list.find("[value=0]").remove();
				delete Customer.get().branches[0];
				adding = false;
				Branches.btnBranchAdd();
			}
		},
		New: function() {
			$.post('search.php', {branch_code: 0, debtor_no: Customer.get().id}, function(data) {
				Branches.add([data]);
				Branches.change(data);
				Customer.get().branches[data.branch_code] = data;
				btn.hide();
				adding = true;
			}, 'json');
		},
		Save: function() {
			btn.unbind('click');
			$.post('customers.php', Customer.get(), function(data) {
				resetHighlights();
				adding = false;
				Customer.setValues(data);
			}, 'json');
		},
		btnBranchAdd : function() {
			btn.unbind('click');
			if (!adding && current.branch_code > 0 && Customer.get().id > 0) {
				btn.button('option', 'label', 'Add New Branch').one('click',
				                                                   function(event) {
					                                                   event.stopImmediatePropagation();
					                                                   Branches.New();
					                                                   adding = true;
					                                                   return false
				                                                   }).show();
			} else {
				if (current.branch_code > 0) {
					btn.show();
				} else {
					btn.hide();
				}
			}
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
resetHighlights = function() {
	$(".ui-state-highlight").removeClass("ui-state-highlight");
	btnCustomer.hide();
	btnCancel.button('option', 'label', 'New Customer');
	Branches.btnBranchAdd();
	feildsChanged = 0;
	window.onbeforeunload = function () {
		return null
	};
};
function revertState() {
	$('.ui-state-highlight').each(function() {
		$(this).val($(this).data('init'))
	});
	resetHighlights();
}
function resetState() {
	$("#tabs input, #tabs textarea").empty();
	$("#customer").val('');
	Customer.fetch(0);
}
var getContactLog = function() {
	var data = {
		contact_id: Customer.get().id,
		type: "C"
	};
	$.post('contact_log.php', data, function(data) {
		setContactLog(data);
	}, 'json')
};
var setContactLog = function(data) {
	var logbox = $("[name='messageLog']").val('');
	var str = '';
	$.each(data, function(key, message) {
		str += '[' + message['date'] + '] Contact: ' + message['contact_name'] + "\nMessage:  " + message['message']
				+ "\n\n";
	});
	logbox.val(str);
};
var Customer = function (item) {
	var customer;
	return {
		setValues: function(data) {
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
					} else {
						setFormValue(i, data);
					}
				}
			});
			Branches.change(data.branches[data.defaultBranch]);
			resetHighlights();
			getContactLog();
		},
		fetch: function(id) {
			loader.show();
			$.post("customers.php", {id: id}, function(data) {
				Customer.setValues(data);
				loader.hide();
			}, 'json')
		},
		set: function(key, value) {
			if (key.substr(0, 4) == ('acc_')) {
				customer.accounts[key.substr(4)] = value;
			}
			else {
				if (key.substr(0, 3) == ('br_')) {
					Branches.setval(key.substr(3), value);
				}
				else {
					customer[key] = value;
				}
			}
		},
		get: function() {
			return customer
		}
	}
}();
function stateModified(feild) {
	btnCancel.button('option', 'label', 'Cancel Changes').show();
	var fieldname = feild.addClass("ui-state-highlight").attr('name');
	$("[name='" + fieldname + "']").each(function() {
		$(this).val(feild.val()).addClass("ui-state-highlight");
	});
	if (Customer.get().id == null || Customer.get().id == 0) {
		btnCustomer.button("option", "label", "Save New Customer").show();
	} else {
		btnCustomer.button("option", "label", "Save Changes").show();
	}
	Customer.set(fieldname, feild.val());
	window.onbeforeunload = function() {
		return "Continue without saving changes?";
	};
}
$(function() {
	tabs = $("#tabs");
	loader = $("<div></div>").hide().attr('id', 'loader').prependTo('#content');
	btnCancel = $("#btnCancel").button().click(function() {
		(  ! feildsChanged > 0) ? resetState() : revertState();
		return false;
	});
	btnCustomer = $("#btnCustomer").button().click(function(event) {
		event.stopImmediatePropagation();
		Branches.Save();
		return false;
	});
	$("[name='messageLog']").keypress(function(event) {
		event.stopImmediatePropagation();
		return false;
	});
	tabs.delegate("#tabs :input", "change", function(event) {
		if ($(this).attr('name') == 'messageLog' || $(this).attr('name') == 'branchList') {
			return;
		}
		event.stopImmediatePropagation();
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
	tabs.delegate(".tablestyle_inner td :nth-child(1)", "keydown", function(event) {
		if (feildsChanged > 0) {
			return;
		}
		$(this).trigger('change');
	});
	resetState();
	$("#addLog").button().click(function(event) {
		event.stopImmediatePropagation();
		$('#contactLog').dialog("open");
		return false;
	});
	Branches.list().change(function(event) {
		var data = Customer.get().branches[$(this).val()];
		Branches.change(data);
	});
	var ContactLog = $("#contactLog").hide().dialog({
		autoOpen: false,
		show: "slide",
		resizable: false,
		hide: "explode",
		modal: true,
		width: 700,
		maxWidth:700,
		buttons: {
			"Ok": function() {
				var data = {
					contact_name: ContactLog.find("[name='contact_name']").val(),
					contact_id: Customer.get().id,
					message: ContactLog.find("[name='message']").val(),
					type: "C"
				};
				ContactLog.dialog('disable');
				$.post('contact_log.php', data, function(data) {
					ContactLog.find(':input').each(function() {
						ContactLog.dialog('close').dialog('enable');
					});
					ContactLog.find("[name='message']").val('');
					setContactLog(data);
				}, 'json')
			},
			Cancel: function() {
				ContactLog.find("[name='message']").val('');
				$(this).dialog("close");
			}
		}
	}).click(function() {
		$(this).dialog("open");
	});
});