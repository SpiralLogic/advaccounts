<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	$page_security = 'SA_TAXREP';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	$js = '';
	ui_view::set_focus('account');
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(800, 500);

	page(_($help_context = "Tax Inquiry"), false, false, '', $js);

	//----------------------------------------------------------------------------------------------------
	// Ajax updates
	//
	if (get_post('Show')) {
		$Ajax->activate('trans_tbl');
	}

	if (get_post('TransFromDate') == "" && get_post('TransToDate') == "") {
		$date = Dates::Today();
		$row = DB_Company::get_prefs();
		$edate = Dates::add_months($date, -$row['tax_last']);
		$edate = Dates::end_month($edate);
		$bdate = Dates::begin_month($edate);
		$bdate = Dates::add_months($bdate, -$row['tax_prd'] + 1);
		$_POST["TransFromDate"] = $bdate;
		$_POST["TransToDate"] = $edate;
	}

	//----------------------------------------------------------------------------------------------------

	function tax_inquiry_controls() {

		start_form();

		//start_table(Config::get('tables_style2'));
		start_table("class='tablestyle_noborder'");
		start_row();

		date_cells(_("from:"), 'TransFromDate', '', null, -30);
		date_cells(_("to:"), 'TransToDate');
		submit_cells('Show', _("Show"), '', '', 'default');

		end_row();

		end_table();

		end_form();
	}

	//----------------------------------------------------------------------------------------------------

	function show_results() {

		/*Now get the transactions  */
		div_start('trans_tbl');
		start_table(Config::get('tables_style'));

		$th = array(_("Type"), _("Description"), _("Amount"), _("Outputs") . "/" . _("Inputs"));
		table_header($th);
		$k = 0;
		$total = 0;
		$bdate = Dates::date2sql($_POST['TransFromDate']);
		$edate = Dates::date2sql($_POST['TransToDate']);

		$taxes = get_tax_summary($_POST['TransFromDate'], $_POST['TransToDate']);

		while ($tx = DBOld::fetch($taxes))
		{

			$payable = $tx['payable'];
			$collectible = $tx['collectible'];
			$net = $collectible + $payable;
			$total += $net;
			alt_table_row_color($k);
			label_cell($tx['name'] . " " . $tx['rate'] . "%");
			label_cell(_("Charged on sales") . " (" . _("Output Tax") . "):");
			amount_cell($payable);
			amount_cell($tx['net_output']);
			end_row();
			alt_table_row_color($k);
			label_cell($tx['name'] . " " . $tx['rate'] . "%");
			label_cell(_("Paid on purchases") . " (" . _("Input Tax") . "):");
			amount_cell($collectible);
			amount_cell($tx['net_input']);
			end_row();
			alt_table_row_color($k);
			label_cell("<b>" . $tx['name'] . " " . $tx['rate'] . "%</b>");
			label_cell("<b>" . _("Net payable or collectible") . ":</b>");
			amount_cell($net, true);
			label_cell("");
			end_row();
		}
		alt_table_row_color($k);
		label_cell("");
		label_cell("<b>" . _("Total payable or refund") . ":</b>");
		amount_cell($total, true);
		label_cell("");
		end_row();

		end_table(2);
		div_end();
	}

	//----------------------------------------------------------------------------------------------------

	tax_inquiry_controls();

	show_results();

	//----------------------------------------------------------------------------------------------------

	end_page();

?>
