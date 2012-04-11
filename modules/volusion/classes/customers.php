<?php
  namespace Modules\Volusion;
  use \ADV\Core\DB\DBDuplicateException, \ADV\Core\DB\DB, \Event, \ADV\Core\XMLParser;

  /**
   * Class for getting Customers from Volusion and putting them in to the intermediate database.
   */
  class Customers {
    /**
     * @var \ADV\Core\Status
     */
    public $status;
    /**
     * @var array
     */
    public $customers = array();
    /**

     */
    public function __construct() {
      echo __NAMESPACE__;
      $this->status = new \ADV\Core\Status();
    }
    /**
     * Gets XML from website containing customer information and stores in in $this->customers
     * @return bool returns false if nothing was retrieved or true otherwise.
     */
    function get() {
      $customersXML = $this->getXML();
      if (!$customersXML) {
        return $this->status->set(FALSE, 'getXML', "Nothing retrieved from website");
      }
      $this->customers = XMLParser::XMLtoArray($customersXML);
      if (!$this->customers) {
        return $this->status->set(FALSE, 'XMLtoArray', "No new custoemrs!");
      }
      return $this->status->set(TRUE, 'get', "Customers retrieved");
    }
    /**
     * @param $customers
     * @return array
     */
    function insertCustomersToDB($customers) {
      if (!$customers) {
        return $this->status->set(FALSE, 'insertToDB', 'No Customers to add.');
      }
      foreach ($customers as $customer) {
        $this->insertCustomerToDB($customer);
      }
      return $this->status->set(TRUE, 'addedToDB', "Finished adding Customers to DB!");
    }
    /**
     * @param $customer
     */
    function insertCustomerToDB($customer) {
      if (!empty($customer['CompanyName'])) {
        $name = $customer['CompanyName'];
      }
      elseif (!empty($customer['FirstName']) || !empty($customer['LastName'])) {
        $name = ucwords($customer['FirstName'] . ' ' . $customer['LastName']);
      }
      else {
        $name = $customer['EmailAddress'];
      }
      try {
        DB::insert('WebCustomers')->values($customer)->exec();
        $this->status->set(TRUE, 'insert', "Added Customer $name to website customer database! {$customer['CustomerID']} ");
      }
      catch (DBDuplicateException $e) {
        DB::update('WebCustomers')->values($customer)->where('CustomerID=', $customer['CustomerID'])->exec();
        $this->status->set(FALSE, 'insert', "Updated Customer $name ! {$customer['CustomerID']}");
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
      return $result;
    }

    /**
     * @return array
     */
    function createSales() {
      $result = DB::select()->from('WebCustomers')->where('extid=', 0)->fetch()->assoc()->all();
      if (!$result) {
        return $this->status->set(FALSE, 'insert', "No new customers in database");
      }
      $added = $updated = 0;
      foreach ($result as $row) {
        if (!empty($row['CompanyName'])) {
          $name = $row['CompanyName'];
        }
        elseif (!empty($row['FirstName']) || !empty($row['LastName'])) {
          $name = $row['CompanyName'] = ucwords($row['FirstName'] . ' ' . $row['LastName']);
        }
        else {
          $name = $row['CompanyName'] = $row['EmailAddress'];
        }
        $debtor_no = \DB::select('debtor_no')->from('debtors')->where('webid =', $row["CustomerID"])->fetch()->assoc()->one();
        if ($debtor_no > 0) {
          $c = new \Debtor($debtor_no);
        }
        else {
          $c = new \Debtor();
        }
        $c->name = $c->debtor_ref = $name;
        $c->branches[$c->defaultBranch]->post_address = $row["BillingAddress2"];
        $c->branches[$c->defaultBranch]->br_address = $row["BillingAddress1"];
        $c->branches[$c->defaultBranch]->city = $row["City"];
        $c->branches[$c->defaultBranch]->state = $row["State"];
        $c->branches[$c->defaultBranch]->postcode = $row["PostalCode"];
        $c->branches[$c->defaultBranch]->contact_name = $row["FirstName"];
        $c->branches[$c->defaultBranch]->phone = $row["PhoneNumber"];
        $c->branches[$c->defaultBranch]->fax = $row["FaxNumber"];
        $c->branches[$c->defaultBranch]->website = $row["WebsiteAddress"];
        $c->branches[$c->defaultBranch]->email = $row["EmailAddress"];
        $c->address = $row["BillingAddress1"];
        $c->post_address = $row ["BillingAddress2"];
        $c->tax_id = $row["TaxID"];
        $c->webid = $row["CustomerID"];
        $c->contact_name = $row["FirstName"];
        $c->save();
        if (\Arr::get($c->getStatus(FALSE), 'status') == E_USER_ERROR) {
          $id = \DB::select('debtor_no')->from('debtors')->where('name=', $c->name)->fetch()->assoc()->one();
          $this->status->set(TRUE, 'update', "Customer {$c->name} could not be added or updated. {$c->webid}.<br>" . $id . ":" . $row["EmailAddress"]);
        }
        elseif ($c->debtor_no > 0) {
          $this->status->set(TRUE, 'update', "Customer {$c->name} has been updated. {$c->id} ");
          $updated++;
        }
        else {

          $added++;
          $this->status->set(TRUE, 'add', "Customer  {$c->name} has been added.  {$c->id} ");
        }
        \DB::update('WebCustomers')->value('extid', $c->id)->where('CustomerID=', $row['CustomerID'])->exec();
      }
      Event::notice("Added $added Customers. Updated $updated Customers.");
      return $this->status->set(TRUE, 'adding', "Added $added Customers. Updated $updated Customers.");
    }
  }
