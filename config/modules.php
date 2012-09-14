<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  return array(
    'login'  => [
      'Jobsboard'=> array('enabled'=> false, 'init'=> true),
      'Youtrack' => array('enabled'=> true, 'init'=> true),
      'Volusion' => array(
        'enabled'       => true,
        'init'          => true,
        'apiuser'       => 'admin@advancedroadsigns.com.au',
        'apikey'        => '9BB57370C3B86C90D5AAC36AE1319FF4AC477A25258FD1F1A6938F84E763FF7B',
        'apiurl'        => 'http://www.advancedroadsigns.com.au/net/WebService.aspx?',
        'product_url'   => 'http://www.advancedroadsigns.com.au/-p/',
        'url_extension' => '.htm',
      )
    ],
    'default'=> [],
  );
