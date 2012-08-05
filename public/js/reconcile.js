/**********************************************************************
 Copyright (C) Advanced Group PTY LTD
 Released under the terms of the GNU General Public License, GPL,
 as published by the Free Software Foundation, either version 3
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/

Adv.extend({Reconcile:{group:{}, toChange:{}, total:0,
  groupSelect:function (e, ui) {
    var self = Adv.Reconcile,  data1 = $(this).data(), data2 = $(ui.draggable).data();
    self.group[data1.id] = {id:data1.id, date:data1.date, amount:data1.amount};
    self.group[data2.id] = {id:data2.id, date:data2.date, amount:data2.amount};
    self.getGrouped();
  },
  getGrouped:function () {console.log(Adv.Reconcile.group, Adv.Reconcile.total);},
  postGroup:function () {
    $.post('#', {Deposit:true, date:$('#deposit_date').value(), toDeposit:Adv.Reconcile.group}, function (data) {$.globalEval(data)}, 'json');
    return false
  },
  changeDate:function (el) {
    var data = {_action:'changeDate'};
    $(el).find('input').each(function () {
      var $this = $(this);
      data[$this.attr('name')] = $this.val();
    });
    $.post('#', data, function (data) {
      if (data.grid) {
        $("#_bank_rec_span").html($('#_bank_rec_span', data.grid).html());
      }
    }, 'json');
    $dateChanger.dialog('close');
  },
  changeBank:function () {
    var data = {_action:'changeBank', newbank:$('#changeBank').val(), type:Adv.Reconcile.toChange.data('type'), trans_no:Adv.Reconcile.toChange.data('transno')};
    $.post('#', data, function (data) {
      if (data.grid) {
        $("#_bank_rec_span").html($('#_bank_rec_span', data.grid).html());
      }
    }, 'json');
    $(this).dialog('close')
  },
  unDeposit:function () {
    var $row = $(this).closest('tr'), data = {_action:'unDeposit', depositid:$row.data('id')};
    $.post('#', data, function (data) {
      if (data.success) {
        $row.remove()
      }
    }, 'json');
    return false;
  },
  unGroup:function () {
    var $row = $(this).closest('tr'), data = {_action:'unGroup', groupid:$row.data('id')};
    $.post('#', data, function (data) {
      if (data.success) {
        $row.remove()
      }
    }, 'json');
    return false;
  }
}});
$(function () {
  var voidtrans = false;
  $("#summary").draggable();
  Adv.o.wrapper.on('click', '#deposit', Adv.Reconcile.postGroup);
  $('.grid').find('.cangroup').droppable({drop:Adv.Reconcile.groupSelect  }).end().find('tbody').sortable({
    items:'.cangroup',
    stop:function (e, ui) {
      var self = $(this), lines = {};
    },
    helper:function (e, ui) {
      ui.children().each(function () {
        $(this).width($(this).width());
      });
      return ui;
    }});
  Adv.o.wrapper.on('click', '.changeDate', function () {
    var $row = $(this).closest('tr');
    Adv.Reconcile.toChange = $row;
    Adv.o.dateChanger.render({id:$row.data('id'), date:$row.data('date')});
    $dateChanger.dialog('open').find('.datepicker').datepicker({dateFormat:'dd/mm/yy'}).datepicker('show');
    return false;

  });
  Adv.o.wrapper.on('click', '.changeBank', function () {
    Adv.Reconcile.toChange = $(this).closest('tr');
    Adv.Forms.setFormValue('changeBank', $('#bank_account').val());
    $("#bankChanger").dialog('open');
    return false;
  });
  Adv.o.wrapper.on('click', '.voidTrans', function () {
    var $this = $(this), url = '/system/void_transaction?type=' + $this.data('type') + '&trans_no=' + $this.data('trans_no') + '&memo=Deleted%20during%20reconcile.';
    if (voidtrans && voidtrans.location) {
      voidtrans.location.href = url;
      voidtrans.focus();
    }
    else {
      voidtrans = window.open(url, '_blank');
    }
    return false;
  });
  Adv.o.wrapper.on('click', '.unDeposit', Adv.Reconcile.unDeposit);
  Adv.o.wrapper.on('click', '.unGroup', Adv.Reconcile.unGroup);

  var bankButtons = {'Cancel':function () {$(this).dialog('close');}, 'Save':Adv.Reconcile.changeBank};
  $("#bankChanger").dialog({autoOpen:false, modal:true, buttons:bankButtons});
});
