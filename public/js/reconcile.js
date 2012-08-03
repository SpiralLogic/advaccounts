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

Adv.extend({Reconcile:{group:{}, total:0,
  groupSelect:               function (e, ui)
  {
    var self = Adv.Reconcile, dateToChange, data1 = $(this).data(), data2 = $(ui.draggable).data();
    self.group[data1.id] = {id:data1.id, date:data1.date, amount:data1.amount};
    self.group[data2.id] = {id:data2.id, date:data2.date, amount:data2.amount};
  },
  getGrouped:                function () {console.log(Adv.Reconcile.group, Adv.Reconcile.total);},
  postGroup:                 function ()
  {
    $.post('#', {Deposit:true, date:$('#deposit_date').value(), toDeposit:Adv.Reconcile.group}, function (data) {$.globalEval(data)}, 'json');
    return false
  },
  changeDate:                function (el)
  {
    var data = {_action:'changeDate'};
    $(el).find('input').each(function ()
                             {
                               var $this = $(this);
                               data[$this.attr('name')] = $this.val();
                             });
    $.post('#', data, function (data)
    {
      console.log(data);
      if (data.newdate) {
        Adv.Reconcile.dateToChange.text(data.newdate)
      }
    }, 'json');
    $dateChanger.dialog('close');
  }

}});
$(function ()
  {
    $("#summary").draggable();
    Adv.o.wrapper.on('click', '.voidlink', function ()
    {
      var voidtrans = false, type = $(this).data('type'), trans_no = $(this).data('trans_no'), url = '/system/void_transaction?type=' + type + '&trans_no=' + trans_no + '&memo=Deleted%20during%20reconcile.';
      if (voidtrans) {
        voidtrans.location.href = url;
      }
      else {
        voidtrans = window.open(url, '_blank');
      }
    });
    Adv.o.wrapper.on('click', '#deposit', Adv.Reconcile.postGroup);
    $('.grid').find('.cangroup').droppable({drop:Adv.Reconcile.groupSelect  }).end().find('tbody').sortable({
                                                                                                              items: '.cangroup',
                                                                                                              stop:  function (e, ui)
                                                                                                              {
                                                                                                                var self = $(this), lines = {};
                                                                                                              },
                                                                                                              helper:function (e, ui)
                                                                                                              {
                                                                                                                ui.children().each(function ()
                                                                                                                                   {
                                                                                                                                     $(this).width($(this).width());
                                                                                                                                   });
                                                                                                                return ui;
                                                                                                              }});
    Adv.o.wrapper.on('dblclick', '.date', function ()
    {
      var $this = $(this);
      Adv.Reconcile.dateToChange = $this;
      Adv.o.dateChanger.render({id:$this.data('id'), date:$this.text()});
      $dateChanger.dialog('open').find('.datepicker').datepicker({dateFormat:'dd/mm/yy'}).datepicker('show')
    });
  });
