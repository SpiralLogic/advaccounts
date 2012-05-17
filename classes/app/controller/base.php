<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 17/05/12
   * Time: 11:37 AM
   * To change this template use File | Settings | File Templates.
   */
  //namespace Controller;
  abstract class Controller_Base {

    protected $title;
    public $help_context;
    abstract function index();
    protected function setTitle($title) {

      $this->title = _($this->help_context = $title);
    }
  }
