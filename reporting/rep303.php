<?php
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

	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
Page::set_security(SA_ITEMSVALREP);

	print_stock_check();
	function getTransactions($category, $location)
		{
			$sql
			 = "SELECT stock_master.category_id,
			stock_category.description AS cat_name,
			stock_master.stock_id,
			stock_master.description, stock_master.inactive,
			IF(stock_moves.stock_id IS NULL, '', stock_moves.loc_code) AS loc_code,
			SUM(IF(stock_moves.stock_id IS NULL,0,stock_moves.qty)) AS QtyOnHand
		FROM (stock_master,
			stock_category)
		LEFT JOIN stock_moves ON
			(stock_master.stock_id=stock_moves.stock_id OR stock_master.stock_id IS NULL)
		WHERE stock_master.category_id=stock_category.category_id
		AND (stock_master.mb_flag='" . STOCK_PURCHASED . "' OR stock_master.mb_flag='" . STOCK_MANUFACTURE . "')";
			if ($category != 0) {
				$sql .= " AND stock_master.category_id = " . DB::escape($category);
			}
			if ($location != 'all') {
				$sql .= " AND IF(stock_moves.stock_id IS NULL, '1=1',stock_moves.loc_code = " . DB::escape($location) . ")";
			}
			$sql
			 .= " GROUP BY stock_master.category_id,
		stock_category.description,
		stock_master.stock_id,
		stock_master.description
		ORDER BY stock_master.category_id,
		stock_master.stock_id";
			return DB::query($sql, "No transactions were returned");
		}


	function print_stock_check()
		{
			$category = $_POST['PARAM_0'];
			$location = $_POST['PARAM_1'];
			$pictures = $_POST['PARAM_2'];
			$check = $_POST['PARAM_3'];
			$shortage = $_POST['PARAM_4'];
			$no_zeros = $_POST['PARAM_5'];
			$comments = $_POST['PARAM_6'];
			$destination = $_POST['PARAM_7'];
			if ($destination) {
				include_once(APPPATH . "reports/excel.php");
			} else {
				include_once(APPPATH . "reports/pdf.php");
			}
			if ($category == ALL_NUMERIC) {
				$category = 0;
			}
			if ($category == 0) {
				$cat = _('All');
			} else {
				$cat = Item_Category::get_name($category);
			}
			if ($location == ALL_TEXT) {
				$location = 'all';
			}
			if ($location == 'all') {
				$loc = _('All');
			} else {
				$loc = Inv_Location::get_name($location);
			}
			if ($shortage) {
				$short = _('Yes');
				$available = _('Shortage');
			} else {
				$short = _('No');
				$available = _('Available');
			}
			if ($no_zeros) {
				$nozeros = _('Yes');
			}
			else {
				$nozeros = _('No');
			}
			if ($check) {
				$cols = array(0, 100, 250, 295, 345, 390, 445, 515);
				$headers = array(
					_('Stock ID'), _('Description'), _('Quantity'), _('Check'), _('Demand'), $available,
					_('On Order')
				);
				$aligns = array('left', 'left', 'right', 'right', 'right', 'right', 'right');
			} else {
				$cols = array(0, 100, 250, 315, 380, 445, 515);
				$headers = array(_('Stock ID'), _('Description'), _('Quantity'), _('Demand'), $available, _('On Order'));
				$aligns = array('left', 'left', 'right', 'right', 'right', 'right');
			}
			$params = array(
				0 => $comments,
				1 => array(
					'text' => _('Category'),
					'from' => $cat,
					'to' => ''
				),
				2 => array(
					'text' => _('Location'),
					'from' => $loc,
					'to' => ''
				),
				3 => array(
					'text' => _('Only Shortage'),
					'from' => $short,
					'to' => ''
				),
				4 => array(
					'text' => _('Suppress Zeros'),
					'from' => $nozeros,
					'to' => ''
				)
			);
			$rep = new ADVReport(_('Stock Check Sheets'), "StockCheckSheet", User::pagesize());
			$rep->Font();
			$rep->Info($params, $cols, $headers, $aligns);
			$rep->Header();
			$res = getTransactions($category, $location);
			$catt = '';
			while ($trans = DB::fetch($res))
			{
				if ($location == 'all') {
					$loc_code = "";
				} else {
					$loc_code = $location;
				}
				$demandqty = Item::get_demand($trans['stock_id'], $loc_code);
				$demandqty += WO::get_demand_asm_qty($trans['stock_id'], $loc_code);
				$onorder = WO::get_on_porder_qty($trans['stock_id'], $loc_code);
				$flag = WO::get_mb_flag($trans['stock_id']);
				if ($flag == STOCK_MANUFACTURE) {
					$onorder += WO::get_on_worder_qty($trans['stock_id'], $loc_code);
				}
				if ($no_zeros && $trans['QtyOnHand'] == 0 && $demandqty == 0 && $onorder == 0) {
					continue;
				}
				if ($shortage && $trans['QtyOnHand'] - $demandqty >= 0) {
					continue;
				}
				if ($catt != $trans['cat_name']) {
					if ($catt != '') {
						$rep->Line($rep->row - 2);
						$rep->NewLine(2, 3);
					}
					$rep->TextCol(0, 1, $trans['category_id']);
					$rep->TextCol(1, 2, $trans['cat_name']);
					$catt = $trans['cat_name'];
					$rep->NewLine();
				}
				$rep->NewLine();
				$dec = Item::qty_dec($trans['stock_id']);
				$rep->TextCol(0, 1, $trans['stock_id']);
				$rep->TextCol(1, 2, $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : ""), -1);
				$rep->AmountCol(2, 3, $trans['QtyOnHand'], $dec);
				if ($check) {
					$rep->TextCol(3, 4, "_________");
					$rep->AmountCol(4, 5, $demandqty, $dec);
					$rep->AmountCol(5, 6, $trans['QtyOnHand'] - $demandqty, $dec);
					$rep->AmountCol(6, 7, $onorder, $dec);
				} else {
					$rep->AmountCol(3, 4, $demandqty, $dec);
					$rep->AmountCol(4, 5, $trans['QtyOnHand'] - $demandqty, $dec);
					$rep->AmountCol(5, 6, $onorder, $dec);
				}
				if ($pictures) {
					$image = COMPANY_PATH . '/images/'
					 . Item::img_name($trans['stock_id']) . '.jpg';
					if (file_exists($image)) {
						$rep->NewLine();
						if ($rep->row - Config::get('item_images_height') < $rep->bottomMargin) {
							$rep->Header();
						}
						$rep->AddImage($image, $rep->cols[1], $rep->row - Config::get('item_images_height'), 0,
							Config::get('item_images_height'));
						$rep->row -= Config::get('item_images_height');
						$rep->NewLine();
					}
				}
			}
			$rep->Line($rep->row - 4);
			$rep->NewLine();
			$rep->End();
		}

?>
