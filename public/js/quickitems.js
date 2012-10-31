/**
 * User: Eli Sklar
 * Date: 17/07/11 - 10:58 PM
 */
Adv.extend({
             revertState:function () {
               var form = document.getElementsByTagName('form')[0];
               form.reset();
               Adv.btnConfirm.hide();
               Adv.btnCancel.hide();
               Adv.btnNew.show();
               Adv.Forms.resetHighlights();
               $("#itemSearchId").val('');
             },
             resetState: function () {
               $("#tabs0 input, #tabs0 textarea").empty();
               Items.fetch(0);
               Adv.btnCancel.hide();
               Adv.btnConfirm.hide();
               Adv.btnNew.show();
             }
           });
var Items = function () {
  var
    item, //
    $itemsearch = $('#itemSearchId'), //
    $webFrame = $('#webFrame'), //
    $selects = $('select'), //
    $Items = $("#Items").show(), //
    $Accounts = $("#Accounts"), //
    $stockRow = $("#stockRow"), $stockLevels = $("#stockLevels");
  $Items.template('items');
  $Accounts.template('accounts');
  $stockRow.template('stockrow');
  return {
    fetch:    function (id) {
      if (id.value !== undefined) {
        $itemsearch.val(id.value);
        Items.getFrames(id.value);
      }
      else {
        $itemsearch.val('');
        Items.getFrames(0);
      }
      if (id.id !== undefined) {
        id = id.id;
      }
      $.post("#", {stockid:id}, function (data) {
        Items.onload(data, true);
      }, 'json');
      return false;
    },
    getFrames:function (id) {
      if (!id) {
        Adv.o.tabs[0].tabs('option', 'disabled', [2, 3, 4, 5]);
        return;
      }
      Adv.o.tabs[0].tabs('option', 'disabled', []);
    },
    set:      function (fieldname, val) {
      item[fieldname] = val;
    },
    onload:   function (data, noframes) {
      var form;
      if (!noframes) {
        this.getFrames(data.item.stock_id);
      }
      $Items.empty();
      $Accounts.empty();
      item = data.item;
      $.tmpl('items', data.item).appendTo("#Items");
      $.tmpl('accounts', data.item).appendTo("#Accounts");
      $('#_sellprices_span').replaceWith(data.sellprices);
      $('#_buyprices_span').replaceWith(data.buyprices);
      $('#_reorderlevels_span').replaceWith(data.reorderlevels);
      if (data.stockLevels) {
        $stockLevels.show().find('tbody').html($.tmpl('stockrow', data.stockLevels));
      }
      else {
        $stockLevels.hide();
      }
      form = data._form_id;
      $.each(item, function (i, data) {
        Adv.Forms.setFormDefault(i, data, form);
      });
      Adv.Forms.setFocus('stock_id');
    },
    get:      function () {
      return item;
    },
    save:     function () {
      $.post('#', item, function (data) {
        if (data.success && data.success.success) {
          Items.onload(data);
        }
      }, 'json');
    }
  };
}();
$(function () {
  Adv.extend({btnCancel:$("#btnCancel").mousedown(function () {
    Adv.revertState();
    return false;
  }), btnConfirm:       $("#btnConfirm").mousedown(function () {
    Items.save();
    return false;
  }),
               btnNew:  $("#btnNew").mousedown(function () {
                 Adv.resetState();
                 return false;
               }) });
  Adv.o.tabs[0] = $("#tabs0").tabs();
  Adv.o.tabs[0].delegate("input,textarea,select", "change keyup", function () {
    var $this = $(this), $thisname = $this.attr('name');
    Adv.Forms.stateModified($this);
    if (Adv.fieldsChanged > 0) {
      Adv.btnNew.hide();
      Adv.btnCancel.show();
      Adv.btnConfirm.show();
    }
    else {
      if (Adv.fieldsChanged === 0) {
        Adv.btnConfirm.hide();
        Adv.btnCancel.hide();
        Adv.btnNew.show();
      }
    }
    Items.set($thisname, this.value);
  })
});
