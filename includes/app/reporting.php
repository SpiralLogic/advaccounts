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
	// Link to printing single document with bulk report template file.
	// Ex. label_cell(static::print_doc_link($myrow['order_no'], _("Print")), $type);
	// or Errors::warning(static::print_doc_link($order_no, _("Print this order")));
	// You only need full parameter list for invoices/credit notes
	class Reporting
	{
		public static function print_doc_link($doc_no, $link_text, $link = true, $type_no, $icon = false, $class = 'button printlink',
			$id = '', $email = 0, $extra = 0)
			{
				$url = '/reporting/prn_redirect.php?';
				$options = static::print_option_array($type_no, $doc_no, $email, $extra);
				$ar = $options[0];
				$rep = $options[1];
				return static::print_link($link_text, $rep, $ar, "", $icon, $class, $id);
			}

		public static function print_option_array($type_no, $doc_no, $email = 0, $extra = 0)
			{
				$ar = array();
				$rep = '';
				switch ($type_no) {
					case ST_SALESQUOTE :
						$rep = 111;
						// from, to, currency, bank acc, email, quote, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_3' => $email, 'PARAM_4' => '');
						break;
					case ST_SALESORDER :
						$rep = 109;
						// from, to, currency, bank acc, email, quote, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_3' => $email, 'PARAM_4' => 0, 'PARAM_5' => '');
						break;
					case ST_CUSTDELIVERY :
						$rep = 110;
						// from, to, email, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => $email, 'PARAM_3' => $extra);
						break;
					case ST_SALESINVOICE : // Sales Invoice
					case ST_CUSTCREDIT : // Customer Credit Note
						$rep = 107;
						// from, to, currency, bank acc, email, paylink, comments, type
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_3' => $email, 'PARAM_4' => '', 'PARAM_5' => '', 'PARAM_6' => $type_no);
						break;
					case ST_PURCHORDER :
						$rep = 209;
						// from, to, currency, bank acc, email, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_3' => $email, 'PARAM_4' => '');
						break;
					case ST_CUSTPAYMENT :
						$rep = 112;
						// from, to, currency, bank acc, email, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_4' => '');
						break;
					case ST_CUSTREFUND :
						$rep = 113;
						// from, to, currency, bank acc, email, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_4' => '');
						break;
					case ST_PROFORMA :
						$rep = 129;
						// from, to, currency, bank acc, email, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_3' => $email, 'PARAM_4' => '2');
						break;
					case ST_PROFORMAQ :
						$rep = 131;
						// from, to, currency, bank acc, email, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_3' => $email, 'PARAM_4' => '3');
						break;
					case ST_SUPPAYMENT :
						$rep = 210;
						// from, to, currency, bank acc, email, comments
						$ar = array(
							'PARAM_0' => $doc_no, 'PARAM_1' => $doc_no, 'PARAM_2' => '', 'PARAM_3' => $email, 'PARAM_4' => '');
						break;
					//		default: $ar = array();
				}
				return array($ar, $rep);
			}

		public static function email_link($doc_no, $link_text, $link = true, $type_no, $class = 'EmailLink', $id = '',
			$emails = array(), $extra = 0, $return = false)
			{
				if (empty($emails)) {
					return false;
				}
				if ($return) {
					ob_start();
				}
				$url = '/reporting/prn_redirect.php?';
				$options = static::print_option_array($type_no, $doc_no, 1, $extra);
				$ars = $options[0];
				$rep = $options[1];
				foreach ($ars as $ar => $val) {
					$ars[$ar] = "$ar=" . urlencode($val);
				}
				$ars[] = 'REP_ID=' . urlencode($rep);
				$url .= implode('&', $ars);
				if ($class != '') {
					$class = Config::get('debug_pdf') ? '' : " class='$class'";
				}
				if ($id != '') {
					$id = " id='$id'";
				}
				$ars = access_string($link_text);
				HTML::br()->p(array('class' => 'center'));
				UI::select('EmailSelect' . $type_no, $emails, array('style' => 'max-width:600px'))->br;
				UI::button('EmailButton' . $type_no, $link_text, array(
																															'style' => 'margin:20px', 'data-url' => $url))->p;
				$js = <<<JS
		$('#EmailButton$type_no').button().click(function() {

			var email = $("#EmailSelect$type_no").val();
			$.get($(this).data('url') + "&Email="+email,function(response) {
				Adv.msgbox.html(response);
			});
			\$emailBox.dialog("close");
		});
JS;
				if ($return) {
					HTML::script('null', $js, false);
					return ob_get_clean();
				}
				JS::onload($js);
			}

//
//	Universal link to any kind of report.
//
		public static function print_link($link_text, $rep, $pars = array(), $dir = '', $icon = false, $class = 'printlink', $id = '')
			{
				$url = $dir == '' ? PATH_TO_ROOT . '/reporting/prn_redirect.php?' : $dir;
				$id = JS::default_focus($id);
				foreach ($pars as $par => $val) {
					$pars[$par] = "$par=" . urlencode($val);
				}
				$pars[] = 'REP_ID=' . urlencode($rep);
				$url .= implode('&', $pars);
				if ($class != '') {
					$class = Config::get('debug_pdf') ? '' : " class='$class'";
				}
				if ($id != '') {
					$id = " id='$id'";
				}
				$pars = access_string($link_text);
				if (User::graphic_links() && $icon) {
					$pars[0] = set_icon($icon, $pars[0]);
				}
				return "<a target='_blank' href='$url'$id$class $pars[1]>$pars[0]</a>";
			}
	}

?>