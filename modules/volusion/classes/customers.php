<?php
  namespace Modules\Volusion;
  /**

   */
  use \ADV\Core\DB\DBDuplicateException;
  use \ADV\Core\DB\DB;
  use \Event;
  use \ADV\Core\XMLParser;

  class Customers {

    protected $status;
    public $customers;

    /**

     */
    public function __construct() {
      echo __NAMESPACE__;
      $this->status = new \ADV\Core\Status();
    }

    /**
     * @return bool
     */
    function get() {
      $customersXML = $this->getXML();
      if (!$customersXML) {
        return $this->status->set(false, 'getxml', "Nothing retrieved from website");
      }
      $customers = XMLParser::XMLtoArray($customersXML);
      $this->customers = $customers;
      if (!$customers) {
        return $this->status->set(FALSE, 'paresexml', 'Nothing found from website.');
      }
      foreach ($customers as $customer) {
        if (!empty($customer['CompanyName'])) {
          $name = $customer['CompanyName'];
        }
        elseif (!empty($customer['FirstName']) || !empty($customer['LastnName'])) {
          $name = $customer['CompanyName'] = ucwords($customer['FirstName'] . ' ' . $customer['LastName']);
        }
        else {
          $name = $customer['CompanyName'] = $customer['EmailAddress'];
        }
        try {
          DB::insert('WebCustomers')->values($customer)->exec();
          $this->status->set(TRUE, 'insert', "Added Customer $name to website customer database!");
        }
        catch (DBDuplicateException $e) {
          return $this->status->set(FALSE, 'insert', "Customer $name already exists!");
        }
      }
    }

    /**
     * @return string
     */
    function getXML() {
      $apiuser = \Config::get('modules.webstore')['apiuser'];
      $apikey = \Config::get('modules.webstore')['apikey'];
      $url = \Config::get('modules.webstore')['apiurl'];
      $url .= "Login=" . $apiuser;
      $url .= '&EncryptedPassword=' . $apikey;
      $url .= '&EDI_Name=Generic\Customers';
      $url .= '&SELECT_Columns=*';
      if (!$result = file_get_contents($url)) {
        Event::warning('Could not retrieve web customers');
      }
      ;
      return $result;
    }

    function insert() {
      $result = DB::select()->from('WebCustomers')->where('extid=', 0)->fetch()->assoc()->all();
      if (!$result) {
        return $this->status->set(false, 'insert', "No new customers in database");
      }
      foreach ($result as $row) {
        $c = new \Debtor();
        $c->name = $c->debtor_ref = $row["CompanyName"];
        $c->branches[0]->post_address = $row["BillingAddress2"];
        $c->branches[0]->br_address = $row["BillingAddress1"];
        $c->branches[0]->city = $row["City"];
        $c->branches[0]->state = $row["State"];
        $c->branches[0]->postcode = $row["PostalCode"];
        $c->branches[0]->contact_name = $row["FirstName"];
        $c->branches[0]->phone = $row["PhoneNumber"];
        $c->branches[0]->fax = $row["FaxNumber"];
        $c->branches[0]->website = $row["WebsiteAddress"];
        $c->branches[0]->email = $row["EmailAddress"];
        $c->address = $row["BillingAddress1"];
        $c->post_address = $row ["BillingAddress2"];
        $c->tax_id = $row["TaxID"];
        $c->webid = $row["CustomerID"];
        $c->contact_name = $row["FirstName"];
        $c->save();
        $status = $c->getStatus();
        $dup = ((substr($status['message'], 0, 9) == "Duplicate"));
        if ($dup) {
          $result2 = \DB::select('debtor_no')->from('debtors')->where('name LIKE', $c->name)->fetch()->assoc()->one();
          if (!$result2) {
            continue;
          }
          $c->id = $result2['debtor_no'];
          $d = new \Debtor((array) $c);
          $d->save();
        }
0        else {
          if ($c->id > 0) {
            \DB::update('WebCustomers')->value('extid', $c->id)->where('CustomerID=', $row['CustomerID'])->exec();
          }
        }
      }
    }
  }
