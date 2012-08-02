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
function focus_amount(i)
{
  save_focus(i);
  i.setAttribute('_last', Adv.Forms.getAmount(i.name));
}
function blur_amount(i)
{
  var change = Adv.Forms.getAmount(i.name);
  Adv.Forms.priceFormat(i.name, change, user.pdec);
  change = change - i.getAttribute('_last');
  if (i.name == 'beg_balance') {
    change = -change;
  }
  Adv.Forms.priceFormat('difference', Adv.Forms.getAmount('difference', 1, 1) + change, user.pdec);
}
var balances = {
  '.amount':function (e)
  {
    e.onblur = function ()
    {
      blur_amount(this);
    };
    e.onfocus = function ()
    {
      focus_amount(this);
    };
  }
};
Behaviour.register(balances);
$(function ()
  {
    $("#summary").draggable();
    $('#wrapper').on('click', '.voidlink', function ()
    {
      var voidtrans=false, type = $(this).data('type'), trans_no = $(this).data('trans_no'), url = '/system/void_transaction?type=' + type + '&trans_no=' + trans_no + '&memo=Deleted%20during%20reconcile.';
      if (voidtrans) {
        voidtrans.location.href = url;
      } else {
        voidtrans = window.open(url, '_blank');
      }
    })
  });
