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
function focus_alloc(i) {
	save_focus(i);
	i.setAttribute('_last', Adv.Forms.getAmount(i.name));
}

function blur_alloc(i) {

	var last = +i.getAttribute('_last')
	var left = Adv.Forms.getAmount('left_to_allocate', 1);
	var cur = Math.min(Adv.Forms.getAmount(i.name), Adv.Forms.getAmount('maxval' + i.name.substr(6), 1), last + left)

	Adv.Forms.priceFormat(i.name, cur, user.pdec);
	change = cur - last;

	var total = Adv.Forms.getAmount('total_allocated', 1) + change;
	left -= change;
	Adv.Forms.priceFormat('left_to_allocate', left, user.pdec, 1, 1);
	Adv.Forms.priceFormat('total_allocated', total, user.pdec, 1, 1);
}

function allocate_all(doc) {
	var amount = Adv.Forms.getAmount('amount' + doc);
	var unallocated = Adv.Forms.getAmount('un_allocated' + doc);
	var total = Adv.Forms.getAmount('total_allocated', 1);
	var left = Adv.Forms.getAmount('left_to_allocate', 1);
	total -= (amount - unallocated);
	left += (amount - unallocated);
	amount = unallocated;
	if (left < 0) {
		total += left;
		amount += left;
		left = 0;
	}

	Adv.Forms.priceFormat('amount' + doc, amount, user.pdec);
	Adv.Forms.priceFormat('left_to_allocate', left, user.pdec, 1, 1);
	Adv.Forms.priceFormat('total_allocated', total, user.pdec, 1, 1);
}

function allocate_none(doc) {
	amount = Adv.Forms.getAmount('amount' + doc);
	left = Adv.Forms.getAmount('left_to_allocate', 1);
	total = Adv.Forms.getAmount('total_allocated', 1);
	Adv.Forms.priceFormat('left_to_allocate', amount + left, user.pdec, 1, 1);
	Adv.Forms.priceFormat('amount' + doc, 0, user.pdec);
	Adv.Forms.priceFormat('total_allocated', total - amount, user.pdec, 1, 1);
}

var allocations = {
	'.amount':function (e) {
		e.onblur = function () {
			blur_alloc(this);
		};
		e.onfocus = function () {
			focus_alloc(this);
		};
	},
	'.allocateAll':function (e) {
		e.onclick = function () {
			allocate_all(this.name.substr(5));
		}
	},
	'.allocateNone':function (e) {
		e.onclick = function () {

			allocate_none(this.name.substr(5));
		}
	}
}

Behaviour.register(allocations);
