Adv.extend({

	resetHighlights:function () {
		$(".ui-state-highlight").removeClass("ui-state-highlight");
		Adv.o.custsearch.prop('disabled', false);
		Adv.btnCustomer.hide();
		Adv.btnCancel.text('New Customer');
		Branches.btnBranchAdd();

		Adv.fieldsChanged = 0;
		Adv.Events.onLeave();

	},
	revertState:function () {
		$('.ui-state-highlight').each(function () {
			$(this).val($(this).data('init'))
		});
		Adv.resetHighlights();
	},
	resetState:function () {
		$("#tabs0 input, #tabs0 textarea").empty();
		$("#customer").val('');
		Customer.fetch(0)
	},
	stateModified:function (feild) {
		if (feild.prop('disabled')) return;
		Adv.o.custsearch.prop('disabled', true);
		Adv.btnCancel.text('Cancel Changes').show();
		var fieldname = feild.addClass("ui-state-highlight").attr('name');
		$("[name='" + fieldname + "']").each(function () {
			$(this).addClass("ui-state-highlight");
		});
		if (Customer.get().id == null || Customer.get().id == 0)
			{
				Adv.btnCustomer.text("Save New Customer").show();
			} else
			{
				Adv.btnCustomer.text("Save Changes").show();
			}
		Customer.set(fieldname, feild.val());
		Adv.Events.onLeave("Continue without saving changes?");
	}
});
Adv.extend({
	getContactLog:function () {
		var data = {
			contact_id:Customer.get().id,
			type:"C"
		};
		$.post('contact_log.php', data, function (data) {
			Adv.setContactLog(data);
		}, 'json')
	},
	setContactLog:function (data) {
		var logbox = $("[id='messageLog']").val('');
		var str = '';
		$.each(data, function (key, message) {
			str
			 += '[' + message['date'] + '] Contact: ' + message['contact_name'] + "\nMessage:  " + message['message'] + "\n\n";
		});
		logbox.val(str);
	}
});
var Contacts = function () {
	var blank, count = 0, adding = false, btn = $("#btnContact"), $Contacts = $("#Contacts");
	$('#contact').template('contact');
	return {
		list:function () {
			return list;
		},
		empty:function () {
			count = 0;
			adding = false;
			$Contacts.empty();
			return this;
		},
		init:function (data) {
			Contacts.empty();
			Contacts.addMany(data);
			Contacts.New();
		},
		add:function (data) {
			Contacts.addMany(data);
		},
		addMany:function (data) {
			var contacts = [];
			$.each(data, function ($k, $v) {
				if (!blank && $v.id == 0)
					{blank = $v;}
				if ($v.id !== 0)
					{contacts[contacts.length] = $v;}
			});
			$.tmpl('contact', contacts).appendTo($Contacts);
		},
		setval:function (key, value) {
			key = key.split('-');
			if (value !== undefined)
				{Customer.get().contacts[key[1]][key[0]] = value;}
		},
		New:function () {
			$.tmpl('contact', blank).appendTo($Contacts);
		}

	};
}();
var Branches = function () {
	var current = {}, list = $("#branchList"), btn = $("#addBranch");
	return {
		adding:false,
		init:function () {
			btn.hide().removeClass('invis');
			list.change(function () {
				if (!$(this).val().length)
					{return;}
				var ToBranch = Customer.get().branches[$(this).val()];
				Branches.change(ToBranch);
			})
		},
		empty:function () {
			list.empty();
			return this;
		},
		add:function (data) {
			if (data.branch_id === undefined)
				{
					var toAdd;
					$.each(data, function (key, value) {
						toAdd += '<option value="' + value.branch_id + '">' + value.br_name + '</option>';
					});
					list.append(toAdd);
				} else
				{
					list.append('<option value="' + data.branch_id + '">' + data.br_name + '</option>');
				}
			return this;
		},
		get:function () {
			return current
		},
		setval:function (key, value) {
			current[key] = value;
			Customer.get().branches[current.branch_id][key] = value;
		},
		change:function (data) {
			if (typeof data !== 'object')
				{
					data = Customer.get().branches[data];
				}
			$.each(data, function (key, value) {
				Adv.Forms.setFormValue('br_' + key, value);
			});
			Adv.resetHighlights();
			list.val(data.branch_id);
			current = data;
			if (current.branch_id > 0)
				{
					list.find("[value=0]").remove();
					delete Customer.get().branches[0];
					Branches.adding = false;
					Branches.btnBranchAdd();
				}
		},
		New:function () {
			$.post('search.php', {branch_id:0, id:Customer.get().id}, function (data) {
				data = data.branch;
				Branches.add(data).change(data);
				Customer.get().branches[data.branch_id] = data;
				btn.hide();
				Branches.adding = true;
			}, 'json');
		},
		btnBranchAdd:function () {
			btn.unbind('click');
			if (!Branches.adding && current.branch_id > 0 && Customer.get().id > 0)
				{
					btn.text('Add New Branch').one('click',
					 function (event) {
						 Branches.New();
						 Branches.adding = true;
						 return false
					 }).show();
				} else
				{
					(current.branch_id > 0) ? btn.show() : btn.hide();
				}
			return false;
		}
	};
}();
var Accounts = function () {
	return {
		change:function (data) {
			$.each(data, function (id, value) {
				Adv.Forms.setFormValue('acc_' + id, value);
			})
		}
	}
}();
var Customer = function () {
	var customer, transactions = $('#transactions'), searchBox = $("#customer"), customerIDs = $("#customerIDs"), $customerID = $("#name").attr('autocomplete',
	 'off');
	return {
		init:function () {
			Customer.getFrames();
			$customerID.autocomplete({
				source:function (request, response) {
					var lastXhr = $.getJSON('#', request, function (data, status, xhr) {
						if (xhr === lastXhr)
							{
								response(data);
							}
					});
				},
				select:function (event, ui) {
					Customer.fetch(ui.item);
					return false;
				},
				focus:function () {
					return false;
				},
				autoFocus:false, delay:10, 'position':{
					my:"left middle",
					at:"right top",
					of:$customerID,
					collision:"none"
				}

			});

		},
		setValues:function (content) {
			if (!content.customer)
				{return;}
			customer = data = content.customer;
			if (id)
				{
					Adv.o.tabs.tabs1.tabs('option', 'disabled', []);
				} else
				{
					Adv.o.tabs.tabs1.tabs('option', 'disabled',true );
				}
			if (content.contact_log !== undefined)
				{
					Adv.setContactLog(content.contact_log);
				}
			if (content.transactions !== undefined)
				{
					transactions.empty().append(content.transactions);
				}
			Contacts.init(data.contacts);
			Branches.empty().add(data.branches).change(data.branches[data.defaultBranch]);
			Accounts.change(data.accounts);
			(customer.id) ? Customer.hideSearch() : Customer.showSearch();
			$.each(customer, function (i, data) {
				if (i !== 'contacts' && i !== 'branches' && i !== 'accounts')
					{
						Adv.Forms.setFormValue(i, data);
					}
			});
			Adv.resetHighlights();
		},
		hideSearch:function () {
			$customerID.autocomplete('disable');
		},
		showSearch:function () {
			$customerID.autocomplete('enable');
		},
		fetch:function (item) {
			if (typeof(item) === "number")
				{item = {id:item};}
			$.post("customers.php", {"id":item.id}, function (data) {
				Customer.setValues(data);
			}, 'json');
			Customer.getFrames();
		},
		getFrames:function() {
if (!Customer.get().id) return;
			var $invoiceFrame = $('#invoiceFrame'), urlregex = /[\w\-\.:/=&!~\*\'"(),]+/g,
						 $invoiceFrameSrc = $('#invoiceFrame').data('src').match(urlregex)[0] + '?frame=1';
						$invoiceFrame.attr('src', $invoiceFrameSrc + '&customer_id=' + Customer.get().id);


		},
		Save:function () {
			Branches.btnBranchAdd();
			Adv.btnCustomer.prop('disabled', true);
			$.post('customers.php', Customer.get(), function (data) {
				if (data.status)
					{
						Adv.showStatus(data.status);
						Adv.btnCustomer.prop('disabled', false);
						if (!data.status.status)
							{return;}
					}
				Adv.resetHighlights();
				Branches.adding = false;
				Customer.setValues(data);
			}, 'json');
		},
		set:function (key, value) {
			if (key.substr(0, 4) == ('acc_'))
				{
					customer.accounts[key.substr(4)] = value;
				} else
				{
					if (key.substr(0, 3) == ('br_'))
						{
							Branches.setval(key.substr(3), value);
						} else
						{
							if (key.substr(0, 4) == ('con_'))
								{
									Contacts.setval(key.substr(4), value);
								} else
								{
									customer[key] = value;
								}
						}
				}
		},
		get:function () {
			return customer
		}
	}
}();
$(function () {


	Adv.extend({
		tabs:$("#tabs0"),
		$shortcutTabs:$("#tabs1").tabs({ select:function (event, ui) {
			var url = $.data(ui.tab, 'load.tabs');
			if (url)
				{location.href = url + Customer.get().id;}
			return false;
		},
			selected:-1
		}),
		accFields:$("[name^='acc_']"),
		btnCustomer:$("#btnCustomer").click(function () {
			Customer.Save();
			return false;
		}),
		btnCancel:$("#btnCancel").click(function () {
			(	!Adv.fieldsChanged > 0) ? Adv.resetState() : Adv.revertState();
			return false;
		}),

		btnUseShipAddress:$("#useShipAddress").click(function () {
			Adv.accFields.each(function () {
				var newVal = $("[name='br_" + $(this).attr('name').substr(4) + "']").val();
				$(this).val(newVal).trigger('change');
				Customer.set($(this).attr('name'), newVal);

			});
			return false;
		}),
		ContactLog:$("#contactLog").hide()
	});
	Adv.o.custsearch = $('#custsearch');
	$("#addLog").click(function (event) {
		event.stopImmediatePropagation();
		Adv.ContactLog.dialog("open");
		return false;
	});

	Adv.ContactLog.dialog({
		autoOpen:false,
		show:"slide",
		resizable:false,
		hide:"explode",
		modal:true,
		width:700,
		maxWidth:700,
		buttons:{
			"Ok":function () {
				var data = {
					contact_name:Adv.ContactLog.find("[name='contact_name']").val(),
					contact_id:Customer.get().id,
					message:Adv.ContactLog.find("[name='message']").val(),
					type:"C"
				};
				Adv.ContactLog.dialog('disable');
				$.post('contact_log.php', data, function (data) {
					Adv.ContactLog.find(':input').each(function () {
						Adv.ContactLog.dialog('close').dialog('enable');
					});
					Adv.ContactLog.find("[name='message']").val('');
					Adv.setContactLog(data);
				}, 'json')
			},
			Cancel:function () {
				Adv.ContactLog.find("[name='message']").val('');
				$(this).dialog("close");
			}
		}
	}).click(function () {
		 $(this).dialog("open");
	 });
	Adv.tabs.delegate(":input", "change keypress", function (event) {
		if ($(this).attr('name') == 'messageLog' || $(this).attr('name') == 'branchList')
			{
				return;
			}
		event.stopImmediatePropagation();
		Adv.fieldsChanged++;
		if ($(this).data('init') == $(this).val())
			{
				$(this).removeClass("ui-state-highlight");
				Adv.fieldsChanged--;
				if (Adv.fieldsChanged === 0)
					{
						Adv.resetHighlights();
					}
				return;
			}
		Adv.stateModified($(this));

	})
	$("[name='messageLog']").keypress(function (event) {
		event.stopImmediatePropagation();
		return false;
	});
	$("#id").prop('disabled', true);
	Branches.init();
	Customer.init();
});
