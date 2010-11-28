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
        $(".ui-state-highlight").each(
                                     function() {
                                         if (feildsChanged>0 ) this.value = $(this).data('init');
                                     }).removeClass("ui-state-highlight");
        if (customer.id==0) {
        $("#tabs input, #tabs textarea").empty();
        }
        window.onbeforeunload = function () {
            return null
        };
        btnCustomer.hide();
        btnCancel.button('option', 'label', 'New Customer');
    };
    var lastXhr;
    var BranchList = $("#branchList").change(function(event) {
                //   $.post('search.php', {branch_code: $(this).val()}, function (data) {
                var data = customer.branches[$(this).val()];

                $.each(data, function(key, value) {
                    $("input[name=\'" + key + "\'],textarea[name=\'" + key + "\']").val(value).data('init', value);
                    $("select[name=\'" + key + "\']").val(value);
                });

                // }, 'json')
            });
    
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
                    $.each(data, function(key, value) {
                        BranchList.append('<option value="' + value.branch_code + '">' + value.br_name + '</option>');
                    });

                }
                else {

                   $("input[name=\'" + i + "\'],textarea[name=\'" + i + "\']").val(data).data('init', data);
                  $("select[name=\'" + i + "\']").val(data).data('init',data);
                }

            });

            if (customer.id == 0) {
                btnCustomer.button("option", "label", 'Save New Customer').hide();
            }
            else {
                btnCancel.button("option", "label", 'New Customer').show();
            }


        }, 'json')
    }
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
var feildsChanged=0;
    $("#tabs").delegate(".tablestyle_inner td :nth-child(1)", "change", function(event) {
        feildsChanged++;
        if ($(this).data('init') == $(this).val()) {
            $(this).removeClass("ui-state-highlight");
            feildsChanged--;
            if (feildsChanged == 0) {
                btnCancel.button('option', 'label', 'New Customer');
                btnCustomer.hide()
            }
            return;
        }
        $(this).addClass("ui-state-highlight");
        btnCancel.button('option','label','Cancel').show();
        btnCustomer.button("option", "label", "Save Changes").show();
        window.onbeforeunload = function() {
            return "Continue without saving changes?"
        }
        if (customer.id == null || customer.id == 0) {
            customer.id = 0;
            btnCustomer.button("option", "label", "Save New Customer").show();
        }
    });
    var btnCancel = $("#btnCancel").button().click(function() {

        if (feildsChanged == 0)
        resetState(); resetState();
        return false;
    });
    var btnCustomer = $("#btnCustomer").button().click(function() {
        if (feildsChanged ==0) resetState();

    });
resetState();
});