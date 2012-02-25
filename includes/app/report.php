<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 31/10/11
	 * Time: 8:06 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Report
	{
		public $id;
		public $name;
		public $ar_params;
		public $controls;

		/**
		 * @param			$id
		 * @param			$name
		 * @param null $ar_params
		 */
		public function __construct($id, $name, $ar_params = null) {
			$this->id = $id;
			$this->name = $name;
			if ($ar_params) {
				$this->set_controls($ar_params);
			}
		}

		/**
		 * @param $ar_params
		 */
		protected function set_controls($ar_params) {
			$this->controls = $ar_params;
		}

		public function get_controls() {
			return $this->controls;
		}

		public function add_custom_reports() {
			global $installed_extensions;
			// include reports installed inside extension modules
			if (count($installed_extensions) > 0) {
				$extensions = $installed_extensions;
				foreach ($extensions as $ext) {
					if (($ext['active'] && $ext['type'] == 'module')) {
						$file = PATH_TO_ROOT . '/' . $ext['path'] . "/reporting/reports_custom.php";
						if (file_exists($file)) {
							/** @noinspection PhpIncludeInspection */
							include_once($file);
						}
					}
				}
			}
			$file = COMPANY_PATH . "reporting/reports_custom.php";
			if (file_exists($file)) {
				/** @noinspection PhpIncludeInspection */
				include_once($file);
			}
		}
	}
