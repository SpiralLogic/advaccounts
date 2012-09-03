/**
 * User: Eli Sklar
 * Date: 17/07/11 - 10:58 PM
 */
Adv.extend({
             revertState: function () {
               var form = document.getElementsByTagName('form')[0];
               form.reset();
               Adv.btnConfirm.hide();
               Adv.btnCancel.hide();
               Adv.btnNew.show();
               Adv.Forms.resetHighlights();
               $("#itemSearchId").val('');
             },
             resetState:  function () {
               $("#tabs0 input, #tabs0 textarea").empty();
               Items.fetch(0);
               Adv.btnCancel.hide();
               Adv.btnConfirm.hide();
               Adv.btnNew.show();
             }
           });
var Items = function () {
  var btn = $("#btnItems").button(), //
    item, //
    $buyFrame = $('#buyFrame'), //
    $sellFrame = $('#sellFrame'), //
    $locationFrame = $('#locationFrame'), //
    $webFrame = $('#webFrame'), //
    $selects = $('select'), //
    urlregex = /[\w\-\.:/=ƒ&!~\*\'"(),]+/g, //
    $buyFrameSrc = $('#buyFrame').data('src').match(urlregex)[0] + '?frame=1', //
    $sellFrameSrc = $('#sellFrame').data('src').match(urlregex)[0] + '?frame=1', //
    $locationFrameSrc = $('#locationFrame').data('src').match(urlregex)[0] + '?frame=1', //
    $Items = $("#Items").show(), //
    $Accounts = $("#Accounts"), //
    $stockRow = $("#stockRow"), $stockLevels = $("#stockLevels");
  $Items.template('items');
  $Accounts.template('accounts');
  $stockRow.template('stockrow');
  return {
    fetch:     function (id) {
      if (id.id !== undefined) {
        id = id.id;
      }
      this.getFrames(id);
      $.post("#", {"id": id}, function (data) {
        Items.onload(data, true);
      }, 'json');
    },
    getFrames: function (id) {
      if (!id) {
        Adv.o.tabs[0].tabs('option', 'disabled', [2, 3, 4, 5]);
        return;
      }
      Adv.o.tabs[0].tabs('option', 'disabled', []);
      $buyFrame.attr('src', $buyFrameSrc + '&stock_id=' + id);
      $sellFrame.attr('src', $sellFrameSrc + '&stock_id=' + id);
      $locationFrame.attr('src', $locationFrameSrc + '&stock_id=' + id);
      /*		$webFrame.attr('src', $webFrame.data('srcpre')+ id+$webFrame.data('srcpost'));*/
    },
    set:       function (fieldname, val) {
      item[fieldname] = val;
    },
    onload:    function (data, noframes) {
      if (!noframes) {
        this.getFrames(data.item.stock_id);
      }
      $Items.empty();
      $Accounts.empty();
      item = data.item;
      $.tmpl('items', data.item).appendTo("#Items");
      $.tmpl('accounts', data.item).appendTo("#Accounts");
      if (data.stockLevels) {
        $stockLevels.show().find('tbody').html($.tmpl('stockrow', data.stockLevels));
      }
      $.each(item, function (i, data) {
        Adv.Forms.setFormDefault(i, data);
      });
      Adv.Forms.setFocus('stock_id');
    },
    get:       function () {
      return item;
    },
    save:      function () {
      $.post('#', item, function (data) {
        if (data.success && data.success.success) {
          Items.onload(data);
        }
      }, 'json');
    }
  };
}();
$(function () {
  Adv.extend({btnCancel: $("#btnCancel").button().mousedown(function () {
    Adv.revertState();
    return false;
  }), btnConfirm:        $("#btnConfirm").button().mousedown(function () {
    Items.save();
    return false;
  }),
               btnNew:   $("#btnNew").button().mousedown(function () {
                 Adv.resetState();
                 return false;
               }) });
  Adv.o.tabs[0] = $("#tabs0");
  Adv.o.tabs[0].delegate("input,textarea,select", "change keyup", function () {
    var $this = $(this), buttontext, $thisname = $this.attr('name');
    Adv.Forms.stateModified($this);
    if (Adv.fieldsChanged > 0) {
      buttontext = (Items.get().id) ? "Changes" : "New";
      Adv.btnNew.hide();
      Adv.btnCancel.html('<i class="icon-trash"></i> Cancel ' + buttontext).show();
      Adv.btnConfirm.html('<i class="icon-ok"></i> Save ' + buttontext).show();
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
