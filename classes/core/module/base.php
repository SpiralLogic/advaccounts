<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 15/05/12
   * Time: 8:35 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Module;
  /**

   */
  abstract class Base implements Loadable
  {

    /**
     * @var array
     */
    protected $_enabled;
    /**
     * @param array $config
     */
    public function __construct($config = []) {
      $this->_enabled = $config and \Arr::get($config, 'enabled', false);
      if ($this->_enabled) {
        $this->_init();
      }
    }
  }
