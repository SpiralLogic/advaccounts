<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 3/02/12
	 * Time: 4:52 PM
	 * To change this template use File | Settings | File Templates.
	 */
	namespace Modules;
	/**
	 *
	 */
	class Jobsboard {
		protected $currentJob;
		protected $lines;
		public $order_no;

		/***
		 * @param $trans_no
		 * @return mixed
		 */
		function removejob($trans_no) {
			$job = $this->get_job($trans_no);
			if ($this->jobExists($trans_no)) {
				$data['Customer'] = $job->Customer . ' - CANCELLED';
				$data['Updates'] = date('Y-m-d h:m:s', strtotime("now")) . ' ' . 'Job has BEEN CANCELLED from acounts by ' . $_SESSION['current_user']->name . ' ' . chr(13) . chr(10) . $job->Updates;
				$data['Next_Action_Required'] = '<div>Job has BEEN CANCELLED from accounts by ' . $_SESSION['current_user']->name . '</div>' . $job->Next_Action_Required;
				$data['order_ref'] = '';
				$data['Priority_Level'] = 5;
				$data['order_no'] = 0;
				return \DB::update('Job_List')->values($this->currentJob)->where('Advanced_Job_No=', $this->currentJob['Advanced_Job_No'])->exec();
			}
			\Event::error('There is no current Order to remove from jobsboard');

			return false;
		}

		/**
		 * @param \Sales_Order $job_data
		 * @internal param $type
		 * @internal param $trans_no
		 * @internal param $so_type
		 * @return mixed
		 */
		function addjob($job_data) {

			$this->order_no = $order_no = $job_data->trans_no;
			$user_data = $_SESSION['current_user']->name;

			$branch = new \Debtor_Branch($job_data->Branch);

			$orderlines = $this->getOrderLines();
			\DB::change_connection('jobsboard');
			$update = var_export($job_data, true);
			$job = $this->get_job($order_no);
			$exists = ($job['Advanced_Job_No'] > 0);
			$lines=array();
			foreach ($orderlines as $line) {
				/***
				 * @var \Sales_Line $line
				 */
				$lines[$line['id']] = array(
					'line_id' => $line['id'],
					'stock_code' => $line['stk_code'],
					'price' => $line['unit_price'],
					'description' => $line['description'],
					'quantity' => $line['quantity']

				);
			}
			if ($exists) {
				$deleted = array_diff_key($this->getLines(),$lines );
						foreach ($deleted as $line) {
					$lines[$line['line_id']]['quantity'] = 0;
					$lines[$line['line_id']]['description'] .= " DELETED!";
				}
			}

			if ($exists) {
				$update = date('Y-m-d h:m:s', strtotime("now")) . ' ' . 'Job Updated from acounts by ' . $user_data . ' ' . chr(13) . chr(10) . $job['Updates'];
				$data['Next_Action_Required'] = '<div>Job has been updated from accounts ' . $user_data . '</div>' . $job['Next_Action_Required'];
			} else {
				$data['Customer'] = $job_data->customer_name;
				$data['Priority_Level'] = 3;
				$data['Date_Ordered'] = date('Y-m-d', strtotime("now"));
				$data['Promised_Due_Date'] = date('Y-m-d', strtotime("+1 week"));
				$data['Next_Action_Required'] = 'Job has been added from accounts';
				$data['Main_Employee_Responsible'] = $user_data;
				$data['salesman'] = $user_data;
				$data['Can_work_be_done_today'] = '-1';
				$data['Goods_Ordered'] = 'No';
			}
			$data['order_no'] = $order_no;
			if (empty($job_data->phone)) {
				$job_data->phone = $branch['phone'];
			}
			$data['Phone'] = $job_data->phone;
			$data['order_ref'] = $job_data->reference;
			$data['Client_PO'] = $job_data->cust_ref;
			$data['debtor_no'] = $job_data->customer_id;
			$data['Site_Ship_to_Address'] = $job_data->deliver_to . chr(13) . chr(10) . str_replace('\n', chr(13) . chr(10), $job_data->delivery_address);
			$data['Deliver_to_Company'] = $job_data->deliver_to;
			$data['Attention'] = $job_data->name;
			$data['Detail'] = str_replace('\n', chr(13) . chr(10), $job_data->Comments);
			$data['Updates'] = $update;
			$this->lines = $lines;
			($exists) ? $this->updateJob($data) : $this->insertJob($data);
			\DB::change_connection();
			return;
		}


		static function tasks() {
			\DB::connect(\Config::get('db.jobsboard'));
			\DB::change_connection('jobsboard');
			$result = false;
			try {
				\DB::query('UPDATE Job_List SET priority_changed = NOW() , Main_Employee_Responsible = previous_user WHERE
Priority_Level<5 AND priority_changed < (NOW() - INTERVAL 3 DAY) AND Main_Employee_Responsible<>previous_user AND priority_changed>0');
				$result = \DB::num_rows();
			}
			catch (\Exception $e) {
			}
			if ($result) {
				\Event::notice($result . ' Jobs were returned to their previous responslble person.');
			}
			$result = false;
			try {
				\DB::query('UPDATE Job_List SET has_worked_change = NOW() , Can_work_be_done_today = -1 WHERE
Priority_Level<5 AND has_worked_change < (NOW() - INTERVAL 3 DAY) AND Can_work_be_done_today=0 AND has_worked_change>0');
				$result = \DB::num_rows();
			}
			catch (\Exception $e) {
			}
			if ($result) {
				\Event::notice($result . ' Jobs were changed back to having "work can be done" due to inactivity.');
			}
			\DB::change_connection();
		}

		/***
		 * @param $trans_no
		 * @return mixed
		 */
		function get_job($trans_no) {
			$this->currentJob = \DB::select()->from('Job_List')->where('order_no=', $trans_no)->fetch()->one();
			if ($this->currentJob) $this->getLines();
			return $this->currentJob;
		}

		/***
		 * @return bool
		 */
		private function jobExists() {
			if (empty($this->currentJob)) {
				return false;
			}
			return (isset($this->currentJob['Advanced_Job_No']));
		}

		private function insertJob($data) {
			$result = \DB::insert('Job_List')->values($data)->exec();
			if ($result) {
				$data['Advanced_Job_No'] = $result;
				$this->currentJob = $data;
				$this->insertLines();
			}
		}

		private function updateJob($data) {
			$result = \DB::update('Job_List')->values($data)->where('Advanced_Job_No=', $this->currentJob['Advanced_Job_No'])->exec();
			if ($result) {
				$this->insertLines();
			}
		}

		private function insertLines() {
			$lines = $this->lines;
			$this->lines = array();
			$currentLines=$this->getLines();
			foreach ($lines as $line) {
				if (isset($currentLines[$line['line_id']]) ) {
					$this->updateline($line);
				} else {
					$this->insertLine($line);
				}
			}
		}

		private function insertLine($line) {
			$line['job_id'] = $this->currentJob['Advanced_Job_No'];
			$line_id = \DB::insert('JobListItems')->values($line)->exec();
			$this->lines[$line_id] = $line;
		}

		private function updateLine($line) {
			$line['job_id'] = $this->currentJob['Advanced_Job_No'];
			\DB::update('JobListItems')->values($line)->where('line_id=', $line['line_id'])->and_where('job_id=', $this->currentJob['Advanced_Job_No'])->exec();
		}

		private function getLines() {
			$lines = \DB::select()->from('JobListItems')->where('job_id=', $this->currentJob['Advanced_Job_No'])->fetch()->all();
			foreach ($lines as $line) {
				$lines[$line['line_id']] = $line;
			}
			return $lines;
		}

		public function getOrderLines() {
			$lines = \DB::select()->from('sales_order_details')->where('order_no=', $this->order_no)->fetch()->all();
			return $lines;
		}
	}
