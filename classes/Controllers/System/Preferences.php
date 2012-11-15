<?php

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\System;

  use ADV\Core\View;
  use ADV\Core\Event;
  use ADV\App\ADVAccounting;
  use ADV\Core\Config;
  use ADV\Core\DIC;
  use ADV\App\Form\Form;

  /**
   *
   */
  class Preferences extends \ADV\App\Controller\Action
  {
    /** @var \ADV\App\Dates */
    protected $Dates;
    protected function before() {
      $this->Dates = DIC::get('Dates');
      if (REQUEST_POST) {
        $this->User->update_prefs($_POST);
      }
    }
    protected function index() {
      $this->Page->start('Preferences', SA_SETUPDISPLAY);
      $view = new View('preferences');
      $form = new Form();
      $view->set('form', $form);
      $form->group('decimals');
      $form->number('prices', 0)->label('Prices/Amounts:');
      $form->number('qty_dec', 0)->label('Quantities:');
      $form->number('exrate_dec', 0)->label('Exchange Rates:');
      $form->number('percent_dec', 0)->label('Percentages:');
      $form->group('dates');
      $form->arraySelect('date_format', $this->Dates->formats)->label('Dateformat');
      $form->arraySelect('date_sep', $this->Dates->separators)->label('Date Separator:');
      $form->arraySelect('tho_sep', Config::_get('separators_thousands'))->label('Thousand Separator:');
      $form->arraySelect('dec_sep', Config::_get('separators_decimal'))->label('Decimal Separator:');
      $form->group('other');
      $form->checkbox('show_hints')->label('Show Hints');
      $form->checkbox('show_gl')->label('Show GL Information:');
      $form->checkbox('show_codes')->label('Show Item Codes:');
      $form->arraySelect('theme', $this->getThemes())->label('Theme:');
      $form->arraySelect('page_size', Config::_get('print_paper_sizes'))->label('Page Size:');
      $form->arraySelect('startup_tab', $this->getApplications())->label('Start-up Tab:');
      $form->checkbox('rep_popup')->label('Use popup window to display reports:');
      $form->checkbox('graphic_links')->label('Use icons instead of text links:');
      $form->checkbox('query_size')->label('Query page size:');
      $form->checkbox('sticky_doc_date')->label('Remember last document date:');
      $form->setValues($this->User->prefs);
      $view->render();
      $this->Page->end();
    }
    /**
     * @return array
     */
    protected function getApplications() {
      $apps = ADVAccounting::i()->applications;
      $tabs = [];
      foreach ($apps as $app => $config) {
        if ($config['enabled']) {
          $tabs[$app] = $app;
        }
      }
      return $tabs;
    }
    /**
     * @return array
     */
    protected function getThemes() {
      $themes = [];
      try {
        $themedir = new \DirectoryIterator(ROOT_WEB . PATH_THEME);
      } catch (\UnexpectedValueException $e) {
        Event::error($e->getMessage());
      }
      foreach ($themedir as $theme) {
        if (!$theme->isDot() && $theme->isDir()) {
          $themes[$theme->getFilename()] = $theme->getFilename();
        }
      }
      ksort($themes);
      return $themes;
    }
  }


