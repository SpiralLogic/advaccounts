(function(window, undefined)
{
	if (window.AdvAcc == undefined) {
		window.AdvAcc = {};
	}
	var AdvAcc = function()
	{
		var btnCancel,btnSupplier,feildsChanged = 0,tabs,
				resetHighlights = function()
				{
					$(".ui-state-highlight").removeClass("ui-state-highlight");
					btnSupplier.hide();
					btnCancel.button('option', 'label', 'New');
					feildsChanged = 0;
					window.onbeforeunload = function ()
					{
						return null
					};
				},
				revertState = function ()
				{
					$('.ui-state-highlight').each(function()
												  {
													  $(this).val($(this).data('init'))
												  });
					resetHighlights();
				},
				resetState = function ()
				{
					$("#tabs input, #tabs textarea").empty();
					$("#supplier").val('');
					Supplier.fetch(0);
				},
				msgbox = $('#msgbox').ajaxError(function(event, request, settings)
												{
													var status = {
														status: false,
														message: "Request failed: " + request + "<br>" + settings
													};
													showStatus(status);
												}),
				showStatus = function(status)
				{
					msgbox.empty();
					if (status.status) {
						msgbox.addClass('note_msg').text(status.message);
					} else {
						msgbox.addClass('err_msg').text(status.message);
					}
				},
				getContactLog = function()
				{
					var data = {
						contact_id: Supplier.get().id,
						type: "C"
					};
					$.post('contact_log.php', data, function(data)
					{
						setContactLog(data);
					}, 'json')
				},
				setContactLog = function(data)
				{
					var logbox = $("[name='messageLog']").val('');
					var str = '';
					$.each(data, function(key, message)
					{
						str += '[' + message['date'] + '] Contact: ' + message['contact_name'] + "\nMessage:  " + message['message']
								+ "\n\n";
					});
					logbox.val(str);
				},
				setFormValue = function(id, value)
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
					}
					else {
						element.val(value).data('init', '');
					}
				};
		return this;
	};
	return window.AdvAcc = AdvAcc();
}(window));

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
			return btn;
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

var Supplier = function ()
{
	var supplier;
	var transactions = $('#transactions');
	return {
		setValues: function(data)
		{
			if (data.contact_log != undefined) setContactLog(data.contact_log);
			if (data.transacionts != undefined) transactions.empty().append(data.transactions);
			data = data.supplier;
			supplier = data;
			$.each(data, function(i, data)
			{
				setFormValue(i, data);
			});
			resetHighlights();
		},
		fetch: function(id)
		{
			loader.show();
			$.post("suppliers.php", {id: id}, function(data)
			{
				Supplier.setValues(data);
				loader.hide();
			}, 'json')
		},
		set: function(key, value)
		{
			supplier[key] = value;


		},
		get: function()
		{
			return supplier
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
	if (Supplier.get().id == null || Supplier.get().id == 0) {
		btnSupplier.button("option", "label", "Save New").show();
	} else {
		btnSupplier.button("option", "label", "Save Changes").show();
	}
	Supplier.set(fieldname, feild.val());
	window.onbeforeunload = function()
	{
		return "Continue without saving changes?";
	};
}
$(function()
  {
	  tabs = $("#tabs");
	  loader = $("<div></div>").hide().attr('id', 'loader').prependTo('#content');
	  btnCancel = $("#btnCancel").button().click(function()
												 {
													 (  ! feildsChanged > 0) ? resetState() : revertState();
													 return false;
												 });
	  btnSupplier = $("#btnSupplier").button().click(function(event)
													 {
														 Branches.Save();
														 return false;
													 });
	  $("[name='messageLog']").keypress(function(event)
										{
											event.stopImmediatePropagation();
											return false;
										});
	  tabs.delegate("#tabs :input", "change", function(event)
	  {
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
																	  contact_id: Supplier.get().id,
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