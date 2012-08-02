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
  groupSelect:function () {
    var self = Adv.Reconcile, data = $(this).data();
    if (this.checked) {
      self.group[data.id] = data;
      self.total += data.amount;
    } else {
      self.total -= data.amount;
      delete     self.group[data.id];
    }
    Adv.Forms.priceFormat('deposited',Adv.Reconcile.total,2,true);
  },
  getGrouped:function () {console.log(Adv.Reconcile.group, Adv.Reconcile.total);}
}});
$(function () {
  $("#summary").draggable();
  $('#wrapper').on('click', '.voidlink', function () {
    var voidtrans = false, type = $(this).data('type'), trans_no = $(this).data('trans_no'), url = '/system/void_transaction?type=' + type + '&trans_no=' + trans_no + '&memo=Deleted%20during%20reconcile.';
    if (voidtrans) {
      voidtrans.location.href = url;
    } else {
      voidtrans = window.open(url, '_blank');
    }
  });
  $('#wrapper').on('change', ':checkbox', Adv.Reconcile.groupSelect)

});
