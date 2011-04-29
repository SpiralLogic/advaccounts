(function(window, undefined)
{
	var Adv = {
		loader: false,
		fieldsChanged: 0,
		tabs: 0

	};
	(function()
	{
		var private = 'private';
		this.method = function()
		{
			return 'private method';
		}
		this.getter = function()
		{
			return 'got ' + private + ' property';
		}
		this.setter = function(value)
		{
			this.fieldsChanged++;
		}
	}).apply(Adv);
	window.Adv = Adv;
})(window);


var loader;
var btnCancel;
var btnCustomer;
var btnContact;
var feildsChanged = 0;

var tabs;

var setFormValue = function(id, value)
{
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
	} else {
		element.val(value).data('init', '');
	}
};
var Contacts = function()
{
	var current = {},list = $("#contactList"),adding = false,btn = $("#btnContact").button();
	return {
		list:function()
		{
			return list;
		},
		add:function(data)
		{
			$.each(data, function(key, value)
			{
				list.append('<option value="' + value.id + '">' + value.name + '</option>');
			});
		},
		setval: function (key, value)
		{
			current[key] = value;
			Customer.get().contacts[current.id][key] = value;
		},
		change:function(data)
		{
			$.each(data, function(key, value)
			{
				setFormValue('con_' + key, value);
			});
			resetHighlights();
			list.val(data.id);
			current = data;
			if (current.id > 0) {
				list.find("[value=0]").remove();
				delete Customer.get().contacts[0];
				Contacts.btnContactAdd();
				adding = false;
			}
		},
		New: function()
		{
			var newCurrent = current;
			$.each(current, function(k, v)
			{
				newCurrent[k] = '';
			});
			newCurrent.id = 0;
			Contacts.add([newCurrent]);
			Customer.get().contacts[0] = newCurrent;
			Contacts.change(newCurrent);

			btn.hide();
			adding = true;
		},
		btnContactAdd : function()
		{
			btn.unbind('click');
			if (!adding && current.id > 0 && Customer.get().id > 0) {
				btn.button('option', 'label', 'Add New Contact').one('click',
																	 function(event)
																	 {
																		 event.stopImmediatePropagation();
																		 Contacts.New();
																		 adding = true;
																		 return false
																	 }).show();
			} else {
				if (current.id > 0) {
					btn.show();
				} else {
					btn.hide();
				}
			}
			return false;
		}
	};
}();
var Branches = function()
{
	var current = {}, list = $("#branchList"),adding = false, btn = $("#addBranch").button();
	return {
		list :function ()
		{
			return list;
		},
		add : function (data)
		{
			$.each(data, function(key, value)
			{
				list.append('<option value="' + value.branch_code + '">' + value.br_name + '</option>');
			});
		},
		setval: function (key, value)
		{
			current[key] = value;
			Customer.get().branches[current.branch_code][key] = value;
		},
		change:function (data)
		{
			$.each(data, function(key, value)
			{
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
		New: function()
		{
			$.post('search.php', {branch_code: 0, debtor_no: Customer.get().id}, function(data)
			{
				Branches.add([data]);
				Branches.change(data);
				Customer.get().branches[data.branch_code] = data;
				btn.hide();
				adding = true;
			}, 'json');
		},
		Save: function()
		{
			btn.unbind('click');
			$.post('customers.php', Customer.get(), function(data)
			{
				resetHighlights();
				adding = false;
				Customer.setValues(data);
				showStatus(data.status);
			}, 'json');
		},
		btnBranchAdd : function()
		{
			btn.unbind('click');
			if (!adding && current.branch_code > 0 && Customer.get().id > 0) {
				btn.button('option', 'label', 'Add New Branch').one('click',
																	function(event)
																	{
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
			return false;
		},
		btnBranchSave : function()
		{
			btn.button('option', 'label', 'Save New Branch').show().one('click', function(event)
			{
				event.stopImmediatePropagation();
				Branches.Save();
				return false
			});
			return btn;
		}
	};
}();
resetHighlights = function()
{
	$(".ui-state-highlight").removeClass("ui-state-highlight");
	btnCustomer.hide();
	btnCancel.button('option', 'label', 'New Customer');
	Branches.btnBranchAdd();
	Contacts.btnContactAdd();
	feildsChanged = 0;
	window.onbeforeunload = function ()
	{
		return null
	};
};
function revertState()
{
	$('.ui-state-highlight').each(function()
								  {
									  $(this).val($(this).data('init'))
								  });
	resetHighlights();
}
function resetState()
{
	$("#tabs0 input, #tabs0 textarea").empty();
	$("#customer").val('');
	Customer.fetch(0);

}
var msgbox = $('#msgbox').ajaxError(function(event, request, settings)
									{
										var status = {
											status: false,
											message: "Request failed: " + request + "<br>" + settings
										};
										showStatus(status);
									});
var showStatus = function(status)
{
	msgbox.empty();
	if (status.status) {
		msgbox.addClass('note_msg').text(status.message);
	} else {
		msgbox.addClass('err_msg').text(status.message);
	}
}
var getContactLog = function()
{
	var data = {
		contact_id: Customer.get().id,
		type: "C"
	};
	$.post('contact_log.php', data, function(data)
	{
		setContactLog(data);
	}, 'json')
};
var setContactLog = function(data)
{
	var logbox = $("[name='messageLog']").val('');
	var str = '';
	$.each(data, function(key, message)
	{
		str += '[' + message['date'] + '] Contact: ' + message['contact_name'] + "\nMessage:  " + message['message'] + "\n\n";
	});
	logbox.val(str);
};
var Customer = function (item)
{
	var customer;
	var transactions = $('#transactions');
	return {
		setValues: function(data)
		{
			if (data.contact_log != undefined) {
				setContactLog(data.contact_log);
			}
			if (data.transacionts != undefined) {
				transactions.empty().append(data.transactions);
			}
			data = data.customer;
			customer = data;
			Branches.list().empty();
			Contacts.list().empty();
			$.each(data, function(i, data)
			{
				if (i == 'accounts') {
					$.each(data, function(id, value)
					{
						setFormValue('acc_' + id, value);
					})
				} else {
					if (i == 'branches') {
						Branches.add(data);
					} else {
						if (i == 'contacts') {
							Contacts.add(data);
						} else {
							setFormValue(i, data);
						}
					}
				}
			});
			Branches.change(data.branches[data.defaultBranch]);
			Contacts.change(data.contacts[data.defaultContact]);
			resetHighlights();
		},
		fetch: function(id)
		{
			loader.show();
			$.post("customers.php", {id: id}, function(data)
			{
				Customer.setValues(data);
				loader.hide();
				$('#customer').focus();
			}, 'json')
		},
		set: function(key, value)
		{
			console.log(value);
			if (key.substr(0, 4) == ('acc_')) {
				customer.accounts[key.substr(4)] = value;
			} else {
				if (key.substr(0, 3) == ('br_')) {
					Branches.setval(key.substr(3), value);
				} else {
					if (key.substr(0, 4) == ('con_')) {
						Contacts.setval(key.substr(4), value);
					} else {
						customer[key] = value;
					}
				}
			}
		},
		get: function()
		{
			return customer
		}
	}
}();
function stateModified(feild)
{
	btnCancel.button('option', 'label', 'Cancel Changes').show();
	var fieldname = feild.addClass("ui-state-highlight").attr('name');
	$("[name='" + fieldname + "']").each(function()
										 {
											 $(this).val(feild.val()).addClass("ui-state-highlight");
										 });
	if (Customer.get().id == null || Customer.get().id == 0) {
		btnCustomer.button("option", "label", "Save New Customer").show();
	} else {
		btnCustomer.button("option", "label", "Save Changes").show();
	}
	Customer.set(fieldname, feild.val());
	window.onbeforeunload = function()
	{
		return "Continue without saving changes?";
	};
}
$(function()
  {
	  tabs = $("#tabs0");
	  var $useShipAddress = $("[name='useShipAddress']").click(function()
															   {
																   if ($(this).attr('checked')) {
																	   $("[name*='acc_']").attr('disabled', true).each(function()
																													   {
																														   var newVal = $("[name='br_" + $(this).attr('name').substr(4) + "']").val();
																														   $(this).val(newVal);
																														   Customer.set($(this).attr('name'), newVal);
																													   });

																   } else {
																	   $("[name*='acc_']").attr('disabled', false);
																   }
															   });
	  loader = $("<div></div>").hide().attr('id', 'loader').prependTo('#content');
	  btnCancel = $("#btnCancel").button().click(function()
												 {
													 (  ! feildsChanged > 0) ? resetState() : revertState();
													 return false;
												 });
	  btnCustomer = $("#btnCustomer").button().click(function(event)
													 {
														 Branches.Save();
														 return false;
													 });

	  $("[name='messageLog']").keypress(function(event)
										{
											event.stopImmediatePropagation();
											return false;
										});
	  tabs.delegate("#tabs0 :input", "change", function(event)
	  {
		  if ($(this).attr('name') == 'messageLog' || $(this).attr('name') == 'branchList' || $(this).attr('name') == 'contactList') {
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
		  if ($useShipAddress.attr('checked') && $(this).attr('name').substr(0, 3) == 'br_') {
			  var feildname = 'acc_' + $(this).attr('name').substr(3);
			  setFormValue(feildname, $(this).val());
			  Customer.set(feildname, $(this).val());
		  }
	  });
	  tabs.delegate(".tablestyle_inner td :nth-child(1)", "keydown", function(event)
	  {
		  if (feildsChanged > 0) {
			  return;
		  }
		  $(this).trigger('change');
	  });
	  resetState();
	  $("#addLog").button().click(function(event)
								  {
									  event.stopImmediatePropagation();
									  $('#contactLog').dialog("open");
									  return false;
								  });
	  Branches.list().change(function(event)
							 {
								 var data = Customer.get().branches[$(this).attr('value')];
								 Branches.change(data);
							 });
	  Contacts.list().change(function(event)
							 {
								 var data = Customer.get().contacts[$(this).attr('value')];
								 Contacts.change(data);
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
															  "Ok": function()
															  {
																  var data = {
																	  contact_name: ContactLog.find("[name='contact_name']").val(),
																	  contact_id: Customer.get().id,
																	  message: ContactLog.find("[name='message']").val(),
																	  type: "C"
																  };
																  ContactLog.dialog('disable');
																  $.post('contact_log.php', data, function(data)
																  {
																	  ContactLog.find(':input').each(function()
																									 {
																										 ContactLog.dialog('close').dialog('enable');
																									 });
																	  ContactLog.find("[name='message']").val('');
																	  setContactLog(data);
																  }, 'json')
															  },
															  Cancel: function()
															  {
																  ContactLog.find("[name='message']").val('');
																  $(this).dialog("close");
															  }
														  }
													  }).click(function()
															   {
																   $(this).dialog("open");
															   });
  });