<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  abstract class Contact_Company extends DB_abstract
  {
    /**
     * @var string
     */
    public $discount = '0';
    /**
     * @var string
     */
    public $name = '';
    /**
     * @var
     */
    public $address;
    /**
     * @var
     */
    public $city;
    /**
     * @var
     */
    public $state;
    /**
     * @var
     */
    public $postcode;
    /**
     * @var string
     */
    public $post_address = '';
    /**
     * @var
     */
    public $tax_id;
    /**
     * @var
     */
    public $contact_name;
    /**
     * @var int
     */
    public $credit_limit = 0;
    /**
     * @var int
     */
    public $dimension_id = 0;
    /**
     * @var int
     */
    public $dimension2_id = 0;
    /**
     * @var int
     */
    public $payment_terms = 1;
    /**
     * @var string
     */
    public $curr_code = '';
    /**
     * @var array
     */
    public $emailAddresses = array();
    /**
     * @abstract
     * @return mixed
     */
    abstract protected function _countTransactions();
    /**
     * @param $name
     * @param $emails
     * @param $trans
     * @param $type
     *
     * @return void
     */
    protected function addEmailGroup($name, $emails, $trans, $type)
    {
    }
    /**
     * @static
     *
     * @param      $selector
     * @param bool $id
     *
     * @return void
     */
    public static function addInfoDialog($selector, $id = false)
    {
      if ($id) {
        $company = new static($id);
      }
      $content = '<div><span class="bold">Shipping Address:</span><br>${address}</br></br>
                         <span class="bold">Mailing Address:</span><br>${post_address}</br></br>
                         <span class="bold">Phone: </span>${phone}</br></br>
                         <span class="bold">Phone2: </span>${phone2}</br></br>
                         <span class="bold">Fax: </span>${fax}</br></br>
                         <span class="bold">Contact: </span>${contact}</br></br>
                         <span class="bold">Email: </span><a href="mailto:${email}">${email}</a></br></br>
                         <span class="bold">Website: </span><a target="_new" href="http://${website}">${website}</a></br></br>
                                            </div>';
      $type    = explode('_', get_called_class());
      $type    = array_pop($type);
      $type    = ($type == 'c') ? 'customer' : 'supplier';
      $details = new Dialog($type . ' Details:', 'company_details', $content, array('minHeight' => 400));
      $type    = strtolower($type);
      $details->setTemplateData(($id) ? $company : '');
      if ($id) {
        $details->addOpenEvent($selector, 'click');
      } else {
        $action = <<<JS

                 $.post('/contacts/{$type}s.php',{id:$(this).data('id')},function(data) {Adv.o.company_details.render(data.$type); \$company_details.dialog('open');},'json');
JS;
        JS::addLiveEvent($selector, 'click', $action, 'wrapper', true);
      }
      $details->addButton('Close', '$(this).dialog("close")');
      $details->show();
    }
    /**
     * @static
     *
     * @param $emailid
     *
     * @return bool|string
     */
    public static function getEmailDialogue($emailid)
    {
      list($id, $type, $trans) = explode('-', $emailid);
      $company = get_called_class();
      $company = new $company($id);
      $emails  = $company->getEmailAddresses();
      if (count($emails) > 0) {
        $types   = $GLOBALS['systypes_array'];
        $text    = $types[$type];
        $content = Reporting::email_link($trans, _("Email This $text"), true, $type, 'EmailLink', null, $emails, 0, true);
        if ($type == ST_SALESQUOTE || $type == ST_SALESORDER) {
          $type = ($type == ST_SALESORDER) ? ST_PROFORMA : ST_PROFORMAQ;
          $text = $types[$type];
          $content .= Reporting::email_link($trans, _("Email This ") . $text, true, $type, 'EmailLink', null, $emails, 0, true);
        }

        return $content;
      }

      return false;
    }
  }
