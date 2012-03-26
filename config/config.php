<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 19/09/11
   * Time: 2:12 AM
   * To change this template use File | Settings | File Templates.
   */
  return array(
    // writable by www server. When set to empty string logging is switched off.
    // Special value 'syslog' can be used for system logger usage (see php manual).
    'ui_windows_popups' => TRUE, /* use date picker for all date fields */
    'ui_users_showonline' => TRUE, /* default print destination. false = PDF/Printer, true = Excel */
    // If your help wiki use translated page titles uncomment next line
    'help_baseurl' => NULL, /* per user data/cache directory */
    'use_fiscalyear' => FALSE,
    'print_default_excel' => FALSE, // Wiki context help configuration
    'print_paper_sizes' => array("Letter", "A4"),
    'print_useinvoicenumber' => 1, //
    'auth_salt' => '342342134sdfgsgdf', //
    'demo_mode' => FALSE, //
    'item_images_height' => 50,
    'item_images_max_size' => 500, //
    'company_subdirs' => array('images', 'pdf_files', 'backup', 'js_cache', 'reporting', 'attachments'), //
    'graphs_skin' => 1, //
    'separators_thousands' => array(",", ".", " "), //
    'separators_decimal' => array(".", ","),
    'session_lifetime' => 3600,
    'max_login_attempts' => 10,
  );
