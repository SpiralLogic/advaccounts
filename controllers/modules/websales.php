<?php
  use ADV\Core\Config;

  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 27/09/12
   * Time: 2:38 AM
   * To change this template use File | Settings | File Templates.
   */
  $configs = Config::_get('modules.default');
  $test    = new \Modules\Volusion\Volusion($configs['Volusion']);
  $test->doWebsales();
