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
    'login'   => [
      'Jobsboard' => array('enabled' => false, 'init' => true),
      'Youtrack'  => array('enabled' => true, 'init' => true),
    ],
    'default' => [
      'Volusion' => array(
        'enabled'       => true,
        'init'          => true,
        'apiuser'       => 'admin@advancedroadsigns.com.au',
        'apikey'        => 'B5E372092955E9A46CCABF6AADA001B819669EE27BF3414B3E1E93F008AF80CE',
        'apiurl'        => 'http://www.advancedroadsigns.com.au/net/WebService.aspx?',
        'product_url'   => 'http://www.advancedroadsigns.com.au/-p/',
        'url_extension' => '.htm',
      )
    ],
  );
