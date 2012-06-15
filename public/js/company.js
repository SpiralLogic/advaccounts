Adv.extend({
             revertState:function (formid)
             {
               var form = document.getElementsByTagName('form')[0];
               form.reset();
               Adv.o.companysearch.prop('disabled', false);
               Adv.btnConfirm.hide();
               Adv.btnCancel.attr('name', 'new').text('New');
               Branches.btnBranchAdd();
               Adv.Forms.resetHighlights();
             },
             resetState: function ()
             {
               $("#tabs0 input, #tabs0 textarea").empty();
               $("#company").val('');
               Company.fetch(0);
             }
           });
Adv.extend({
             getContactLog:function (id, type)
             {
               var data = {
                 contact_id:id,
                 type:      type
               };
               $.post('contact_log.php', data, function (data)
               {
                 Adv.setContactLog(data);
               }, 'json');
             },
             setContactLog:function (data)
             {
               var logbox = $("[id='messageLog']").val(''), str = '';
               $.each(data, function (key, message)
               {
                 str += '[' + message['date'] + '] Contact: ' + message['contact_name'] + "\nMessage:  " + message['message'] + "\n\n";
               });
               logbox.val(str);
             }
           });
var Contacts = function ()
{
  var blank, count = 0, adding = false, $Contacts = $("#Contacts");
  $('#contact_tmpl').template('contact');
  return {
    list:   function ()
    {
      return list;
    },
    empty:  function ()
    {
      count = 0;
      adding = false;
      $Contacts.empty();
      return this;
    },
    init:   function (data)
    {
      Contacts.empty();
      Contacts.addMany(data);
    },
    add:    function (data)
    {
      Contacts.addMany(data);
    },
    addMany:function (data)
    {
      var contacts = [];
      $.each(data, function ($k, $v)
      {
        if (!blank && $v.id === 0) {
          blank = $v;
        }
        $v._k = $k;
        contacts[contacts.length] = $v;
      });
      $.tmpl('contact', contacts).appendTo($Contacts);
    },
    setval: function (key, value)
    {
      key = key.split('-');
      if (value !== undefined) {
        Company.get().contacts[key[1]][key[0]] = value;
      }
    },
    New:    function ()
    {
      $.tmpl('contact', blank).appendTo($Contacts);
    }
  };
}();
var Branches = function ()
{
  var current = {}, list = $("#branchList"), btn = $("#addBranch");
  return {
    adding:      false,
    init:        function ()
    {
      btn.hide().removeClass('invis');
      list.change(function ()
                  {
                    if (!$(this).val().length) {
                      return;
                    }
                    var ToBranch = Company.get().branches[$(this).val()];
                    Branches.change(ToBranch);
                  })
    },
    empty:       function ()
    {
      list.empty();
      return this;
    },
    add:         function (data)
    {
      if (data.branch_id === undefined) {
        var toAdd;
        $.each(data, function (key, value)
        {
          toAdd += '<option value="' + value.branch_id + '">' + value.br_name + '</option>';
        });
        list.append(toAdd);
      }
      else {
        list.append('<option value="' + data.branch_id + '">' + data.br_name + '</option>');
      }
      return this;
    },
    get:         function ()
    {
      return current
    },
    setval:      function (key, value)
    {
      current[key] = value;
      Company.get().branches[current.branch_id][key] = value;
    },
    change:      function (data)
    {
      if (typeof data !== 'object') {
        data = Company.get().branches[data];
      }
      $.each(data, function (key, value)
      {
        Adv.Forms.setFormDefaults('branch[' + key + ']', value);
      });
      Adv.Forms.resetHighlights();
      list.val(data.branch_id);
      current = data;
      if (current.branch_id > 0) {
        list.find("[value=0]").remove();
        delete Company.get().branches[0];
        Branches.adding = false;
        Branches.btnBranchAdd();
      }
    },
    New:         function ()
    {
      $.post('search.php', {branch_id:0, id:Company.get().id}, function (data)
      {
        data = data.branch;
        Branches.add(data).change(data);
        Company.get().branches[data.branch_id] = data;
        btn.hide();
        Branches.adding = true;
      }, 'json');
    },
    btnBranchAdd:function ()
    {
      btn.unbind('click');
      if (!Branches.adding && current.branch_id > 0 && Company.get().id > 0) {
        btn.text('Add New Branch').one('click',function (event)
                                       {
                                         Branches.New();
                                         Branches.adding = true;
                                         return false
                                       }).show();
      }
      else {
        current.branch_id > 0 ? btn.show() : btn.hide();
      }
      return false;
    }
  };
}();
var Accounts = function ()
{
  return {
    change:function (data)
    {
      $.each(data, function (id, value)
      {
        Adv.Forms.setFormDefaults('accounts[' + id + ']', value);
      })
    }
  }
}();
var Company = function ()
{
  var company, companytype, transactions = $('#transactions'), companyIDs = $("#companyIDs"), $companyID = $("#name").attr('autocomplete', 'off');
  return {
    init:      function ()
    {
      Branches.init();
      $companyID.autocomplete({
                                source:   function (request, response)
                                {
                                  var lastXhr = $.getJSON('#', request, function (data, status, xhr)
                                  {
                                    if (xhr === lastXhr) {
                                      response(data);
                                    }
                                  });
                                },
                                select:   function (event, ui)
                                {
                                  Company.fetch(ui.item);
                                  return false;
                                },
                                focus:    function ()
                                {
                                  return false;
                                },
                                autoFocus:false, delay:10, 'position':{
          my:       "left middle",
          at:       "right top",
          of:       $companyID,
          collision:"none"
        }
                              }).on('paste', function ()
                                    {
                                      var $this = $(this);
                                      window.setTimeout(function () {$this.autocomplete('search', $this.val())}, 1)
                                    });
    },
    setValues: function (content)
    {
      if (!content.company) {
        return;
      }
      company = content.company;
      var data = company;
      var activetabs = (!company.id) ? [0, 1, 2, 3, 4] : [];
      Adv.tabs1.tabs('option', 'disabled', activetabs);
      if (content.contact_log !== undefined) {
        Adv.setContactLog(content.contact_log);
      }
      if (content.transactions !== undefined) {
        transactions.empty().append(content.transactions);
      }
      if (data.contacts) {
        Contacts.init(data.contacts);
      }
      if (data.branches) {
        Branches.empty().add(data.branches).change(data.branches[data.defaultBranch]);
      }
      if (data.accounts) {
        Accounts.change(data.accounts);
      }
      (company.id) ? Company.hideSearch() : Company.showSearch();
      $.each(company, function (i, data)
      {
        if (i !== 'contacts' && i !== 'branches' && i !== 'accounts') {
          Adv.Forms.setFormDefaults(i, data);
        }
      });
      Adv.Forms.resetHighlights();
    },
    hideSearch:function ()
    {
      $companyID.autocomplete('disable');
    },
    showSearch:function ()
    {
      $companyID.autocomplete('enable');
    },
    fetch:     function (item)
    {
      if (typeof(item) === "number") {
        item = {id:item};
      }
      $.post('#', {"id":item.id}, function (data)
      {
        Company.setValues(data);
        Adv.btnCancel.attr('name', 'new').text("New");
      }, 'json');
      Company.getFrames(item.id);
    },
    getFrames: function (id, data)
    {
      if (id === undefined && company.id) {
        id = company.id
      }
      var $invoiceFrame = $('#invoiceFrame'), urlregex = /[\w\-\.:/=&!~\*\'"(),]+/g, $invoiceFrameSrc = $invoiceFrame.data('src').match(urlregex)[0];
      if (!id) {
        return;
      }
      data = data || '';
      $invoiceFrame.load($invoiceFrameSrc, '&' + data + "&frame=1&id=" + id);
    },
    Save:      function ()
    {
      Branches.btnBranchAdd();
      Adv.btnConfirm.prop('disabled', true);
      $.post('#', Company.get(), function (data)
      {
        Adv.btnConfirm.prop('disabled', false);
        if (data.status) {
          Adv.showStatus(data.status);
          if (!data.status.status) {
            return;
          }
        }
        Branches.adding = false;
        Company.setValues(data);
        Adv.revertState();
      }, 'json');
    },
    set:       function (key, value)
    {
      var group, valarray = key.match(/([^[]*)\[(.+)\]/);
      if (valarray !== null) {
        group = valarray[1];
        key = valarray[2];
      }
      switch (group) {
        case 'accounts':
          company.accounts[key] = value;
          break;
        case 'branch':
          Branches.setval(key, value);
          break;
        case 'contact':
          Contacts.setval(key, value);
          break;
        default:
          company[key] = value;
      }
    },
    get:       function ()
    {
      return company
    }
  }
}();
$(function ()
  {
    Adv.extend({
                 tabs:         $("#tabs0"),
                 accFields:    $("[name^='accounts']"),
                 fieldsChanged:0,
                 btnConfirm:   $("#btnConfirm").mousedown(function ()
                                                          {
                                                            Company.Save();
                                                            return false;
                                                          }).hide(),
                 btnCancel:    $("#btnCancel").mousedown(function ()
                                                         {
                                                           ($(this).attr('name') == 'new') ? Adv.resetState() : Adv.revertState();
                                                           return false;
                                                         }),
                 ContactLog:   $("#contactLog").hide(),
                 tabs1:        $("#tabs1").tabs({ select:function (event, ui)
                 {
                   var url = $.data(ui.tab, 'load.tabs');
                   if (url) {
                     location.href = url + Company.get().id;
                   }
                   return false;
                 }, selected:                            -1 })
               });
    $("#useShipAddress").click(function ()
                               {
                                 Adv.accFields.each(function ()
                                                    {
                                                      var name = $(this).attr('name').match(/([^[]*)\[(.+)\]/);
                                                      if (!name) {
                                                        return;
                                                      }
                                                      var newVal = $("[name='branch[" + name[2] + "]']").val();
                                                      if (!newVal || !newVal.length) {
                                                        newVal = $("[name='" + name[0] + "'").val();
                                                      }
                                                      $(this).val(newVal).trigger('change');
                                                      Company.set(name[0], newVal);
                                                    });
                                 return false;
                               });
    Adv.o.companysearch = $('#companysearch');
    $("#addLog").click(function (event)
                       {
                         Adv.ContactLog.dialog("open");
                         return false;
                       });
    Adv.ContactLog.dialog({
                            autoOpen: false,
                            show:     "slide",
                            resizable:false,
                            hide:     "explode",
                            modal:    true,
                            width:    700,
                            maxWidth: 700,
                            buttons:  {
                              "Ok":  function ()
                              {
                                var data = {
                                  contact_name:Adv.ContactLog.find("[name='contact_name']").val(),
                                  contact_id:  Company.get().id,
                                  message:     Adv.ContactLog.find("[name='message']").val(),
                                  type:        "C"
                                };
                                Adv.ContactLog.dialog('disable');
                                $.post('contact_log.php', data, function (data)
                                {
                                  Adv.ContactLog.find(':input').each(function ()
                                                                     {
                                                                       Adv.ContactLog.dialog('close').dialog('enable');
                                                                     });
                                  Adv.ContactLog.find("[name='message']").val('');
                                  Adv.setContactLog(data);
                                }, 'json');
                              },
                              Cancel:function ()
                              {
                                Adv.ContactLog.find("[name='message']").val('');
                                $(this).dialog("close");
                              }
                            }
                          }).click(function ()
                                   {
                                     $(this).dialog("open");
                                   });
    $("#messageLog").prop('disabled', true).css('background', 'white');
    $("[name='messageLog']").keypress(function (event)
                                      {
                                        return false;
                                      });
    Adv.tabs.delegate("input, textarea,select", "change keyup", function ()
    {
      var $this = $(this), $thisname = $this.attr('name'), buttontext;
      if ($thisname === 'messageLog' || $thisname === 'branchList' || Adv.tabs.tabs('option', 'selected') == 4) {
        return;
      }
      Adv.Forms.stateModified($this);
      if (Adv.fieldsChanged > 0) {
        buttontext = (Company.get().id) ? "Save Changes" : "Save New";
        Adv.btnConfirm.text(buttontext).attr('name', 'save').show();
        Adv.btnCancel.attr('name', 'cancel').text('Cancel Changes');
        Adv.o.companysearch.prop('disabled', true);
      }
      else {
        if (Adv.fieldsChanged === 0) {
          Adv.btnConfirm.hide();
          Adv.btnCancel.attr('name', 'new').text("New").show();
        }
      }
      Company.set($thisname, $this.val());
    });
    $("#id").prop('disabled', true);
    Company.init();
    Adv.o.wrapper.delegate('#RefreshInquiry', 'click', function ()
    {
      Company.getFrames(undefined, $('#invoiceForm').serialize());
      return false;
    });
    Company.getFrames($("#id").val());
  });
