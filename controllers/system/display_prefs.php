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
      JS::setFocus('query_size');
    } else {
      $chg_theme = User::theme() != $_POST['theme'];
      $chg_lang  = $_SESSION['Language']->code != $_POST['language'];
      User::i()
        ->update_prefs($_POST['prices'], $_POST['Quantities'], $_POST['Rates'], $_POST['Percent'], Forms::hasPost('show_gl'), Forms::hasPost('show_codes'), $_POST['date_format'], $_POST['date_sep'], $_POST['tho_sep'], $_POST['dec_sep'], $_POST['theme'], $_POST['page_size'], Forms::hasPost('show_hints'), $_POST['profile'], Forms::hasPost('rep_popup'), (int) ($_POST['query_size']), Forms::hasPost('graphic_links'), $_POST['language'], Forms::hasPost('sticky_doc_date'), $_POST['startup_tab']);
      if ($chg_lang) {
        $_SESSION['Language']->setLanguage($_POST['language']);
      }
      // refresh main menu
      Files::flushDir(COMPANY_PATH . 'js_cache');
      if ($chg_theme && Config::get('demo_mode')) {
        User::prefs()->theme = $_POST['theme'];
      }
      if ($chg_theme || $chg_lang) {
        Display::meta_forward($_SERVER['DOCUMENT_URI']);
      }
      if (Config::get('demo_mode')) {
        Event::warning(_("Display settings have been updated. Keep in mind that changed settings are restored on every login in demo mode."));
      } else {
        Event::success(_("Display settings have been updated."));
      }
    }
  }
  Forms::start();
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Decimal Places"));
  Forms::textRowEx(_("Prices/Amounts:"), 'prices', 5, 5, '', User::price_dec());
  Forms::textRowEx(_("Quantities:"), 'Quantities', 5, 5, '', User::qty_dec());
  Forms::textRowEx(_("Exchange Rates:"), 'Rates', 5, 5, '', User::exrate_dec());
  Forms::textRowEx(_("Percentages:"), 'Percent', 5, 5, '', User::percent_dec());
  Table::sectionTitle(_("Dateformat and Separators"));
  Forms::dateFormatsListRow(_("Dateformat:"), "date_format", User::date_format());
  Forms::dateSepsListRow(_("Date Separator:"), "date_sep", User::date_sep());
  /* The array $dateseps is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  Forms::thoSepsListRow(_("Thousand Separator:"), "tho_sep", User::prefs()->tho_sep);
  /* The array Config::get('separators_thousands') is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  Forms::decSepsListRow(_("Decimal Separator:"), "dec_sep", User::prefs()->dec_sep);
  if (!isset($_POST['language'])) {
    $_POST['language'] = $_SESSION['Language']->code;
  }
  Table::sectionTitle(_("Language"));
  Languages::row(_("Language:"), 'language', $_POST['language']);
  Table::section(2);
  Table::sectionTitle(_("Miscellaneous"));
  Forms::checkRow(_("Show hints for new users:"), 'show_hints', User::hints());
  Forms::checkRow(_("Show GL Information:"), 'show_gl', User::show_gl());
  Forms::checkRow(_("Show Item Codes:"), 'show_codes', User::show_codes());
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
  Reports_UI::print_profiles_row(_("Printing profile") . ':', 'profile', null, _('Browser printing support'));
  Forms::checkRow(_("Use popup window to display reports:"), 'rep_popup', User::rep_popup(), false, _('Set this option to on if your browser directly supports pdf files'));
  Forms::checkRow(_("Use icons instead of text links:"), 'graphic_links', User::graphic_links(), false, _('Set this option to on for using icons instead of text links'));
  Forms::textRowEx(_("Query page size:"), 'query_size', 5, 5, '', User::query_size());
  Forms::checkRow(_("Remember last document date:"), 'sticky_doc_date', User::sticky_doc_date(), false, _('If set document date is remembered on subsequent documents, otherwise default is current date'));
  Table::endOuter(1);
  Forms::submitCenter('setprefs', _("Update"), true, '', 'default');
  Forms::end(2);
  Page::end();


