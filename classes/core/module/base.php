<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 15/05/12
   * Time: 8:35 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Module;

  abstract class Base implements Loadable {

    protected $_enabled;
    public function __construct($config) {
      $this->_enabled = \Arr::get($config, 'enabled', FALSE);
      if ($this->_enabled) {
        $this->_init();
      }
    }
  }
