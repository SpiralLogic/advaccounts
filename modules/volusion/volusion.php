<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 11/01/12
	 * Time: 8:54 PM
	 * To change this template use File | Settings | File Templates.
	 */
	namespace Modules;
	use \Modules\Volusion\Orders as Orders;

	class Volusion
	{
		function __construct() {
			\DB::connect(\Config::get('db_jobsboard'));
		}

		function doWebsales() {
			echo "<br><br>Retrieving new webWales from Volusion<br>";

			$result = $this->getNewWebsales();

			if ($result) {
				echo "<br><br>Jamming all new orders in local database to Jobs Board<br>";
			}
			else {
				echo "<br><br>No new websales, checking for local strays<br>";
			}
			$result = $this->notOnJobsboard();
			if ($result) {
				echo "<br><br>New orders successfully added!<br>";
			}
			\DB::change_connection();
		}
		protected function getNewWebsales() {
			$orders = new Orders();
			if (!$orders) {
				echo "<br>No Orders Retrieved";
				return false;
			}
			$results = array();
			foreach ($orders as $order) {
				$orders->process();
				$results[] = "Status of adding order: " . $order['OrderID'] . ": <strong>" . $orders->status . "</strong><br>";
			}
			return $results;
		}
		function getNotOnJobsboard() {
			\DB::change_connection('jobsboard');

			$results = \DB::select('OrderID,ison_jobsboard')->from('WebOrders')->where('ison_jobsboard IS NULL')->fetch()->all();
			if (count($results) > 0) {
				return $results;
			}
			return false;
		}

		protected function notOnJobsboard() {
			$neworders = $this->getNotOnJobsboard();
			if (!$neworders) {
				echo "No new jobs in database!";
				return false;
			}
			foreach ($neworders as $neworder) {
				$job = $this->insertJob($neworder['OrderID']);
				if (!$job) {
					$result = "Websale {$neworder['OrderID']} couldn't be added to Jobs Board (it's probably aready there)!";
				}
				else {
					\DB::change_connection();
					//\DB::update('WebOrders')->value('ison_jobsboard', $job)->where('OrderID=', $neworder['OrderID']);
					var_dump($job);
					echo "Websale {$neworder['OrderID']} successfully added to Jobs Board with Job Number $job!";
				}
			}
			return true;
		}
		protected function insertJob($id) {
			\DB::change_connection();
			$order = \DB::select()->from('WebOrders')->where('OrderID=', $id)->fetch()->all();
			$orderdetails = \DB::select()->from('WebOrderDetails')->where('OrderID=', $id)->fetch()->all();
			if (!$order) {
				return false;
			}
			\DB::change_connection('josboard');
			$jobsboard_no = \DB::select('Advanced_Job_No')->from('Job_List')->where('websaleid=', $id)->fetch()->all();
			$lineitems = $lines = array();
			foreach ($orderdetails as $detail) {
				$lines[] = array(
					'item_code' => '[' . $detail['ProductCode'] . ']', 'ProductName' => $detail['ProductName'],
					'quantity' => 'x' . $detail['Quantity'],
					'options' => '</div><div>' . $detail['Options'],
				);
				$lineitems[] = array(
					'stock_code' => $detail['ProductCode'],
					'quantity' => $detail['Quantity'],
					'description' => $detail['ProductName'] . $detail['Options'],
					'line_id' => $detail['OrderDetailID'],
					'job_id' => $jobsboard_no
				);
			}
			if ($jobsboard_no > 0) {
				$freight_method = Orders::$shipping_types[$neworder['ShippingMethodID']];
				$payment_method = Orders::$payment_types[$neworder['PaymentMethodID']];
				$comments = (strlen($neworder['Order_Comments']) > 0) ? $neworder['Order_Comments'] . "\r\n" : '';
				$detail = $comments . "Payment Method: " . $payment_method . "\r\nShipping Method: " . $freight_method . "\r\nFreight Paid: " . $order['TotalShippingCost'];
				$newJob = array(
					'Advanced_Job_No' => $jobsboard_no,
					'websaleid' => $id,
					'Detail' => $detail,
				);
				//$result = \DB::update('Job_List')->values($newJob)->where('Advanced_Job_No=', $jobsoard_no);
				var_dump($newJob);
				return $result;
			}
			$newJob = array(
				'websaleid' => $id,
				'Customer' => "Websale: $id " . $neworder['BillingCompanyName'],
				'Date_Ordered' => date('Y-m-d', strtotime("now")),
				'Promised_Due_Date' => date('Y-m-d', strtotime("+1 week")),
				'Brief_Job_Description' => $lines
			);
			if ($order['PaymentDeclined'] == "Y") {
				$newJob['Priority_Level'] = 3;
				$newJob['Next_Action_Required'] = '<div><br/></div><div><font face="Tekton Pro Cond" size=3 color="red"><strong>PAYMENT WAS DECLINED FOR THIS ORDER</strong></font></div><div>Job has been added automatically from websales</div>';
			}
			else {
				$newJob['Priority_Level'] = 0;
				$newJob['Next_Action_Required'] = '<div><br/></div><div><font face="Tekton Pro Cond" size=3 color="red"><strong>' .
				 $order['OrderStatus'] . '</strong></font></div><div>Job has been added automatically from
																			 websales</div>';
				$newJob['Main_Employee_Responsible'] = 'Automatic Websale';
				$newJob['Can_work_be_done_today'] = -1;
				$newJob['Phone'] = $order['BillingPhoneNumber'];
				$newJob['Deliver_to_Company'] = $order['ShipCompanyName'];
				$newJob['Client_PO'] = $order['PONum'];
				$shipping_address = $order['ShipAddress1'] . "\r\n";
				if (!empty($order['ShipAddress2'])) {
					$shipping_address .= $order['ShipAddress2'] . "\r\n";
				}
				$shipping_address .= $order['ShipCity'] . " " . $order['ShipState'] . " " . $order['ShipPostalCode'] . "\r\n" . $order['ShipCountry'];
				$newJob['Site_Ship_to_Address'] = $shipping_address;
				$newJob['Attention'] = $order['ShipFirstName'] . ' ' . $order['ShipLastName'];
				$newJob['Goods_Ordered'] = 'No';
				$freight_method = Orders::$shipping_types[$order['ShippingMethodID']];
				$payment_method = Orders::$payment_types[$order['PaymentMethodID']];
				$comments = (strlen($order['Order_Comments']) > 0) ? $order['Order_Comments'] . "\r\n" : '';
				$newJob['Detail'] = $comments . "Payment Method: " . $payment_method .
				 "\r\nShipping Method: " . $freight_method . "\r\nFreight Paid: " . $order['TotalShippingCost'];
				$updates = "Initial Automated Insert Details: \r\n";
				foreach ($order as $key => $value) {
					if (!empty($value)) {
						$updates .= "[$key]: $value\r\n";
					}
				}
				foreach ($orderdetails as $key => $detail) {
					$updates .= "----------------------\r\nOrder Line $key:\r\n----------------------\r\n";
					foreach ($detail as $key => $value) {
						if (!empty($value)) {
							$updates .= "[$key]: $value\r\n";
						}
					}
				}
				$newJob['Updates'] = $updates;
				//\DB::insert('Job_List')->values($newJob);
				var_dump($newJob);
				return $result;
			}
			\DB::change_connection();
		}
	}

