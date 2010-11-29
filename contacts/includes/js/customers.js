/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 11:25 PM
 * To change this template use File | Settings | File Templates.
 */
$(function() {
	var account = {};
	var branch = {};
	var customer;
	var currentBranch;
	var newCustomer = function() {
	$("#tabs").link(account).link(branch);
	$("#tabs").link(account,{
		acc_br_address: { twoWay: false, name:  "address"},
		acc_email: { twoWay: false, name:  "email"}
	});
	$("#tabs").link(branch,{
		br_address: { twoWay: false, name:  "address"},
		email: { twoWay: false, name:  "acc_email"},
		phone: { twoWay: false, name:  "acc_phone"},
		contact_name: { twoWay: false, name:  "acc_contact_name"},
		phone2: { twoWay: false, name:  "acc_phone2"},
		fax:{ twoWay: false, name:  "acc_fax"}
	});
	var tabs = $("#tabs");
		tabs.find("a").bind('click.linking',function() {
		if ($(this).attr('href') == "#tabs-2") {
			unlinkAccounts();
			$(this).unbind('click.linking');
		}
		if ($(this).attr('href') == "#tabs-3") {
	unlinkBranches();
			$(this).unbind('click.linking');
			}
	});
		var unlinkAccounts = function() {
			tabs.unlink(account);
					tabs.link(branch);
				};
		var unlinkBranches = function() {
			tabs.unlink(branch);
		} 
	};
	newCustomer();
	var resetHighlights = function () {
		$(".ui-state-highlight").removeClass("ui-state-highlight");
		btnCustomer.hide();
		btnCancel.button('option', 'label', 'New Customer');
		feildsChanged = 0;
		window.onbeforeunload = function () {
			return null
		};
	};
	var changeBranch = function (data) {
		$.each(data, function(key, value) {
			$("input[name=\'" + key + "\'],textarea[name=\'" + key + "\']").val(value).data('init', value);
			$("select[name=\'" + key + "\']").val(value);
		});
		currentBranch = data;
	};
	var revertState = function() {
		resetHighlights();
		getCustomer(customer.id);
	};
    var resetState = function () {
	    resetHighlights();
	    $("#tabs input, #tabs textarea").empty();
	    getCustomer(0);

    };
    var lastXhr;
    var BranchList = $("#branchList").change(function(event) {
                var data = customer.branches[$(this).val()];
	    changeBranch(data);

            });
	var addBranches = function(data) {
		$.each(data, function(key, value) {
			BranchList.append('<option value="' + value.branch_code + '">' + value.br_name + '</option>');
		});
		changeBranch(customer.branches[customer.defaultBranch]);

	};

	var getBranches = function() {

	};
    var stateModified = function(feild) {

		btnCancel.button('option', 'label', 'Cancel Changes').show();
	    if (customer.id == null || customer.id == 0) {
		    btnCustomer.button("option", "label", "Save New Customer").show();

	    } else {
		    btnCustomer.button("option", "label", "Save Changes").show();
	    }
		window.onbeforeunload = function() {
			return "Continue without saving changes?";
		};
	    
	};
    var getCustomer = function(id) {
        $.post("search.php",
        {id: id}, function(data) {
	        customer = data;


            BranchList.empty();
            $.each(data, function(i, data) {
                if (i == 'accounts') {
                    $.each(data, function(key, value) {
                        $("input[name=\'acc_" + key + "\'],textarea[name=\'acc_" + key + "\']").val(value).data('init', value);
                        $("select[name=\'" + key + "\']").val(value);
                    })
                }
                if (i == 'branches') {
	            

                    addBranches(data);
	            
                }
                else {
                   $("input[name=\'" + i + "\'],textarea[name=\'" + i + "\']").val(data).data('init', data);
                  $("select[name=\'" + i + "\']").val(data).data('init',data);
                }
            });

        }, 'json')
    };
    $("#customers").autocomplete({
        source: function(request, response) {
            lastXhr = $.getJSON("search.php", request, function(data, status, xhr) {
                if (xhr === lastXhr) {
                    response(data);
                }
            })
        },
        minLength: 2,
        select: function(event, ui) {
            getCustomer(ui.item.id);
        }}).css({"z-index" : "2", "margin" : "10px"});
	var feildsChanged = 0;
	$("#tabs").delegate(".tablestyle_inner td :nth-child(1)", "change", function() {
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
	var btnCancel = $("#btnCancel").button().click(function() {
		if (customer.id == 0) resetState();
		if(feildsChanged>0) revertState();
		else resetState();
		return false;
	});
	var btnCustomer = $("#btnCustomer").button().click(function() {
resetHighlights();

	});

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
	var btnBranch = $("#addBranch").button().click(function() {
		$.post('search.php', {branch_code: 0}, function(data) {

			customer.branches +=data;
console.log(cusomter);
		addBranches(branch);
		},'json');
	});

resetState();

});
