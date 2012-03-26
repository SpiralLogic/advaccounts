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
        ->update_prefs($_POST['prices'], $_POST['Quantities'], $_POST['Rates'], $_POST['Percent'], check_value('show_gl'), check_value('show_codes'), $_POST['date_format'], $_POST['date_sep'], $_POST['tho_sep'], $_POST['dec_sep'], $_POST['theme'], $_POST['page_size'], check_value('show_hints'),
        $_POST['profile'], check_value('rep_popup'), (int) ($_POST['query_size']), check_value('graphic_links'), $_POST['language'], check_value('sticky_doc_date'), $_POST['startup_tab']);
      if ($chg_lang) {
        $_SESSION['Language']->set_language($_POST['language']);
      }
      // refresh main menu
      Files::flush_dir(COMPANY_PATH . 'js_cache');
      if ($chg_theme && Config::get('demo_mode')) {
        User::prefs()->theme = $_POST['theme'];
      }
      if ($chg_theme || $chg_lang) {
        Display::meta_forward($_SERVER['PHP_SELF']);
      }
      if (Config::get('demo_mode')) {
        Event::warning(_("Display settings have been updated. Keep in mind that changed settings are restored on every login in demo mode."));
      }
      else {
        Event::success(_("Display settings have been updated."));
      }
    }
  }
  start_form();
  start_outer_table('tablestyle2');
  table_section(1);
  table_section_title(_("Decimal Places"));
  text_row_ex(_("Prices/Amounts:"), 'prices', 5, 5, '', User::price_dec());
  text_row_ex(_("Quantities:"), 'Quantities', 5, 5, '', User::qty_dec());
  text_row_ex(_("Exchange Rates:"), 'Rates', 5, 5, '', User::exrate_dec());
  text_row_ex(_("Percentages:"), 'Percent', 5, 5, '', User::percent_dec());
  table_section_title(_("Dateformat and Separators"));
  dateformats_list_row(_("Dateformat:"), "date_format", User::date_format());
  dateseps_list_row(_("Date Separator:"), "date_sep", User::date_sep());
  /* The array $dateseps is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  thoseps_list_row(_("Thousand Separator:"), "tho_sep", User::prefs()->tho_sep);
  /* The array Config::get('separators_thousands') is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  decseps_list_row(_("Decimal Separator:"), "dec_sep", User::prefs()->dec_sep);
  if (!isset($_POST['language'])) {
    $_POST['language'] = $_SESSION['Language']->code;
  }
  table_section_title(_("Language"));
  Languages::row(_("Language:"), 'language', $_POST['language']);
  table_section(2);
  table_section_title(_("Miscellaneous"));
  check_row(_("Show hints for new users:"), 'show_hints', User::hints());
  check_row(_("Show GL Information:"), 'show_gl', User::show_gl_info());
  check_row(_("Show Item Codes:"), 'show_codes', User::show_codes());
  Users::themes_row(_("Theme:"), "theme", User::theme());
  /* The array $themes is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  Reports_UI::pagesizes_row(_("Page Size:"), "page_size", User::pagesize());
  Users::tabs_row(_("Start-up Tab"), 'startup_tab', User::startup_tab());
  /* The array Config::get('print_paper_sizes') is set up in config.php for modifications
      possible separators can be added by modifying the array definition by editing that file */
  if (!isset($_POST['profile'])) {
    $_POST['profile'] = User::print_profile();
  }
  Reports_UI::print_profiles_row(_("Printing profile") . ':', 'profile', NULL, _('Browser printing support'));
  check_row(_("Use popup window to display reports:"), 'rep_popup', User::rep_popup(), FALSE, _('Set this option to on if your browser directly supports pdf files'));
  check_row(_("Use icons instead of text links:"), 'graphic_links', User::graphic_links(), FALSE, _('Set this option to on for using icons instead of text links'));
  text_row_ex(_("Query page size:"), 'query_size', 5, 5, '', User::query_size());
  check_row(_("Remember last document date:"), 'sticky_doc_date', User::sticky_date(), FALSE, _('If set document date is remembered on subsequent documents, otherwise default is current date'));
  end_outer_table(1);
  submit_center('setprefs', _("Update"), TRUE, '', 'default');
  end_form(2);
  Page::end();

?>
