<?php
  namespace ADV\App\Reports;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\Config;
  use ADV\Core\Num;
  use ADV\App\Dates;
  use DB_Company;
  use ADV\App\Page;
  use Reports_Printer_Remote;
  use ADV\Core\Ajax;
  use ADV\App\User;
  use Printer;
  use ADV\Core\Event;
  use ADV\App\Debtor\Debtor;

  /**

   */
  class PDF extends \Cpdf {
    /** @var array **/
    public $size;
    /** @var */
    public $company;
    /** @var */
    public $user;
    /** @var */
    public $host;
    /** @var */
    public $fiscal_year;
    /** @var string **/
    public $title;
    /** @var string **/
    public $filename;
    /** @var int **/
    public $pageWidth;
    /** @var int **/
    public $pageHeight;
    /** @var int **/
    public $topMargin;
    /** @var int **/
    public $bottomMargin;
    /** @var int **/
    public $leftMargin;
    /** @var int **/
    public $rightMargin;
    /** @var int **/
    public $endLine;
    /** @var int **/
    public $lineHeight;
    //public $rtl;
    /** @var */
    public $cols;
    /** @var */
    public $params;
    /** @var */
    public $headers;
    /** @var */
    public $aligns;
    /** @var */
    public $headers2;
    /** @var */
    public $aligns2;
    /** @var */
    public $cols2;
    /** @var int **/
    public $pageNumber;
    /** @var int **/
    public $fontSize;
    /** @var int **/
    public $oldFontSize;
    /** @var string **/
    public $currency;
    /** @var */
    public $companyLogoEnable;
    // select whether to use a company logo graphic in some header templates
    /** @var bool **/
    public $scaleLogoWidth;
    /** @var */
    public $footerEnable;
    // select whether to print a page footer or not
    /** @var */
    public $footerText;
    // store user-generated footer text
    /** @var string **/
    public $headerFunc; // store the name of the currently selected header public function
    /**
     * @param string       $title
     * @param array|string $filename
     * @param mixed|string $size
     * @param int          $fontsize
     * @param string       $orientation
     * @param null         $margins
     * @param null         $excelColWidthFactor
     */
    public function __construct($title, $filename, $security_level, $size = 'A4', $fontsize = 9, $orientation = 'P', $margins = null, $excelColWidthFactor = null) {
      if (!User::i()->hasAccess($security_level)) {
        Event::error(_("The security settings on your account do not permit you to print this report"));
        Page::end();
        exit;
      }
      // Page margins - if user-specified, use those. Otherwise, use defaults below.
      if (isset($margins)) {
        $this->topMargin    = $margins['top'];
        $this->bottomMargin = $margins['bottom'];
        $this->leftMargin   = $margins['left'];
        $this->rightMargin  = $margins['right'];
      }
      // Page orientation - P: portrait, L: landscape
      $orientation = strtoupper($orientation);
      // Page size name
      switch (strtoupper($size)) {
        default:
        case 'A4':
          // Portrait
          if ($orientation == 'P') {
            $this->pageWidth  = 595;
            $this->pageHeight = 842;
            if (!isset($margins)) {
              $this->topMargin    = 40;
              $this->bottomMargin = 30;
              $this->leftMargin   = 40;
              $this->rightMargin  = 30;
            }
          } // Landscape
          else {
            $this->pageWidth  = 842;
            $this->pageHeight = 595;
            if (!isset($margins)) {
              $this->topMargin    = 30;
              $this->bottomMargin = 30;
              $this->leftMargin   = 40;
              $this->rightMargin  = 30;
            }
          }
          break;
        case 'A3':
          // Portrait
          if ($orientation == 'P') {
            $this->pageWidth  = 842;
            $this->pageHeight = 1190;
            if (!isset($margins)) {
              $this->topMargin    = 50;
              $this->bottomMargin = 50;
              $this->leftMargin   = 50;
              $this->rightMargin  = 40;
            }
          } // Landscape
          else {
            $this->pageWidth  = 1190;
            $this->pageHeight = 842;
            if (!isset($margins)) {
              $this->topMargin    = 50;
              $this->bottomMargin = 50;
              $this->leftMargin   = 50;
              $this->rightMargin  = 40;
            }
          }
          break;
        case 'LETTER':
          // Portrait
          if ($orientation == 'P') {
            $this->pageWidth  = 612;
            $this->pageHeight = 792;
            if (!isset($margins)) {
              $this->topMargin    = 30;
              $this->bottomMargin = 30;
              $this->leftMargin   = 30;
              $this->rightMargin  = 25;
            }
          } // Landscape
          else {
            $this->pageWidth  = 792;
            $this->pageHeight = 612;
            if (!isset($margins)) {
              $this->topMargin    = 30;
              $this->bottomMargin = 30;
              $this->leftMargin   = 30;
              $this->rightMargin  = 25;
            }
          }
          break;
        case 'LEGAL':
          // Portrait
          if ($orientation == 'P') {
            $this->pageWidth  = 612;
            $this->pageHeight = 1008;
            if (!isset($margins)) {
              $this->topMargin    = 50;
              $this->bottomMargin = 40;
              $this->leftMargin   = 30;
              $this->rightMargin  = 25;
            }
          } // Landscape
          else {
            $this->pageWidth  = 1008;
            $this->pageHeight = 612;
            if (!isset($margins)) {
              $this->topMargin    = 50;
              $this->bottomMargin = 40;
              $this->leftMargin   = 30;
              $this->rightMargin  = 25;
            }
          }
          break;
      }
      $this->size           = array(0, 0, $this->pageWidth, $this->pageHeight);
      $this->title          = $title;
      $this->filename       = $filename . ".pdf";
      $this->pageNumber     = 0;
      $this->endLine        = $this->pageWidth - $this->rightMargin;
      $this->lineHeight     = 12;
      $this->fontSize       = $fontsize;
      $this->oldFontSize    = 0;
      $this->row            = $this->pageHeight - $this->topMargin;
      $this->currency       = '';
      $this->scaleLogoWidth = false; // if Logo, scale on width (else height).
      $this->headerFunc     = 'Header'; // default to the original header template
      $rtl                  = ($_SESSION['language']->dir === 'rtl' ? 'rtl' : 'ltr');
      $code                 = $_SESSION['language']->code;
      $enc                  = strtoupper($_SESSION['language']->encoding);
      // for the language array in class.pdf.inc
      $l = array(
        'a_meta_charset'  => $enc,
        'a_meta_dir'      => $rtl,
        'a_meta_language' => $code,
        'w_page'          => 'page'
      );
      parent::__construct($size, $l, $orientation);
    }
    /**
     * Select the font and style to use for following output until
     * it's changed again.
     * $style is either:
     * * a special case string (for backwards compatible with older code):
     * * bold
     * * italic
     * * or a case-insensitive string where each char represents a style choice
     * and you can use more than one or none at all. Possible choices:
     * * empty string: regular
     * * B: bold
     * * I: italic
     * * U: underline
     * * D: line trough (aka "strike through")
     * $fontname should be a standard PDF font (like 'times', 'helvetica' or 'courier')
     * or one that's been installed on your system (see TCPDF docs for details).
     * An empty string can also be used which will retain the font currently in use if
     * you just want to change the style.
     *
     * @param string $style
     * @param string $fontname
     *
     * @return void
     */
    /**
     * @param string $style
     * @param string $fontname
     *
     * @return void
     */
    public function Font($style = '', $fontname = '') {
      $this->selectFont($fontname, $style);
    }
    /**
     * @param        $params
     * @param        $cols
     * @param        $headers
     * @param        $aligns
     * @param null   $cols2
     * @param null   $headers2
     * @param null   $aligns2
     * @param bool   $companylogoenable
     * @param bool   $footerenable
     * @param string $footertext
     *
     * @return void
     */
    /**
     * @param        $params
     * @param        $cols
     * @param        $headers
     * @param        $aligns
     * @param null   $cols2
     * @param null   $headers2
     * @param null   $aligns2
     * @param bool   $companylogoenable
     * @param bool   $footerenable
     * @param string $footertext
     *
     * @return void
     */
    public function Info($params, $cols, $headers, $aligns, $cols2 = null, $headers2 = null, $aligns2 = null, $companylogoenable = false, $footerenable = false, $footertext = '') {
      $this->addinfo('Title', $this->title);
      $this->addinfo('Subject', $this->title);
      $this->addinfo('Author', APP_TITLE . ' ' . VERSION);
      $this->addinfo('Creator', POWERED_BY . ' - ' . POWERED_URL);
      $year = DB_Company::get_current_fiscalyear();
      if ($year['closed'] == 0) {
        $how = _("Active");
      } else {
        $how = _("Closed");
      }
      $this->fiscal_year = Dates::_sqlToDate($year['begin']) . " - " . Dates::_sqlToDate($year['end']) . " (" . $how . ")";
      $this->company     = DB_Company::get_prefs();
      $this->user        = User::i()->name;
      $this->host        = $_SERVER['SERVER_NAME'];
      $this->params      = $params;
      $this->cols        = $cols;
      for ($i = 0; $i < count($this->cols); $i++) {
        $this->cols[$i] += $this->leftMargin;
      }
      $this->headers = $headers;
      $this->aligns  = $aligns;
      $this->cols2   = $cols2;
      if ($this->cols2 != null) {
        for ($i = 0; $i < count($this->cols2); $i++) {
          $this->cols2[$i] += $this->leftMargin;
        }
      }
      $this->headers2 = $headers2;
      $this->aligns2  = $aligns2;
      // Set whether to display company logo in some header templates
      $this->companyLogoEnable = $companylogoenable;
      // Store footer settings
      $this->footerEnable = $footerenable;
      $this->footerText   = $footertext;
    }
    public function Header() {
      $companyCol = $this->endLine - 150;
      $titleCol   = $this->leftMargin + 100;
      $this->pageNumber++;
      if ($this->pageNumber > 1) {
        $this->newPage();
      }
      $this->row = $this->pageHeight - $this->topMargin;
      $this->SetDrawColor(128, 128, 128);
      $this->Line($this->row + 5, 1);
      $this->NewLine();
      $this->fontSize += 4;
      $this->Font('bold');
      $this->Text($this->leftMargin, $this->title, $companyCol);
      $this->Font();
      $this->fontSize -= 4;
      $this->Text($companyCol, $this->company['coy_name']);
      $this->row -= ($this->lineHeight + 4);
      $str = _("Print Out Date") . ':';
      $this->Text($this->leftMargin, $str, $titleCol);
      $str = Dates::_today() . ' ' . Dates::_now();
      if ($this->company['time_zone']) {
        $str .= ' ' . date('O') . ' GMT';
      }
      $this->Text($titleCol, $str, $companyCol);
      $this->Text($companyCol, $this->host);
      $this->NewLine();
      $str = _("Fiscal Year") . ':';
      $this->Text($this->leftMargin, $str, $titleCol);
      $str = $this->fiscal_year;
      $this->Text($titleCol, $str, $companyCol);
      $this->Text($companyCol, $this->user);
      for ($i = 1; $i < count($this->params); $i++) {
        if ($this->params[$i]['from'] != '') {
          $this->NewLine();
          $str = $this->params[$i]['text'] . ':';
          $this->Text($this->leftMargin, $str, $titleCol);
          $str = $this->params[$i]['from'];
          if ($this->params[$i]['to'] != '') {
            $str .= " - " . $this->params[$i]['to'];
          }
          $this->Text($titleCol, $str, $companyCol);
        }
      }
      if (isset($this->params[0]) && $this->params[0] != '') { // Comments
        $this->NewLine();
        $str = _("Comments") . ':';
        $this->Text($this->leftMargin, $str, $titleCol);
        $this->Font('bold');
        $this->Text($titleCol, $this->params[0], $this->endLine - 35);
        $this->Font();
      }
      $str = _("Page") . ' ' . $this->pageNumber;
      $this->Text($this->endLine - 38, $str);
      $this->Line($this->row - 5, 1);
      $this->row -= ($this->lineHeight + 6);
      $this->Font('italic');
      if ($this->headers2 != null) {
        $count = count($this->headers2);
        for ($i = 0; $i < $count; $i++) {
          $this->TextCol2($i, $i + 1, $this->headers2[$i]);
        }
        $this->NewLine();
      }
      $count = count($this->headers);
      for ($i = 0; $i < $count; $i++) {
        $this->TextCol($i, $i + 1, $this->headers[$i]);
      }
      $this->Font();
      $this->Line($this->row - 5, 1);
      $this->NewLine(2);
    }
    /**
     * @param      $myrow
     * @param null $branch
     * @param null $sales_order
     * @param null $bankaccount
     * @param null $doctype
     *
     * @return void
     */
    public function Header2($myrow, $branch = null, $sales_order = null, $bankaccount = null, $doctype = null) {
      global $print_as_quote, $packing_slip;
      $this->pageNumber++;
      if ($this->pageNumber > 1) {
        $this->newPage();
      }
      $header2type = true;
      if ($doctype == ST_PROFORMA || $doctype == ST_PROFORMAQ) {
        $isproforma = true;
        $doctype    = ($doctype == ST_PROFORMA) ? ST_SALESORDER : ST_SALESQUOTE;
      }
      if ($doctype == ST_STATEMENT) {
        $customer = new Debtor($myrow['debtor_id']);
        // include("includes/lang/en_AU/statement.php");
        // include("includes/lang/en_AU/statement_head.php");
        //} else
      }
      include(PATH_REPORTS . 'includes' . DS . 'doctext.php');
      include(PATH_REPORTS . 'includes' . DS . 'header.php');
      // }
      $this->row = isset($temp) ? $temp : $this->row;
    }
    // Alternate header style which also supports a simple footer
    public function Header3() {
      // Make this header the default for the current report ( used by NewLine() )
      $this->headerFunc = 'Header3';
      // Turn off cell padding for the main report header, restoring the current setting later
      $oldcMargin = $this->cMargin;
      $this->SetCellPadding(0);
      // Set some constants which control header item layout
      // only set them once or the PHP interpreter gets angry
      if ($this->pageNumber == 0) {
        define('COMPANY_WIDTH', 150);
        define('LOGO_HEIGHT', 50);
        define('LOGO_Y_POS_ADJ_FACTOR', 0.74);
        define('LABEL_WIDTH', 80);
        define('PAGE_NUM_WIDTH', 60);
        define('TITLE_FONT_SIZE', 14);
        define('HEADER1_FONT_SIZE', 10);
        define('HEADER2_FONT_SIZE', 9);
        define('FOOTER_FONT_SIZE', 10);
        define('FOOTER_MARGIN', 4);
      }
      // Set some variables which control header item layout
      $companyCol     = $this->endLine - COMPANY_WIDTH;
      $headerFieldCol = $this->leftMargin + LABEL_WIDTH;
      $pageNumCol     = $this->endLine - PAGE_NUM_WIDTH;
      $footerCol      = $this->leftMargin + PAGE_NUM_WIDTH;
      $footerRow      = $this->bottomMargin - FOOTER_MARGIN;
      // Calling this public function generates a new PDF page after the first instance
      $this->pageNumber++;
      if ($this->pageNumber > 1) {
        //			// TODO: experimenting with line drawing to highlight current period
        //			$this->SetLineWidth(1);
        //			$this->LineTo($this->cols[3], 33, $this->cols[3], 534);
        //			$this->LineTo($this->cols[4], 33, $this->cols[4], 534);
        //			$this->SetLineWidth(0.1);
        $this->newPage();
      }
      $this->row = $this->pageHeight - $this->topMargin;
      // Set the color of dividing lines we'll draw
      $oldDrawColor = $this->GetDrawColor();
      $this->SetDrawColor(128, 128, 128);
      // Tell TCPDF that we want to use its alias system to track the total number of pages
      $this->AliasNbPages();
      // Footer
      if ($this->footerEnable) {
        $this->Line($footerRow, 1);
        $prevFontSize   = $this->fontSize;
        $this->fontSize = FOOTER_FONT_SIZE;
        $this->TextWrap(
          $footerCol,
          $footerRow - ($this->fontSize + 1),
          $pageNumCol - $footerCol,
          $this->footerText,
          $align = 'center',
          $border = 0,
          $fill = 0,
          $link = null,
          $stretch = 1
        );
        $this->TextWrap(
          $pageNumCol,
          $footerRow - ($this->fontSize + 1),
          PAGE_NUM_WIDTH,
          _("Page") . ' ' . $this->pageNumber . '/' . $this->getAliasNbPages(),
          $align = 'right',
          $border = 0,
          $fill = 0,
          $link = null,
          $stretch = 1
        );
        $this->fontSize = $prevFontSize;
      }
      //
      // Header
      //
      // Print gray line across the page
      $this->Line($this->row + 8, 1);
      $this->NewLine();
      // Print the report title nice and big
      $oldFontSize    = $this->fontSize;
      $this->fontSize = TITLE_FONT_SIZE;
      $this->Font('B');
      $this->Text($this->leftMargin, $this->title, $companyCol);
      $this->fontSize = HEADER1_FONT_SIZE;
      // Print company logo if present and requested, or else just print company name
      if ($this->companyLogoEnable && ($this->company['coy_logo'] != '')) {
        // Build a string specifying the location of the company logo file
        $logo = PATH_COMPANY . "images/" . $this->company['coy_logo'];
        // Width being zero means that the image will be scaled to the specified height
        // keeping its aspect ratio intact.
        if ($this->scaleLogoWidth) {
          $this->AddImage($logo, $companyCol, $this->row, COMPANY_WIDTH, 0);
        } else {
          $this->AddImage($logo, $companyCol, $this->row - (LOGO_HEIGHT * LOGO_Y_POS_ADJ_FACTOR), 0, LOGO_HEIGHT);
        }
      } else {
        $this->Text($companyCol, $this->company['coy_name']);
      }
      // Dimension 1 - optional
      // - only print if available and not blank
      if (count($this->params) > 3) {
        if ($this->params[3]['from'] != '') {
          $this->NewLine(1, 0, $this->fontSize + 2);
          $str = $this->params[3]['text'] . ':';
          $this->Text($this->leftMargin, $str, $headerFieldCol);
          $str = $this->params[3]['from'];
          $this->Text($headerFieldCol, $str, $companyCol);
        }
      }
      // Dimension 2 - optional
      // - only print if available and not blank
      if (count($this->params) > 4) {
        if ($this->params[4]['from'] != '') {
          $this->NewLine(1, 0, $this->fontSize + 2);
          $str = $this->params[4]['text'] . ':';
          $this->Text($this->leftMargin, $str, $headerFieldCol);
          $str = $this->params[4]['from'];
          $this->Text($headerFieldCol, $str, $companyCol);
        }
      }
      // Tags - optional
      // if present, it's an array of tag names
      if (count($this->params) > 5) {
        if ($this->params[5]['from'] != '') {
          $this->NewLine(1, 0, $this->fontSize + 2);
          $str = $this->params[5]['text'] . ':';
          $this->Text($this->leftMargin, $str, $headerFieldCol);
          $str = '';
          for ($i = 0; $i < count($this->params[5]['from']); $i++) {
            if ($i != 0) {
              $str .= ', ';
            }
            $str .= $this->params[5]['from'][$i];
          }
          $this->Text($headerFieldCol, $str, $companyCol);
        }
      }
      // Report Date - time period covered
      // - can specify a range, or just the end date (and the report contents
      // should make it obvious what the beginning date is)
      $this->NewLine(1, 0, $this->fontSize + 2);
      $str = _("Report Period") . ':';
      $this->Text($this->leftMargin, $str, $headerFieldCol);
      $str = '';
      if (isset($this->params[1]['from']) && $this->params[1]['from'] != '') {
        $str = $this->params[1]['from'] . ' - ';
      }
      $str .= $this->params[1]['to'];
      $this->Text($headerFieldCol, $str, $companyCol);
      // Turn off Bold
      $this->Font();
      $this->NewLine(1, 0, $this->fontSize + 1);
      // Make the remaining report headings a little less important
      $this->fontSize = HEADER2_FONT_SIZE;
      // Timestamp of when this copy of the report was generated
      $str = _("Generated At") . ':';
      $this->Text($this->leftMargin, $str, $headerFieldCol);
      $str = Dates::_today() . ' ' . Dates::_now();
      if ($this->company['time_zone']) {
        $str .= ' ' . date('O') . ' GMT';
      }
      $this->Text($headerFieldCol, $str, $companyCol);
      // Name of the user that generated this copy of the report
      $this->NewLine(1, 0, $this->fontSize + 1);
      $str = _("Generated By") . ':';
      $this->Text($this->leftMargin, $str, $headerFieldCol);
      $str = $this->user;
      $this->Text($headerFieldCol, $str, $companyCol);
      // Display any user-generated comments for this copy of the report
      if ($this->params[0] != '') { // Comments
        $this->NewLine(1, 0, $this->fontSize + 1);
        $str = _("Comments") . ':';
        $this->Text($this->leftMargin, $str, $headerFieldCol);
        $this->Font('B');
        $this->Text($headerFieldCol, $this->params[0], $companyCol, 0, 0, 'left', 0, 0, $link = null, 1);
        $this->Font();
      }
      // Add page numbering to header if footer is turned off
      if (!$this->footerEnable) {
        $str = _("Page") . ' ' . $this->pageNumber . '/' . $this->getAliasNbPages();
        $this->Text($pageNumCol, $str, 0, 0, 0, 'right', 0, 0, null, 1);
      }
      // Print gray line across the page
      $this->Line($this->row - 5, 1);
      // Restore font size to user-defined size
      $this->fontSize = $oldFontSize;
      // restore user-specified cell padding for column headers
      $this->SetCellPadding($oldcMargin);
      // scoot down the page a bit
      $oldLineHeight    = $this->lineHeight;
      $this->lineHeight = $this->fontSize + 1;
      $this->row -= ($this->lineHeight + 6);
      $this->lineHeight = $oldLineHeight;
      // Print the column headers!
      $this->Font('I');
      if ($this->headers2 != null) {
        $count = count($this->headers2);
        for ($i = 0; $i < $count; $i++) {
          $this->TextCol2($i, $i + 1, $this->headers2[$i], $corr = 0, $r = 0, $border = 0, $fill = 0, $link = null, $stretch = 1);
        }
        $this->NewLine();
      }
      $count = count($this->headers);
      for ($i = 0; $i < $count; $i++) {
        $this->TextCol($i, $i + 1, $this->headers[$i], $corr = 0, $r = 0, $border = 0, $fill = 0, $link = null, $stretch = 1);
      }
      $this->Font();
      $this->NewLine(2);
      // restore user-specified draw color
      $this->SetDrawColor($oldDrawColor[0], $oldDrawColor[1], $oldDrawColor[2]);
    }
    /**
     * Format a numeric string date into something nicer looking.
     *
     * @param string $date          Date string to be formatted.
     * @param int    $input_format  Format of the input string.  Possible values are:<ul><li>0: user's default (default)</li></ul>
     * @param int    $output_format Format of the output string.  Possible values are:<ul><li>0: Month (word) Day (numeric), 4-digit Year - Example: January 1, 2000 (default)</li><li>1: Month 4-digit Year - Example: January 2000</li><li>2: Month Abbreviation 4-digit Year - Example: Jan 2000</li></ul>
     *
     * @return int|string
     * @access public
     */
    public function DatePrettyPrint($date, $input_format = 0, $output_format = 0) {
      if ($date != '') {
        $date  = Dates::_dateToSql($date);
        $year  = (int) (substr($date, 0, 4));
        $month = (int) (substr($date, 5, 2));
        $day   = (int) (substr($date, 8, 2));
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
    /**
     * @param $logo
     * @param $x
     * @param $y
     * @param $w
     * @param $h
     *
     * @return void
     */
    public function AddImage($logo, $x, $y, $w, $h) {
      if (strpos($logo, ".png") || strpos($logo, ".PNG")) {
        $this->addPngFromFile($logo, $x, $y, $w, $h);
      } else {
        $this->addJpegFromFile($logo, $x, $y, $w, $h);
      }
    }
    // Get current draw color setting from TCPDF object; returns array of RGB numbers
    /**
     * @return array
     */
    public function GetDrawColor() {
      // Convert the TCPDF stored DrawColor string into an array of strings
      $colorFields = explode(' ', $this->DrawColor);
      // Test last value: G == grayscale, single number; RG == RGB, 3 numbers
      if ($colorFields[count($colorFields) - 1] == 'G') // Convert a grayscale string value to the equivalent RGB value
      {
        $drawColor = array((float) $colorFields[0], (float) $colorFields[0], (float) $colorFields[0]);
      } else // Convert RGB string values to the a numeric array
      {
        $drawColor = array((float) $colorFields[0], (float) $colorFields[1], (float) $colorFields[2]);
      }
      return $drawColor;
    }
    /**
     * @param int $r
     * @param int $g
     * @param int $b
     *
     * @return void
     */
    public function SetDrawColor($r, $g, $b) {
      parent::SetDrawColor($r, $g, $b);
    }
    /**
     * @param int $r
     * @param int $g
     * @param int $b
     *
     * @return void
     */
    public function SetTextColor($r, $g, $b) {
      parent::SetTextColor($r, $g, $b);
    }
    /**
     * Set the fill color for table cells.
     * @see reporting/includes/TCPDF#SetFillColor($col1, $col2, $col3, $col4)
     *
     * @param int $r
     * @param int $g
     * @param int $b
     *
     * @return void
     */
    public function SetFillColor($r, $g, $b) {
      parent::SetFillColor($r, $g, $b);
    }
    // Get current cell padding setting from TCPDF object
    /**
     * @return \ADV\Core\Cell|float
     */
    public function GetCellPadding() {
      return $this->cMargin;
    }
    // Set desired cell padding (aka "cell margin")
    // Seems to be just left and right margins...
    /**
     * @param float $pad
     *
     * @return void
     */
    public function SetCellPadding($pad) {
      parent::SetCellPadding($pad);
    }
    /**
     * @param float      $c
     * @param float      $txt
     * @param int|string $n
     * @param int        $corr
     * @param bool|int   $r
     * @param string     $align
     * @param int        $border
     * @param int        $fill
     * @param null       $link
     * @param int        $stretch
     *
     * @return string|void
     */
    public function Text($c, $txt, $n = 0, $corr = 0, $r = 0, $align = 'left', $border = 0, $fill = 0, $link = null, $stretch = 1) {
      if ($n == 0) {
        $n = $this->pageWidth - $this->rightMargin;
      }
      return $this->TextWrap($c, $this->row - $r, $n - $c + $corr, $txt, $align, $border, $fill, $link, $stretch);
    }
    /**
     * @param        $xpos
     * @param        $ypos
     * @param        $len
     * @param        $str
     * @param string $align
     * @param int    $border
     * @param int    $fill
     * @param null   $link
     * @param int    $stretch
     * @param bool   $spacebreak
     *
     * @return string
     */
    public function TextWrap($xpos, $ypos, $len, $str, $align = 'left', $border = 0, $fill = 0, $link = null, $stretch = 1, $spacebreak = false) {
      if ($this->fontSize != $this->oldFontSize) {
        $this->SetFontSize($this->fontSize);
        $this->oldFontSize = $this->fontSize;
      }
      return $this->addTextWrap($xpos, $ypos, $len, $this->fontSize, $str, $align, $border, $fill, $link, $stretch, $spacebreak);
    }
    /**
     * @param      $c
     * @param      $n
     * @param      $txt
     * @param int  $corr
     * @param int  $r
     * @param int  $border
     * @param int  $fill
     * @param null $link
     * @param int  $stretch
     *
     * @return string
     */
    public function TextCol($c, $n, $txt, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = null, $stretch = 1) {
      return $this->TextWrap($this->cols[$c], $this->row - $r, $this->cols[$n] - $this->cols[$c] + $corr, $txt, $this->aligns[$c], $border, $fill, $link, $stretch);
    }
    /**
     * @param      $c
     * @param      $n
     * @param      $txt
     * @param int  $dec
     * @param int  $corr
     * @param int  $r
     * @param int  $border
     * @param int  $fill
     * @param null $link
     * @param int  $stretch
     * @param bool $color_red
     *
     * @return string
     */
    public function AmountCol($c, $n, $txt, $dec = 0, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = null, $stretch = 1, $color_red = false) {
      if ($color_red && $txt < 0) {
        $this->SetTextColor(255, 0, 0);
      }
      $ret = $this->TextCol($c, $n, Num::_format($txt, $dec), $corr, $r, $border, $fill, $link, $stretch);
      if ($color_red && $txt < 0) {
        $this->SetTextColor(0, 0, 0);
      }
      return $ret;
    }
    /**
     * @param        $c
     * @param        $n
     * @param        $txt
     * @param int    $dec
     * @param int    $corr
     * @param int    $r
     * @param int    $border
     * @param int    $fill
     * @param null   $link
     * @param int    $stretch
     * @param bool   $color_red
     * @param string $amount_locale
     * @param string $amount_format
     *
     * @return string
     */
    public function AmountCol2(
      $c,
      $n,
      $txt,
      $dec = 0,
      $corr = 0,
      $r = 0,
      $border = 0,
      $fill = 0,
      $link = null,
      $stretch = 1,
      $color_red = false,
      $amount_locale = 'en_US.UTF-8',
      $amount_format = '%(!.2n'
    ) {
      setlocale(LC_MONETARY, $amount_locale);
      if ($color_red && $txt < 0) {
        $this->SetTextColor(255, 0, 0);
      }
      $ret = $this->TextCol($c, $n, Num::_priceFormat($txt), $corr, $r, $border, $fill, $link, $stretch);
      if ($color_red && $txt < 0) {
        $this->SetTextColor(0, 0, 0);
      }
      return $ret;
    }
    /**
     * @param      $c
     * @param      $n
     * @param      $txt
     * @param bool $conv
     * @param int  $corr
     * @param int  $r
     * @param int  $border
     * @param int  $fill
     * @param null $link
     * @param int  $stretch
     *
     * @return string
     */
    public function DateCol($c, $n, $txt, $conv = false, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = null, $stretch = 1) {
      if ($conv) {
        $txt = Dates::_sqlToDate($txt);
      }
      return $this->TextCol($c, $n, $txt, $corr, $r, $border, $fill, $link, $stretch);
    }
    /**
     * @param      $c
     * @param      $n
     * @param      $txt
     * @param int  $corr
     * @param int  $r
     * @param int  $border
     * @param int  $fill
     * @param null $link
     * @param int  $stretch
     *
     * @return string
     */
    public function TextCol2($c, $n, $txt, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = null, $stretch = 1) {
      return $this->TextWrap($this->cols2[$c], $this->row - $r, $this->cols2[$n] - $this->cols2[$c] + $corr, $txt, $this->aligns2[$c], $border, $fill, $link, $stretch);
    }
    /**
     * @param      $c
     * @param      $n
     * @param      $txt
     * @param int  $corr
     * @param int  $r
     * @param int  $border
     * @param int  $fill
     * @param null $link
     * @param int  $stretch
     *
     * @return void
     */
    public function TextColLines($c, $n, $txt, $corr = 0, $r = 0, $border = 0, $fill = 0, $link = null, $stretch = 0) {
      $this->row -= $r;
      $this->TextWrapLines($this->cols[$c], $this->cols[$n] - $this->cols[$c] + $corr, $txt, $this->aligns[$c], $border, $fill, $link, $stretch, true);
    }
    /**
     * @param        $c
     * @param        $width
     * @param        $txt
     * @param string $align
     * @param int    $border
     * @param int    $fill
     * @param null   $link
     * @param int    $stretch
     * @param bool   $spacebreak
     *
     * @return void
     */
    public function TextWrapLines($c, $width, $txt, $align = 'left', $border = 0, $fill = 0, $link = null, $stretch = 0, $spacebreak = true) {
      $str = Explode("\n", $txt);
      for ($i = 0; $i < count($str); $i++) {
        $l = $str[$i];
        do {
          $l = $this->TextWrap($c, $this->row, $width, $l, $align, $border, $fill, $link, $stretch, $spacebreak);
          $this->row -= $this->lineHeight;
        } while ($l != '');
      }
    }
    /**
     * Expose the underlying calcTextWrap() public function in this API.
     *
     * @param      $txt
     * @param      $width
     * @param bool $spacebreak
     *
     * @return array
     */
    public function TextWrapCalc($txt, $width, $spacebreak = false) {
      return $this->calcTextWrap($txt, $width, $spacebreak);
    }
    /**
     * Sets the line drawing style.
     * Takes an associative array as arg so you don't need to specify all values.
     * Array keys:
     * width (float) - the thickness of the line in user units
     * cap (string) - the type of cap to put on the line, values can be 'butt','round','square'
     *  where the diffference between 'square' and 'butt' is that 'square' projects a flat end past the
     *  end of the line.
     * join (string) - can be 'miter', 'round', 'bevel'
     * dash (mixed) - Dash pattern. Is 0 (without dash) or string with series of length values, which are the
     *    lengths of the on and off dashes. For example: "2" represents 2 on, 2 off, 2 on, 2 off, ...;
     *    "2,1" is 2 on, 1 off, 2 on, 1 off, ...
     * phase (integer) - a modifier on the dash pattern which is used to shift the point at which the pattern starts.
     * color (array) - draw color.  Format: array(GREY), or array(R,G,B) or array(C,M,Y,K).
     *
     * @param array $style
     *
     * @return void
     */
    public function SetLineStyle($style) {
      parent::SetLineStyle($style);
    }
    /**
     * Sets the line drawing width.
     *
     * @param float $width
     *
     * @return void
     */
    public function SetLineWidth($width) {
      parent::SetLineWidth($width);
    }
    /**
     * @param $from
     * @param $row
     * @param $to
     * @param $row2
     *
     * @return void
     */
    public function LineTo($from, $row, $to, $row2) {
      parent::line($from, $row, $to, $row2);
    }
    /**
     * @param float     $row
     * @param float|int $height
     *
     * @return void
     */
    public function Line($row, $height = 0) {
      $oldLineWidth = $this->GetLineWidth();
      $this->SetLineWidth($height + 1);
      parent::line($this->pageWidth - $this->rightMargin, $row, $this->leftMargin, $row);
      $this->SetLineWidth($oldLineWidth);
    }
    /**
     * Underlines the contents of a cell, but not the cell padding area.
     * Primarily useful for the last line before a "totals" line.
     *
     * @param int   $c         Column number to underline.
     * @param int   $r         Print the underline(s) this number of rows below the current position.  Can be negative in order to go up.
     * @param int   $type      Type of underlining to draw.  Possible values are:<ul><li>1: single underline (default)</li><li>2: double underline</li></ul>
     * @param int   $linewidth Thickness of the line to draw.  Default value of zero will use the current line width defined for this document.
     * @param array $style     Line style. Array like for {@link SetLineStyle SetLineStyle}. Default value: default line style (empty array).
     *
     * @return void
     * @access     public
     * @see        SetLineWidth(), SetDrawColor(), SetLineStyle()
     */
    public function UnderlineCell($c, $r = 0, $type = 1, $linewidth = 0, $style = []) {
      // If line width was specified, save current setting so we can reset it
      if ($linewidth != 0) {
        $oldLineWidth = $this->GetLineWidth();
        $this->SetLineWidth($linewidth);
      }
      // Figure out how far down to move the line based on current font size
      // Calculate this because printing underline directly at $this->row goes on top
      // of the parts of characters that "hang down", like the bottom of commas &
      // lowercase letter 'g', etc.
      if ($this->fontSize < 10) {
        $y_adj = 2;
      } else {
        $y_adj = 3;
      }
      parent::line($this->cols[$c] + $this->cMargin, $this->row - $r - $y_adj, $this->cols[$c + 1] - $this->cMargin, $this->row - $r - $y_adj, $style);
      // Double underline, far enough below the first underline so as not to overlap
      // the first underline (depends on current line thickness (aka "line width")
      if ($type == 2) {
        parent::line(
          $this->cols[$c] + $this->cMargin,
          $this->row - $r - $y_adj - ($this->GetLineWidth() + 2),
          $this->cols[$c + 1] - $this->cMargin,
          $this->row - $r - $y_adj - ($this->GetLineWidth() + 2),
          $style
        );
      }
      // If line width was specified, reset it back to the original setting
      if ($linewidth != 0) {
        $this->SetLineWidth($oldLineWidth);
      }
    }
    /**
     * @param int  $l
     * @param int  $np
     * @param null $h
     *
     * @return void
     */
    public function NewLine($l = 1, $np = 0, $h = null) {
      // If the line height wasn't specified, use the current setting
      if ($h == null) {
        $h = $this->lineHeight;
      }
      // Move one line down the page
      $this->row -= ($l * $h);
      // Reset the "current line height" for the new line
      $this->curLineHeight = $this->fontSize;
      // Check to see if we're at the bottom and should insert a page break
      if ($this->row < $this->bottomMargin + ($np * $h)) {
        $this->{$this->headerFunc}();
      } // call header template chosen by current report
    }
    /**
     * @param int  $email
     * @param null $subject
     * @param null $myrow
     * @param int  $doctype
     *
     * @return void
     */
    public function  End($email = 0, $subject = null, $myrow = null, $doctype = 0) {
      if (Config::_get('debug.pdf') == 1) {
        $pdfcode = $this->Output('', 'S');
        $pdfcode = str_replace("\n", "\n<br>", htmlspecialchars($pdfcode));
        ob_clean();
        echo '<html><body>';
        echo trim($pdfcode);
        echo '</body></html>';
        //header("Content-Length: $len");
        //header("Content-Disposition: inline; filename=" . $this->filename);
        //header('Expires: 0');
        //header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        //header('Pragma: public');
        //$this->pdf->stream();
      } else {
        $dir = PATH_COMPANY . 'pdf_files';
        //save the file
        if (!file_exists($dir)) {
          mkdir($dir, 0777);
        }
        // do not use standard filenames or your sensitive company data
        // are world readable
        if ($email == 1) {
          $fname = $dir . '/' . $this->filename;
        } else {
          $fname = $dir . '/' . uniqid('') . '.pdf';
        }
        $this->Output($fname, 'F');
        $doc_Dear_Sirs       = '';
        $doc_AttachedFile    = '';
        $doc_Payment_Link    = '';
        $doc_Kindest_regards = '';
        if ($email == 1) {
          $emailtype = true;
          include(PATH_REPORTS . 'includes' . DS . 'doctext.php');
          $mail = new Email(str_replace(",", "", $this->company['coy_name']), $this->company['email']);
          if (!isset($myrow['email']) || $myrow['email'] == '') {
            $myrow['email'] = isset($myrow['contact_email']) ? $myrow['contact_email'] : '';
          }
          $msg = $doc_Dear_Sirs . " " . $myrow['DebtorName'] . ",\n\n" . $doc_AttachedFile . " " . $subject . "\n\n";
          if (isset($myrow['dimension_id']) && $myrow['dimension_id'] > 0 && $doctype == ST_SALESINVOICE) { // helper for payment links
            if ($myrow['dimension_id'] == 1) {
              $amt = number_format($myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"], User::price_dec());
              $txt = $doc_Payment_Link . " PayPal: ";
              $nn  = urlencode($this->title . " " . $myrow['reference']);
              $url = "https://www.paypal.com/xclick/business=" . $this->company['email'] . "&item_name=" . $nn . "&amount=" . $amt . "&currency_code=" . $myrow['curr_code'];
              $msg .= $txt . $url . "\n\n";
            }
          }
          $msg .= $doc_Kindest_regards . "\n\n";
          $sender = $this->company['postal_address'] . "\n" . $this->company['email'] . "\n" . $this->company['phone'];
          //$mail->to($to);
          if (!empty($myrow['debtor_id'])) {
            $customer     = new Debtor($myrow['debtor_id']);
            $emailAddress = $customer->accounts->email;
          }
          if (empty($emailAddress)) {
            $emailAddress = $myrow['email'];
          }
          if (isset($_GET['Email'])) {
            $emailAddress = $_GET['Email'];
          }
          $mail->to($emailAddress, str_replace(",", "", $myrow['DebtorName']));
          $mail->subject($subject);
          $mail->text($msg . $sender);
          $mail->attachment($fname);
          $ret = $mail->send();
          if (!$ret) {
            Event::error('Error: ' . $emailAddress . ': ' . $mail->toerror);
          } else {
            $myrow['reference'] = (isset($myrow['reference'])) ? $myrow['reference'] : '';
            Event::success(
              $this->title . " " . $myrow['reference'] . " " . _("has been sent by email to: ") . str_replace(",", "", $myrow['DebtorName']) . " &lt;" . $emailAddress . "&gt;"
            );
          }
          unlink($fname);
        } else {
          $printer = Printer::get_report(User::print_profile(), $_POST['REP_ID']);
          if ($printer == false) {
            if (Ajax::_inAjax()) {
              if (User::rep_popup()) {
                Ajax::_popup($fname);
              } // when embeded pdf viewer used
              else {
                Ajax::_redirect($fname);
              } // otherwise use faster method
            } else {
              //echo '<html>
              //		<head>
              //	 	 <SCRIPT LANGUAGE="JavaScript"><!--
              //	 function go_now () { window.location.href = "'.$fname.'"; }
              //	 //--></SCRIPT>
              //	 </head>
              //	 <body onLoad="go_now()"; >
              //	 <a href="'.$fname.'">click here</a> if you are not re-directed.
              //	 </body>
              // </html>';
              ob_clean();
              header('Content-type: application/pdf');
              header("Content-Disposition: inline; filename=$this->filename");
              header('Expires: 0');
              header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
              header('Pragma: public');
              $this->Stream();
            }
          } else { // send report to network printer
            $prn   = new Reports_Printer_Remote($printer['queue'], $printer['host'], $printer['port'], $printer['timeout']);
            $error = $prn->print_file($fname);
            if ($error) {
              Event::error($error);
            } else {
              Event::success(_('Report has been sent to network printer ') . $printer['name']);
            }
          }
        }
        // first have a look through the directory,
        // and remove old temporary pdfs
        if ($d = @opendir($dir)) {
          while (($file = readdir($d)) !== false) {
            if (!is_file($dir . '/' . $file) || $file == 'index.php') {
              continue;
            }
            // then check to see if this one is too old
            $ftime = filemtime($dir . '/' . $file);
            // seems 3 min is enough for any report download, isn't it?
            if (time() - $ftime > 180) {
              unlink($dir . '/' . $file);
            }
          }
          closedir($d);
        }
      }
    }
  }
