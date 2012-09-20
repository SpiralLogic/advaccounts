<?php
  use Modules\Volusion\Volusion;

  $config = Config::_get('modules.login');
  $test   = new Volusion($config['Volusion']);
  $test->doWebsales();
