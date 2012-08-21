/**
 * User: Eli Sklar
 * Date: 17/07/11 - 10:58 PM
 */
Adv.extend({
  revertState:function () {
    var form = document.getElementsByTagName('form')[0];
    form.reset();
    Adv.btnCancel.attr('name', 'new').text("New");
    Adv.Forms.resetHighlights();
  },
  resetState:function () {
    $("#tabs0 input, #tabs0 textarea").empty();
    Items.fetch(0);
  }
});
var Items = function () {
  var btn = $("#btnItems").button(), item,
    $buyFrame = $('#buyFrame'),
    $sellFrame = $('#sellFrame'),
    $locationFrame = $('#locationFrame'),
    $webFrame = $('#webFrame'),
    $selects = $('select'),
    urlregex = /[\w\-\.:/=ƒ&!~\*\'"(),]+/g;
  var $buyFrameSrc = $('#buyFrame').data('src').match(urlregex)[0] + '?frame=1',
    $sellFrameSrc = $('#sellFrame').data('src').match(urlregex)[0] + '?frame=1',
    $locationFrameSrc = $('#locationFrame').data('src').match(urlregex)[0] + '?frame=1',
    $Items = $("#Items").show(), $Accounts = $("#Accounts"), $stockRow = $("#stockRow"), $stockLevels = $("#stockLevels");
  $Items.template('items');
  $Accounts.template('accounts');
  $stockRow.template('stockrow');
  return {
    fetch:function (id) {
      if (id.id !== undefined) {
        id = id.id;
      }
      this.getFrames(id);
      $.post("#", {"id":id}, function (data) {
        Items.onload(data, true);
      }, 'json');
    },
    getFrames:function (id) {
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
    set:function (feildname, val) {
      item[feildname] = val;
    },
    onload:function (data, noframes) {
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
        Adv.Forms.setFormValue(i, data);
      });      Adv.btnCancel.attr('name', 'new').text("New");
      $("#itemSearchId").val('');
      Adv.Forms.setFocus('stock_id');

    },
    get:function () {
      return item;
    },
    save:function () {
      $.post('#', item, function (data) {
        console.log(data);
        Items.onload(data);
      }, 'json');
    }
  };
}();
$(function () {
  Adv.extend({btnCancel:$("#btnCancel").button().click(function () {
    ($(this).attr('name') == 'new') ? Adv.resetState() : Adv.revertState();
    return false;
  }), btnSave:$("#btnSave").button().click(function () {
    Items.save();
    Adv.btnCancel.attr('name', 'new').text("New");
    return false;
  }).hide()});
  Adv.o.tabs[0] = $("#tabs0");
  Adv.o.tabs[0].delegate("input,textarea,select", "change keyup", function (event) {
    var $this = $(this), $thisname = $this.attr('name');
    Adv.Forms.stateModified($this);
    Adv.btnCancel.button('option', 'label', 'Cancel Changes');
    if (Items.get().id) {
      Adv.btnSave.button("option", "label", "Save Changes").show();
    } else {
      Adv.btnSave.button("option", "label", "Save New Item").show();
    }
    if (Adv.fieldsChanged === 0) {
      Adv.btnCancel.attr('name', 'new').text("New");
    } else {
      Adv.btnCancel.attr('name', 'cancel').text('Cancel Changes');
    }
    Items.set($thisname, this.value);
  })
});
