var Adv;
(function(window, undefined) {
	var Adv = {
		loader: false,
		fieldsChanged: 0,
		tabs: 0

	};
	(function() {
		var private = 'private';
		this.method = function() {
			return 'private method';
		}
		this.getter = function() {
			return 'got ' + private + ' property';
		}
		this.setter = function(value) {
			this.fieldsChanged++;
		}
		this.setFormValue= function (id, value) {
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
		}

	}).apply(Adv);
	window.Adv = Adv;
})(window);

//var Adv.loader, btnCancel, btnCustomer, btnContact, tabs,Adv.feildsChanged = 0;

var Contacts = function() {
	var current = {},adding = false,btn = $("#btnContact").button(),contactCell = $('#contactcell-').detach();
	return {
		list:function() {
			return list;
		},
		empty:function() {

			return this;
		},
		add:function(data) {
			var finaldata = [];
			$.each(data, function(key, value) {
				       finaldata[finaldata.length] =
						       contactCell.clone().attr({'id': 'contactcell-' + key,'contactid':key}).find("[name='contactname']").attr('name', 'contactname-' + key).text(data[key]['name'])
								       .end().find('input').each(function() {
									                                 var $value = $(this).attr('name').substr(4);
									                                 $value = $value.substr(0, $value.length - 1);
									                                 $(this).val(value[$value]).attr('name', $(this).attr('name') + key)
								                                 }).end();
			       });
			$("#contactplace").empty();
			$.each(finaldata, function(k, v) {
				       v.css({"clear":"none","float":"left"}).appendTo("#contactplace")
			       });
			$.each($.grep(finaldata, function(n, i) {
				              return ((i) % 4 == 0)
			              }), function(k, v) {
				       v.css("clear", "both")
			       });
			return this;
		},
		setval: function (key, value) {
			current[key] = value;
			Customer.get().contacts[current.id][key] = value;
		},
		change:function(data) {
		},
		New: function() {

		},
		btnContactAdd : function() {
			return false;
		}
	};
}();
var Branches = function() {
	var current = {}, list = $("#branchList"),adding = false, btn = $("#addBranch").button();
	return {
		list :function () {
			return list;
		},
		empty:function() {
			list.empty();
			return this;
		},
		add : function (data) {
			$.each(data, function(key, value) {
				       list.append('<option value="' + value.branch_code + '">' + value.br_name + '</option>');
			       });
			return this;
		},
		get: function() { return current},
		setval: function (key, value) {
			current[key] = value;
			Customer.get().branches[current.branch_code][key] = value;
		},
		change:function (data) {
			if (typeof data !== 'object') {
				data = Customer.get().branches[data];
			}
			$.each(data, function(key, value) {
				       Adv.setFormValue('br_' + key, value);
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
				       Branches.add(data).change(data);
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
				       showStatus(data.status);
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
				(current.branch_code > 0) ? btn.show() : btn.hide();
			}
			return false;
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
var Accounts = function() {
	return {
		change: function(data) {
			console.log(data);
			$.each(data, function(id, value) {
				       Adv.setFormValue('acc_' + id, value);
			       })
		}
	}
}();
function resetHighlights() {
	$(".ui-state-highlight").removeClass("ui-state-highlight");
	btnCustomer.hide();
	btnCancel.button('option', 'label', 'New Customer');
	Branches.btnBranchAdd();
	Contacts.btnContactAdd();
	Adv.feildsChanged = 0;
	window.onbeforeunload = function () {
		return null;
	};
}
function revertState() {
	$('.ui-state-highlight').each(function() {
		                              $(this).val($(this).data('init'))
	                              });
	resetHighlights();
}
function resetState() {
	$("#tabs0 input, #tabs0 textarea").empty();
	$("#customer").val('');
	Customer.fetch(0);
}
var msgbox = $('#msgbox').ajaxError(function(event, request, settings) {
	                                    var status = {
		                                    status: false,
		                                    message: "Request failed: " + request + "<br>" + settings
	                                    };
	                                    showStatus(status);
                                    });
function showStatus(status) {
	msgbox.empty();
	if (status.status) {
		msgbox.addClass('note_msg').text(status.message);
	} else {
		msgbox.addClass('err_msg').text(status.message);
	}
}
function getContactLog() {
	var data = {
		contact_id: Customer.get().id,
		type: "C"
	};
	$.post('contact_log.php', data, function(data) {
		       setContactLog(data);
	       }, 'json')
}
function setContactLog(data) {
	var logbox = $("[name='messageLog']").val('');
	var str = '';
	$.each(data, function(key, message) {
		       str += '[' + message['date'] + '] Contact: ' + message['contact_name'] + "\nMessage:  " + message['message'] + "\n\n";
	       });
	logbox.val(str);
}
var Customer = function () {
	var customer;
	var transactions = $('#transactions');
	return {
		setValues: function(data, quiet) {
			customer = data = data.customer;
			if (data.contact_log !== undefined) {
				setContactLog(data.contact_log);
			}
			if (data.transactions !== undefined) {
				transactions.empty().append(data.transactions);
			}
			Contacts.add(data.contacts);
			if (quiet === true) {
				return;
			}
			Branches.empty().add(data.branches).change(data.branches[data.defaultBranch]);
			Accounts.change(data.accounts);
			$.each(customer, function(i, data) {
				       if (i !== 'contacts' && i !== 'branches' && i !== 'accounts') {
					       Adv.setFormValue(i, data);
				       }
			       });
			resetHighlights();
		},
		fetch: function(id) {
			Adv.loader.show();
			$.post("customers.php", {"id": id}, function(data) {
				       Customer.setValues(data);
				       Adv.loader.hide();
			       }, 'json')
		},
		set: function(key, value) {
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
	  var tabs = $("#tabs0"),accFields = $("[name*='acc_']").attr('disabled', true);
	  var $useShipAddress = $("[name='useShipAddress']").click(function() {
		                                                           if ($(this).attr('checked')) {
			                                                           accFields.attr('disabled', true).each(function() {
				                                                                                                 var newVal = $("[name='br_" + $(this).attr('name').substr(4) + "']").val();
				                                                                                                 $(this).val(newVal);
				                                                                                                 console.log($(this).attr('name').substr(4) + ': ' + newVal);
				                                                                                                 Customer.set($(this).attr('name'), newVal);
			                                                                                                 });

		                                                           } else {
			                                                           accFields.attr('disabled', false);
		                                                           }
	                                                           });
	  Adv.loader = $("<div></div>").hide().attr('id', 'loader').prependTo('#content');
	  btnCancel = $("#btnCancel").button().click(function() {
		                                             (  ! Adv.feildsChanged > 0) ? resetState() : revertState();
		                                             return false;
	                                             });
	  btnCustomer = $("#btnCustomer").button().click(function(event) {
		                                                 Branches.Save();
		                                                 return false;
	                                                 });
	  $("[name='messageLog']").keypress(function(event) {
		                                    event.stopImmediatePropagation();
		                                    return false;
	                                    });
	  tabs.delegate(":input", "change", function(event) {
		                if ($(this).attr('name') == 'messageLog' || $(this).attr('name') == 'branchList') {
			                return;
		                }
		                event.stopImmediatePropagation();
		                Adv.feildsChanged++;
		                if ($(this).data('init') == $(this).val()) {
			                $(this).removeClass("ui-state-highlight");
			                Adv.feildsChanged--;
			                if (Adv.feildsChanged == 0) {
				                resetHighlights();
			                }
			                return;
		                }
		                stateModified($(this));
		                if ($useShipAddress.attr('checked') && $(this).attr('name').substr(0, 3) == 'br_') {
			                var feildname = 'acc_' + $(this).attr('name').substr(3);
			                Adv.setFormValue(feildname, $(this).val());
			                Customer.set(feildname, $(this).val());
		                }
	                });
	  tabs.delegate(".tablestyle_inner td :nth-child(1)", "keydown", function(event) {
		                if (Adv.feildsChanged > 0) {
			                return;
		                }
		                $(this).trigger('change');
	                });
	  $("#addLog").button().click(function(event) {
		                              event.stopImmediatePropagation();
		                              $('#contactLog').dialog("open");
		                              return false;
	                              });
	  Branches.list().change(function(event) {
		                         var data = Customer.get().branches[$(this).attr('value')];
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
	  $.post('customers.php', {id:$('[name="id"]').val()}, function(data) {
		         Customer.setValues(data, true);
		         Branches.change(Customer.get().defaultBranch);
		         $('#customer').focus();
	         }, 'json');
  });