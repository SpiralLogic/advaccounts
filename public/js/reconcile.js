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

Adv.extend({Reconcile:{group:{}, toChange:{}, total:0, voidtrans:false,
  groupSelect:               function (e, ui)
  {
    var data = {_action:'deposit', trans1:$(this).data('id'), trans2:$(ui.draggable).data('id')};
    return Adv.Reconcile.sendAction(data);
  },
  changeDate:                function (el)
  {
    var data = {_action:'changeDate', trans_id:Adv.Reconcile.toChange.data('id')};
    $(el).find('[name="date"]').each(function ()
                                     {
                                       data['date'] = $(this).val();
                                     });
    Adv.Reconcile.sendAction(data);
    $dateChanger.dialog('close');
  },
  changeBank:                function ()
  {
    var data = {_action:'changeBank', newbank:$('#changeBank').val(), type:Adv.Reconcile.toChange.data('type'), trans_no:Adv.Reconcile.toChange.data('transno')};
    Adv.Reconcile.sendAction(data);
    $(this).dialog('close')
  },
  createLink:                function ()
  {
    var self = $(this), fee = '', url = self.attr('href'), $row = $(this).closest('tr'), date = $row.data('date'), amount = $row.data('amount'), memo = $row.find('.state_memo').text();
    if (self.data('fee')) {
      fee = '&fee=' + self.data('fee');
      amount = self.data('amount');
    }
    url = encodeURI(url + '?date=' + date + '&account=' + $('#bank_account').val() + '&amount=' + amount + fee + '&memo=' + memo);
    Adv.Reconcile.openLink(url);
    return false;
  },
  openLink:                  function (url)
  {
    if (Adv.Reconcile.voidtrans && Adv.Reconcile.voidtrans.location) {
      Adv.Reconcile.voidtrans.location.href = url;
      Adv.Reconcile.voidtrans.focus();
    }
    else {
      Adv.Reconcile.voidtrans = window.open(url, '_blank');
    }
  },
  unGroup:                   function ()
  {
    return Adv.Reconcile.sendAction({_action:'unGroup', groupid:$(this).closest('tr').data('id')});
  },
  sendAction:                function (data)
  {
    var overlay = $("<div class='black_overlay'></div>").css('display', 'block').appendTo("#_bank_rec_span tbody");
    $("<div></div>").attr('id', 'loading').appendTo(overlay);
    $.post('#', data, function (data)
    {
      if (data.grid) {
        $("#_bank_rec_span").html($('#_bank_rec_span', data.grid).html());
        Adv.Reconcile.setUpGrid();
      }
    }, 'json');
    return false;
  },
  setUpGrid:                 function ()
  {
    $('.recgrid').find('.cangroup').droppable({drop:        Adv.Reconcile.groupSelect,
                                                hoverClass: 'hoverclass',
                                                placeholder:'placeholder',
                                                activeClass:'activeclass'}).end().find('tbody').sortable({
                                                                                                           tolerance: 'pointer',
                                                                                                           axis:      'y',
                                                                                                           items:     '.cangroup',
                                                                                                           cursor:    'move', revert:true,
                                                                                                           beforestop:function (e, ui) {$(this).sortable('cancel');},
                                                                                                           helper:    function (e, ui)
                                                                                                           {
                                                                                                             ui.children().each(function ()
                                                                                                                                {
                                                                                                                                  $(this).width($(this).width() + 6);
                                                                                                                                  $(this).height($(this).height() + 2);
                                                                                                                                });
                                                                                                             return ui;
                                                                                                           }});
  }
}});
$(function ()
  {
    $("#summary").draggable();
    Adv.o.wrapper.on('click', '.changeDate', function ()
    {
      var $row = $(this).closest('tr');
      Adv.Reconcile.toChange = $row;
      Adv.o.dateChanger.render({id:$row.data('id'), date:$row.data('date')});
      $dateChanger.dialog('open').find('.datepicker').datepicker({dateFormat:'dd/mm/yy'}).datepicker('show');
      return false;
    });
    Adv.o.wrapper.on('click', '.changeBank', function ()
    {
      Adv.Reconcile.toChange = $(this).closest('tr');
      Adv.Forms.setFormValue('changeBank', $('#bank_account').val());
      $("#bankChanger").dialog('open');
      return false;
    });
    Adv.o.wrapper.on('click', '.voidTrans', function ()
    {
      var $this = $(this), url = '/system/void_transaction?type=' + $this.data('type') + '&trans_no=' + $this.data('trans_no') + '&memo=Deleted%20during%20reconcile.';
      Adv.Reconcile.openLink(url);
      return false;
    });
    Adv.o.wrapper.on('click', '.unGroup', Adv.Reconcile.unGroup);
    Adv.o.wrapper.on('click', '[class^="create"]', Adv.Reconcile.createLink);
    var bankButtons = {'Cancel':function () {$(this).dialog('close');}, 'Save':Adv.Reconcile.changeBank};
    $("#bankChanger").dialog({autoOpen:false, modal:true, buttons:bankButtons});
  });
