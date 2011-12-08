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
	// xls version
	class ADVReport extends Spreadsheet_Excel_Writer_Workbook
	{
		public $size;
		public $company;
		public $user;
		public $host;
		public $fiscal_year;
		public $title;
		public $filename;
		public $unique_name;
		public $path;
		public $code;
		public $bottomMargin = 0;
		public $lineHeight;
		public $leftMargin = 0;
		public $cols;
		public $params;
		public $headers;
		public $aligns;
		public $headers2;
		public $aligns2;
		public $cols2;
		public $fontSize;
		public $oldFontSize;
		public $currency;
		public $row = 9999999;
		public $y;
		public $numcols;
		public $excelColWidthFactor;
		public $endLine;
		public $formatTitle;
		public $formatDateTime;
		public $formatDate;
		public $formatHeaderLeft;
		public $formatHeaderRight;
		public $formatFooter;
		public $formatAmount = array();
		public $sheet;

		public function __construct($title, $filename, $size = 'A4', $fontsize = 9, $orientation = 'P', $margins = NULL,
			$excelColWidthFactor = 6.5) {
			global $page_security;
			if (!User::get()->can_access_page($page_security)) {
				Errors::error(_("The security settings on your account do not permit you to print this report"));
				Renderer::end_page();
				exit;
			}
			$this->size = $size;
			$this->title = $title;
			$this->lineHeight = 12;
			$this->endLine = 760;
			$this->fontSize = $fontsize;
			$this->oldFontSize = 0;
			$this->y = 0;
			$this->currency = '';
			$this->excelColWidthFactor = $excelColWidthFactor;
			$rtl = ($_SESSION['Language']->dir == 'rtl');
			$this->code = strtolower($_SESSION['Language']->encoding);
			$this->filename = $filename . ".xls";
			$this->unique_name = uniqid('') . ".xls";
			$this->path = COMPANY_PATH . '/pdf_files';
			$this->Spreadsheet_Excel_Writer_Workbook($this->path . "/" . $this->unique_name);
			//$this->setCountry(48);
			if ($this->code != "iso-8859-1") {
				$this->setVersion(8);
			} // set biff version to 8 (0x0006 internal)
			$this->sheet =& $this->addWorksheet($this->worksheetNameGenerator($this->title));
			if ($this->code != "iso-8859-1") {
				$this->sheet->setInputEncoding($this->code);
			} // set sheet encoding
			if ($rtl) {
				$this->sheet->setRTL();
			}
			$this->formatTitle =& $this->addFormat();
			$this->formatTitle->setSize(16);
			$this->formatTitle->setBold();
			$this->formatTitle->setAlign($rtl ? 'right' : 'left');
			$this->formatTitle->setTop(2);
			$this->formatTitle->setTopColor('gray');
			$how = User::date_format();
			$sep = Config::get('separators_date', User::date_sep());
			if ($sep == '.') {
				$sep = "\\.";
			}
			if ($how == 0) {
				$dateformat_long = "mm{$sep}dd{$sep}yyyy\ \ hh:mm\ am/pm";
				$dateformat = "mm{$sep}dd{$sep}yyyy";
			} elseif ($how == 1) {
				$dateformat_long = "dd{$sep}mm{$sep}yyyy\ \ hh:mm";
				$dateformat = "dd{$sep}mm{$sep}yyyy";
			} else {
				$dateformat_long = "yyyy{$sep}mm{$sep}dd\ \ hh:mm";
				$dateformat = "yyyy{$sep}mm{$sep}dd";
			}
			$this->formatDateTime =& $this->addFormat();
			$this->formatDateTime->setNumFormat($dateformat_long);
			$this->formatDateTime->setAlign($rtl ? 'right' : 'left');
			$this->formatDate =& $this->addFormat();
			$this->formatDate->setNumFormat($dateformat);
			$this->formatDate->setAlign($rtl ? 'right' : 'left');
			$this->formatRight =& $this->addFormat();
			$this->formatRight->setAlign($rtl ? 'left' : 'right');
			$this->formatLeft =& $this->addFormat();
			$this->formatLeft->setAlign($rtl ? 'right' : 'left');
			$this->formatHeaderLeft =& $this->addFormat();
			$this->formatHeaderLeft->setItalic();
			$this->formatHeaderLeft->setTop(2);
			$this->formatHeaderLeft->setTopColor('gray');
			$this->formatHeaderLeft->setBottom(2);
			$this->formatHeaderLeft->setBottomColor('gray');
			$this->formatHeaderLeft->setAlign('vcenter');
			$this->formatTopHeaderLeft =& $this->addFormat();
			$this->formatTopHeaderLeft->setItalic();
			$this->formatTopHeaderLeft->setTop(2);
			$this->formatTopHeaderLeft->setTopColor('gray');
			$this->formatTopHeaderLeft->setAlign('vcenter');
			$this->formatBottomHeaderLeft =& $this->addFormat();
			$this->formatBottomHeaderLeft->setItalic();
			$this->formatBottomHeaderLeft->setBottom(2);
			$this->formatBottomHeaderLeft->setBottomColor('gray');
			$this->formatBottomHeaderLeft->setAlign('vcenter');
			$this->formatDate->setAlign($rtl ? 'right' : 'left');
			$this->formatHeaderRight =& $this->addFormat();
			$this->formatHeaderRight->setItalic();
			$this->formatHeaderRight->setTop(2);
			$this->formatHeaderRight->setTopColor('gray');
			$this->formatHeaderRight->setBottom(2);
			$this->formatHeaderRight->setBottomColor('gray');
			$this->formatHeaderRight->setAlign('vcenter');
			$this->formatHeaderRight->setAlign('right');
			$this->formatTopHeaderRight =& $this->addFormat();
			$this->formatTopHeaderRight->setItalic();
			$this->formatTopHeaderRight->setTop(2);
			$this->formatTopHeaderRight->setTopColor('gray');
			$this->formatTopHeaderRight->setAlign('vcenter');
			$this->formatTopHeaderRight->setAlign('right');
			$this->formatBottomHeaderRight =& $this->addFormat();
			$this->formatBottomHeaderRight->setItalic();
			$this->formatBottomHeaderRight->setBottom(2);
			$this->formatBottomHeaderRight->setBottomColor('gray');
			$this->formatBottomHeaderRight->setAlign('vcenter');
			$this->formatBottomHeaderRight->setAlign('right');
			$this->formatFooter =& $this->addFormat();
			$this->formatFooter->setTop(2);
			$this->formatFooter->setTopColor('gray');
		}

		// Check a given name to see if it's a valid Excel worksheet name,
		// and fix if necessary
		public function worksheetNameGenerator($name) {
			// First, strip out characters which aren't allowed
			$illegal_chars = array(':', '\\', '/', '?', '*', '[', ']');
			for ($i = 0; $i < count($illegal_chars); $i++) {
				$name = str_replace($illegal_chars[$i], '', $name);
			}
			// Now, if name is longer than 31 chars, truncate it
			if (strlen($name) > 31) {
				$name = substr($name, 0, 31);
			}
			return $name;
		}

		public function NumFormat($dec) {
			if (!isset($this->formatAmount[$dec])) {
				$dec = (int)$dec;
				//$tsep = Config::get('separators_thousands',User::tho_sep());
				//$dsep = Config::get('separators_decimal',User::dec_sep());
				$tsep = ',';
				$dsep = '.';
				$format = "###{$tsep}###{$tsep}###{$tsep}##0";
				if ($dec > 0) {
					$format .= "{$dsep}" . str_repeat('0', $dec);
				}
				$this->formatAmount[$dec] =& $this->addFormat();
				$this->formatAmount[$dec]->setNumFormat($format);
				$this->formatAmount[$dec]->setAlign('right');
			}
			return $this->formatAmount[$dec];
		}

		public function Font($fontname = '', $style = 'normal') {
		}

		public function Info($params, $cols, $headers, $aligns, $cols2 = null, $headers2 = null, $aligns2 = null) {
			$this->company = DB_Company::get_prefs();
			$year = DB_Company::get_current_fiscalyear();
			if ($year['closed'] == 0) {
				$how = _("Active");
			} else {
				$how = _("Closed");
			}
			$this->fiscal_year = Dates::sql2date($year['begin']) . " - " . Dates::sql2date($year['end']) . " (" . $how . ")";
			$this->user = User::get()->name;
			$this->host = $_SERVER['SERVER_NAME'];
			$this->params = $params;
			$this->cols = $cols;
			$this->headers = $headers;
			$this->aligns = $aligns;
			$this->cols2 = $cols2;
			$this->headers2 = $headers2;
			$this->aligns2 = $aligns2;
			$this->numcols = count($this->headers);
			$tcols = count($this->headers2);
			if ($tcols > $this->numcols) {
				$this->numcols = $tcols;
			}
			for ($i = 0; $i < $this->numcols; $i++) {
				$this->sheet->setColumn($i, $i, $this->px2units($this->cols[$i + 1] - $this->cols[$i]));
			}
		}

		public function Header() {
			$tcol = $this->numcols - 1;
			$this->sheet->setRow($this->y, 20);
			for ($i = 0; $i < $this->numcols; $i++) {
				$this->sheet->writeBlank($this->y, $i, $this->formatTitle);
			}
			$this->sheet->writeString($this->y, 0, $this->title, $this->formatTitle);
			$this->sheet->mergeCells($this->y, 0, $this->y, $tcol);
			$this->NewLine();
			$str = _("Print Out Date") . ':';
			$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
			$this->sheet->writeString($this->y, 1, Dates::Today() . " " . Dates::Now(), $this->formatLeft);
			$this->sheet->writeString($this->y, $tcol - 1, $this->company['coy_name'], $this->formatLeft);
			$this->sheet->mergeCells($this->y, $tcol - 1, $this->y, $tcol);
			$this->NewLine();
			$str = _("Fiscal Year") . ':';
			$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
			$str = $this->fiscal_year;
			$this->sheet->writeString($this->y, 1, $str, $this->formatLeft);
			$this->sheet->writeString($this->y, $tcol - 1, $this->host, $this->formatLeft);
			$this->sheet->mergeCells($this->y, $tcol - 1, $this->y, $tcol);
			for ($i = 1; $i < count($this->params); $i++) {
				if ($this->params[$i]['from'] != '') {
					$this->NewLine();
					$str = $this->params[$i]['text'] . ':';
					$this->sheet->writeString($this->y, 0, $str);
					$str = $this->params[$i]['from'];
					if ($this->params[$i]['to'] != '') {
						$str .= " - " . $this->params[$i]['to'];
					}
					$this->sheet->writeString($this->y, 1, $str, $this->formatLeft);
					if ($i == 1) {
						$this->sheet->writeString($this->y, $tcol - 1, $this->user, $this->formatLeft);
						$this->sheet->mergeCells($this->y, $tcol - 1, $this->y, $tcol);
					}
				}
			}
			if ($this->params[0] != '') // Comments
			{
				$this->NewLine();
				$str = _("Comments") . ':';
				$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
				$this->sheet->writeString($this->y, 1, $this->params[0], $this->formatLeft);
			}
			$this->NewLine();
			if ($this->headers2 != null) {
				for ($i = 0, $j = 0; $i < $this->numcols; $i++) {
					if ($this->cols2[$j] >= $this->cols[$i] && $this->cols2[$j] <= $this->cols[$i + 1]) {
						if ($this->aligns2[$j] == "right") {
							$this->sheet->writeString($this->y, $i, $this->headers2[$j], $this->formatHeaderRight);
						} else {
							$this->sheet->writeString($this->y, $i, $this->headers2[$j], $this->formatHeaderLeft);
						}
						$j++;
					} else {
						$this->sheet->writeString($this->y, $i, "", $this->formatHeaderLeft);
					}
				}
				$this->NewLine();
			}
			for ($i = 0; $i < $this->numcols; $i++) {
				if (!isset($this->headers[$i])) {
					$header = "";
				} else {
					$header = $this->headers[$i];
				}
				if ($this->aligns[$i] == "right") {
					$this->sheet->writeString($this->y, $i, $header, $this->formatHeaderRight);
				} else {
					$this->sheet->writeString($this->y, $i, $header, $this->formatHeaderLeft);
				}
			}
			$this->NewLine();
		}

		public function Header2($myrow, $branch, $sales_order, $bankaccount, $doctype) {
			return;
		}

		// Alternate header style - primary differences are for PDFs
		public function Header3() {
			// Flag to make sure we only print the company name once
			$companyNamePrinted = false;
			$this->y = 0;
			$tcol = $this->numcols - 1;
			$this->sheet->setRow($this->y, 20);
			// Title
			for ($i = 0; $i < $this->numcols; $i++) {
				$this->sheet->writeBlank($this->y, $i, $this->formatTitle);
			}
			$this->sheet->writeString($this->y, 0, $this->title, $this->formatTitle);
			$this->sheet->mergeCells($this->y, 0, $this->y, $tcol);
			// Dimension 1 - optional
			// - only print if available and not blank
			if (count($this->params) > 3) {
				if ($this->params[3]['from'] != '') {
					$this->NewLine();
					$str = $this->params[3]['text'] . ':';
					$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
					$this->sheet->writeString($this->y, 1, $this->params[3]['from'], $this->formatLeft);
					// Company Name - at end of this row
					if (!$companyNamePrinted) {
						$this->sheet->writeString($this->y, $tcol - 1, $this->company['coy_name'], $this->formatLeft);
						$this->sheet->mergeCells($this->y, $tcol - 1, $this->y, $tcol);
						$companyNamePrinted = true;
					}
				}
			}
			// Dimension 2 - optional
			// - only print if available and not blank
			if (count($this->params) > 4) {
				if ($this->params[4]['from'] != '') {
					$this->NewLine();
					$str = $this->params[4]['text'] . ':';
					$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
					$this->sheet->writeString($this->y, 1, $this->params[4]['from'], $this->formatLeft);
					// Company Name - at end of this row
					if (!$companyNamePrinted) {
						$this->sheet->writeString($this->y, $tcol - 1, $this->company['coy_name'], $this->formatLeft);
						$this->sheet->mergeCells($this->y, $tcol - 1, $this->y, $tcol);
						$companyNamePrinted = true;
					}
				}
			}
			// Tags - optional
			// TBD!!!
			// Report Date - time period covered
			// - can specify a range, or just the end date (and the report contents
			// should make it obvious what the beginning date is)
			$this->NewLine();
			$str = _("Report Date") . ':';
			$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
			$str = '';
			if ($this->params[1]['from'] != '') {
				$str = $this->params[1]['from'] . ' - ';
			}
			$str .= $this->params[1]['to'];
			$this->sheet->writeString($this->y, 1, $str, $this->formatLeft);
			// Company Name - at end of this row
			if (!$companyNamePrinted) {
				$this->sheet->writeString($this->y, $tcol - 1, $this->company['coy_name'], $this->formatLeft);
				$this->sheet->mergeCells($this->y, $tcol - 1, $this->y, $tcol);
				$companyNamePrinted = true;
			}
			// Timestamp of when this copy of the report was generated
			$this->NewLine();
			$str = _("Generated At") . ':';
			$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
			$this->sheet->writeString($this->y, 1, Dates::Today() . " " . Dates::Now(), $this->formatLeft);
			// Name of the user that generated this copy of the report
			$this->NewLine();
			$str = _("Generated By") . ':';
			$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
			$str = $this->user;
			$this->sheet->writeString($this->y, 1, $str, $this->formatLeft);
			// Comments - display any user-generated comments for this copy of the report
			if ($this->params[0] != '') {
				$this->NewLine();
				$str = _("Comments") . ':';
				$this->sheet->writeString($this->y, 0, $str, $this->formatLeft);
				$this->sheet->writeString($this->y, 1, $this->params[0], $this->formatLeft);
			}
			$this->NewLine();
			if ($this->headers2 != null) {
				for ($i = 0, $j = 0; $i < $this->numcols; $i++) {
					if ($this->cols2[$j] >= $this->cols[$i] && $this->cols2[$j] <= $this->cols[$i + 1]) {
						if ($this->aligns2[$j] == "right") {
							$this->sheet->writeString($this->y, $i, $this->headers2[$j], $this->formatTopHeaderRight);
						} else {
							$this->sheet->writeString($this->y, $i, $this->headers2[$j], $this->formatTopHeaderLeft);
						}
						$j++;
					} else {
						$this->sheet->writeString($this->y, $i, "", $this->formatTopHeaderLeft);
					}
				}
				$this->NewLine();
			}
			for ($i = 0; $i < $this->numcols; $i++) {
				if (!isset($this->headers[$i])) {
					$header = "";
				} else {
					$header = $this->headers[$i];
				}
				if ($this->aligns[$i] == "right") {
					if ($this->headers2 == null) {
						$this->sheet->writeString($this->y, $i, $header, $this->formatHeaderRight);
					} else {
						$this->sheet->writeString($this->y, $i, $header, $this->formatBottomHeaderRight);
					}
				} else if ($this->headers2 == null) {
					$this->sheet->writeString($this->y, $i, $header, $this->formatHeaderLeft);
				} else {
					$this->sheet->writeString($this->y, $i, $header, $this->formatBottomHeaderLeft);
				}
			}
			$this->NewLine();
		}

		/**
		 * Format a numeric string date into something nicer looking.
		 *
		 * @param string $date					Date string to be formatted.
		 * @param int		$input_format	Format of the input string.	Possible values are:<ul><li>0: user's default (default)</li></ul>
		 * @param int		$output_format Format of the output string.	Possible values are:<ul><li>0: Month (word) Day (numeric), 4-digit Year - Example: January 1, 2000 (default)</li><li>1: Month 4-digit Year - Example: January 2000</li><li>2: Month Abbreviation 4-digit Year - Example: Jan 2000</li></ul>
		 *
		 * @access public
		 */
		public function DatePrettyPrint($date, $input_format = 0, $output_format = 0) {
			if ($date != '') {
				$date = Dates::date2sql($date);
				$year = (int)(substr($date, 0, 4));
				$month = (int)(substr($date, 5, 2));
				$day = (int)(substr($date, 8, 2));
				if ($output_format == 0) {
					return (date('F j, Y', mktime(12, 0, 0, $month, $day, $year)));
				} elseif ($output_format == 1) {
					return (date('F Y', mktime(12, 0, 0, $month, $day, $year)));
				} elseif ($output_format == 2) {
					return (date('M Y', mktime(12, 0, 0, $month, $day, $year)));
				}
			} else {
				return $date;
			}
		}

		public function AddImage($logo, $x, $y, $w, $h) {
			return;
		}

		public function SetDrawColor($r, $g, $b) {
			return;
		}

		public function SetTextColor($r, $g, $b) {
			return;
		}

		public function SetFillColor($r, $g, $b) {
			return;
		}

		public function GetCellPadding() {
			return 0;
		}

		public function SetCellPadding($pad) {
			return;
		}

		public function Text($c, $txt, $n = 0, $corr = 0, $r = 0, $align = 'left', $border = 0, $fill = 0, $link = NULL, $stretch = 0) {
			return;
		}

		public function TextWrap($xpos, $ypos, $len, $str, $align = 'left', $border = 0, $fill = 0, $link = NULL, $stretch = 0) {
			return;
		}

		public function TextCol($c, $n, $txt, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = NULL, $stretch = 0) {
			$txt = html_entity_decode($txt);
			if ($this->aligns[$c] == 'right') {
				$this->sheet->writeString($this->y, $c, $txt, $this->formatRight);
			} else {
				$this->sheet->writeString($this->y, $c, $txt, $this->formatLeft);
			}
			if ($n - $c > 1) {
				$this->sheet->mergeCells($this->y, $c, $this->y, $n - 1);
			}
		}

		public function AmountCol($c, $n, $txt, $dec = 0, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = NULL, $stretch = 0,
			$color_red = false) {
			if (!is_numeric($txt)) {
				$txt = 0;
			}
			$this->sheet->writeNumber($this->y, $c, $txt, $this->NumFormat($dec));
		}

		public function AmountCol2($c, $n, $txt, $dec = 0, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = NULL, $stretch = 0,
			$color_red = false, $amount_locale = NULL, $amount_format = NULL) {
			if (!is_numeric($txt)) {
				$txt = 0;
			}
			$this->sheet->writeNumber($this->y, $c, $txt, $this->NumFormat($dec));
		}

		public function DateCol($c, $n, $txt, $conv = false, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = NULL, $stretch = 0) {
			if (!$conv) {
				$txt = Dates::date2sql($txt);
			}
			list($year, $mo, $day) = explode("-", $txt);
			$date = $this->ymd2date((int)$year, (int)$mo, (int)$day);
			$this->sheet->writeNumber($this->y, $c, $date, $this->formatDate);
		}

		public function TextCol2($c, $n, $txt, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = NULL, $stretch = 0) {
			$txt = html_entity_decode($txt);
			$this->sheet->writeString($this->y, $c, $txt, $this->formatLeft);
			if ($n - $c > 1) {
				$this->sheet->mergeCells($this->y, $c, $this->y, $n - 1);
			}
		}

		public function TextColLines($c, $n, $txt, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = NULL, $stretch = 0) {
			return;
		}

		public function TextWrapLines($c, $width, $txt, $align = 'left', $border = 0, $fill = 0, $link = NULL, $stretch = 0) {
			return;
		}

		/**
		 * Crude text wrap calculator based on PDF version.
		 */
		public function TextWrapCalc($txt, $width, $spacebreak = false) {
			// Assume an average character width
			$avg_char_width = 5;
			$ret = "";
			$txt2 = $txt;
			$w = strlen($txt) * $avg_char_width;
			if ($w > $width && $w > 0 && $width != 0) {
				$n = strlen($txt);
				$k = intval($n * $width / $w);
				if ($k > 0 && $k < $n) {
					$txt2 = substr($txt, 0, $k);
					if ($spacebreak && (($pos = strrpos($txt2, " ")) !== false)) {
						$txt2 = substr($txt2, 0, $pos);
						$ret = substr($txt, $pos + 1);
					} else {
						$ret = substr($txt, $k);
					}
				}
			}
			return array($txt2, $ret);
		}

		public function SetLineStyle($style) {
			return;
		}

		public function SetLineWidth($width) {
			return;
		}

		public function LineTo($from, $row, $to, $row2) {
			return;
		}

		public function Line($row, $height = 0) {
			return;
		}

		public function UnderlineCell($c, $r = 0, $type = 1, $linewidth = 0, $style = array()) {
			return;
		}

		public function NewLine($l = 1, $np = 0, $h = NULL) {
			$this->y += $l;
		}

		public function ymd2Date($year, $mon, $day) // XLS internal date representation is a number between 1900-01-01 and 2078-12-31
		{ // if we need the time part too, we have to add this value after a decimalpoint.
			$mo = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
			$BASE = 1900;
			$MAXYEAR = 2075;
			if (($year % 4) == 0) {
				$mo[2]++;
			}
			if ($mon < 1) {
				$mon = 1;
			} elseif ($mon > 12) {
				$mon = 12;
			}
			if ($day < 1) {
				$day = 1;
			} elseif ($day > $mo[$mon]) {
				$day = $mo[$mon];
			}
			if ($year < $BASE) {
				$year = $BASE;
			} elseif ($year > $MAXYEAR) {
				$year = $MAXYEAR;
			}
			$jul = (int)$day;
			for ($n = 1; $n < $mon; $n++) {
				$jul += $mo[$n];
			}
			for ($n = $BASE; $n < $year; $n++) {
				$jul += 365;
				if (($n % 4) == 0) {
					$jul++;
				}
			}
			return $jul;
		}

		public function px2units($px) // XLS app conversion. Not bulletproof.
		{
			$excel_column_width_factor = 256;
			$unit_offset_length = $this->excelColWidthFactor;
			return ($px / $unit_offset_length);
		}

		public function End($email = 0, $subject = null, $myrow = null, $doctype = 0) {
			for ($i = 0; $i < $this->numcols; $i++) {
				$this->sheet->writeBlank($this->y, $i, $this->formatFooter);
			}
			$this->sheet->mergeCells($this->y, 0, $this->y, $this->numcols - 1);
			$this->close();
			// first have a look through the directory,
			// and remove old temporary pdfs
			if ($d = @opendir($this->path)) {
				while (($file = readdir($d)) !== false) {
					if (!is_file($this->path . '/' . $file) || $file == 'index.php') {
						continue;
					}
					// then check to see if this one is too old
					$ftime = filemtime($this->path . '/' . $file);
					// seems 3 min is enough for any report download, isn't it?
					if (time() - $ftime > 180) {
						unlink($this->path . '/' . $file);
					}
				}
				closedir($d);
			}
			Display::meta_forward($_SERVER['PHP_SELF'], "xls=1&filename=$this->filename&unique=$this->unique_name");
			exit();
		}
	}