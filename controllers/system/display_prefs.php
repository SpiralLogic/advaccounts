<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  Page::start(_($help_context = "Display Setup"), SA_SETUPDISPLAY);
  if (isset($_POST['setprefs'])) {
    if (!is_numeric($_POST['query_size']) || ($_POST['query_size'] < 1)) {
      Event::error($_POST['query_size']);
      Event::error(_("Query size must be integer and greater than zero."));
      JS::set_focus('query_size');
    }
    else {
      $chg_theme = User::theme() != $_POST['theme'];
      $chg_lang = $_SESSION['Language']->code != $_POST['language'];
      User::i()
        ->update_prefs($_POST['prices'], $_POST['Quantities'], $_POST['Rates'], $_POST['Percent'], Form::hasPost('show_gl'), Form::hasPost('show_codes'), $_POST['date_format'], $_POST['date_sep'], $_POST['tho_sep'], $_POST['dec_sep'], $_POST['theme'], $_POST['page_size'], Form::hasPost('show_hints'),
        $_POST['profile'], Form::hasPost('rep_popup'), (int) ($_POST['query_size']), Form::hasPost('graphic_links'), $_POST['language'], Form::hasPost('sticky_doc_date'), $_POST['startup_tab']);
      if ($chg_lang) {
        $_SESSION['Language']->set_language($_POST['language']);
      }
      // refresh main menu
      Files::flush_dir(COMPANY_PATH . 'js_cache');
      if ($chg_theme && Config::get('demo_mode')) {
        User::prefs()->theme = $_POST['theme'];
      }
      if ($chg_theme || $chg_lang) {
        Display::meta_forward($_SERVER['DOCUMENT_URI']);
      }
      if (Config::get('demo_mode')) {
        Event::warning(_("Display settings have been updated. Keep in mind that changed settings are restored on every login in demo mode."));
      }
      else {
        Event::success(_("Display settings have been updated."));
      }
    }
  }
  Form::start();
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Decimal Places"));
   Form::textRowEx(_("Prices/Amounts:"), 'prices', 5, 5, '', User::price_dec());
   Form::textRowEx(_("Quantities:"), 'Quantities', 5, 5, '', User::qty_dec());
   Form::textRowEx(_("Exchange Rates:"), 'Rates', 5, 5, '', User::exrate_dec());
   Form::textRowEx(_("Percentages:"), 'Percent', 5, 5, '', User::percent_dec());
  Table::sectionTitle(_("Dateformat and Separators"));
   Form::dateFormatsListRow(_("Dateformat:"), "date_format", User::date_format());
   Form::dateSepsListRow(_("Date Separator:"), "date_sep", User::date_sep());
  /* The array $dateseps is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
   Form::thoSepsListRow(_("Thousand Separator:"), "tho_sep", User::prefs()->tho_sep);
  /* The array Config::get('separators_thousands') is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
   Form::decSepsListRow(_("Decimal Separator:"), "dec_sep", User::prefs()->dec_sep);
  if (!isset($_POST['language'])) {
    $_POST['language'] = $_SESSION['Language']->code;
  }
  Table::sectionTitle(_("Language"));
  Languages::row(_("Language:"), 'language', $_POST['language']);
  Table::section(2);
  Table::sectionTitle(_("Miscellaneous"));
   Form::checkRow(_("Show hints for new users:"), 'show_hints', User::hints());
   Form::checkRow(_("Show GL Information:"), 'show_gl', User::show_gl());
   Form::checkRow(_("Show Item Codes:"), 'show_codes', User::show_codes());
  Users::themes_row(_("Theme:"), "theme", User::theme());
  /* The array $themes is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  Reports_UI::pagesizes_row(_("Page Size:"), "page_size", User::page_size());
  Users::tabs_row(_("Start-up Tab"), 'startup_tab', User::startup_tab());
  /* The array Config::get('print_paper_sizes') is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  if (!isset($_POST['profile'])) {
    $_POST['profile'] = User::print_profile();
  }
  Reports_UI::print_profiles_row(_("Printing profile") . ':', 'profile', NULL, _('Browser printing support'));
   Form::checkRow(_("Use popup window to display reports:"), 'rep_popup', User::rep_popup(), FALSE, _('Set this option to on if your browser directly supports pdf files'));
   Form::checkRow(_("Use icons instead of text links:"), 'graphic_links', User::graphic_links(), FALSE, _('Set this option to on for using icons instead of text links'));
   Form::textRowEx(_("Query page size:"), 'query_size', 5, 5, '', User::query_size());
   Form::checkRow(_("Remember last document date:"), 'sticky_doc_date', User::sticky_doc_date(), FALSE, _('If set document date is remembered on subsequent documents, otherwise default is current date'));
  Table::endOuter(1);
  Form::submitCenter('setprefs', _("Update"), TRUE, '', 'default');
  Form::end(2);
  Page::end();


