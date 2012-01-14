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

			$orders = $this->getNewWebsales();

			if (count($orders) > 0) {
				echo "<br><br>Jamming all new orders in local database to Jobs Board<br>";
			}
			else {
				echo "<br><br>No new websales, checking for local strays<br>";
			}
			$result = $this->notOnJobsboard();
			if ($result) {
				echo "<br><br>New orders successfully added!<br>";
			}
			echo "<br><br>Updating Jobs Board with fetched orders<br>";

			foreach ($orders as $order) {
				$this->insertJob($order['OrderID']);
			}
			\DB::change_connection();
		}
		protected function getNewWebsales() {
			$orders = new Orders();
			if (!$orders) {
				echo "<br>No Orders Retrieved";
				return false;
			}
			foreach ($orders as $order) {
				$orders->process();
			}

			return $orders;
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
					\DB::update('WebOrders')->value('ison_jobsboard', $job)->where('OrderID=', $neworder['OrderID'])->exec();
					echo "Websale {$neworder['OrderID']} successfully added to Jobs Board with Job Number $job!";
				}
			}
			return true;
		}

		protected function insertJob($id) {
			\DB::change_connection();
			$order = \DB::select()->from('WebOrders')->where('OrderID=', $id)->fetch()->one();
			$orderdetails = \DB::select()->from('WebOrderDetails')->where('OrderID=', $id)->fetch()->all();
			if (!$order) {
				return false;
			}
			\DB::change_connection('jobsboard');
			$jobsboard_no = \DB::select('Advanced_Job_No')->from('Job_List')->where('websaleid=', $id)->fetch()->one();
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
				$result = \DB::update('Job_List')->values($newJob)->where('Advanced_Job_No=', $jobsoard_no)->exec();

				$this->insertLines($lineitems, $jobsoard_no);
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
				$jobsboard_no = \DB::insert('Job_List')->values($newJob)->exec();
				$this->insertlines($lineitems, $jobsboard_no);

				return $result;
			}
			\DB::change_connection();
		}
		function insertlines($lines, $jobid) {
			$existing_lines = $this->getLines($jobid);
			$deleted = array_diff_key($lines, $existing_lines);
			foreach ($deleted as $line) {
				$line['quantity'] = 0;
				$line['description'] .= " DELETED!";
				\DB::update('JobListItems')->where('line_id=', $line['line_id'])->and_where('job_id=', $jobid)->exec();
				echo 'Deleting line ' . $line['line_id'] . ' for job ' . $jobid;
			}
			foreach ($lines as $line) {
				$line['job_id'] = $jobid;
				try {
					$line['line_id'] = \DB::insert('JobListItems')->values($line)->exec();
					echo 'Added line ' . $line['line_id'] . ' for job ' . $jobid;
				}
				catch (\DBDuplicateException $e) {
					\DB::update('JobListItems')->values($line)->where('line_id=', $line['line_id'])->and_where('job_id=', $jobid);
					echo 'Updating line ' . $line['line_id'] . ' for job ' . $jobid;
				}
			}
			echo '<br>';
		}
		function getLines($jobid) {
			$result = \DB::select()->from('JobListItems')->where('job_id=', $jobid)->fetch()->all();
			return $result;
		}
	}

