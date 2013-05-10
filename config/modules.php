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
        'apikey'        => 'FE2446D419E44896ED983B445C8E81486B2652718A8D121E748BFC5E28442582',
        'apiurl'        => 'http://www.advancedroadsigns.com.au/net/WebService.aspx?',
        'product_url'   => 'http://www.advancedroadsigns.com.au/-p/',
        'url_extension' => '.htm',
      )
    ],
  );
