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
  unDeposit:                 function ()
  {
    return Adv.Reconcile.sendAction({_action:'unDeposit', depositid:$(this).closest('tr').data('id')});
  },
  unGroup:                   function ()
  {
    return Adv.Reconcile.sendAction({_action:'unGroup', groupid:$(this).closest('tr').data('id')});
  },
  sendAction:                function (data)
  {
    var overlay = $("<div class='black_overlay'></div>").css('display', 'block').appendTo("#_bank_rec_span");
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
                                                activeClass:'activeclass'}).end().find('tbody').sortable({
                                                                                                           placeholder:         "ui-state-highlight",
                                                                                                           forcePlaceholderSize:true,
                                                                                                           axis:                'y',
                                                                                                           items:               '.cangroup',
                                                                                                           cursor:              'move', opacity:0.8,
                                                                                                           helper:              function (e, ui)
                                                                                                           {
                                                                                                             ui.children().each(function ()
                                                                                                                                {
                                                                                                                                  $(this).width($(this).width());
                                                                                                                                  $(this).height($(this).height());
                                                                                                                                });
                                                                                                             return ui;
                                                                                                           }});
  }
}});
$(function ()
  {
    var voidtrans = false;
    $("#summary").draggable();
    Adv.Reconcile.setUpGrid();
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
