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

		function __construct($id, $name, $ar_params = null)
			{
				$this->id = $id;
				$this->name = $name;
				if ($ar_params) {
					$this->set_controls($ar_params);
				}
			}

		protected function set_controls($ar_params)
			{
				$this->controls = $ar_params;
			}

		function get_controls()
			{
				return $this->controls;
			}

		function add_custom_reports(&$reports)
			{
				global $installed_extensions;
				// include reports installed inside extension modules
				if (count($installed_extensions) > 0) {
					$extensions = $installed_extensions;
					foreach ($extensions as $ext) {
						if (($ext['active'] && $ext['type'] == 'module')) {
							$file = PATH_TO_ROOT . '/' . $ext['path'] . "/reporting/reports_custom.php";
							if (file_exists($file)) {
								include_once($file);
							}
						}
					}
				}
				$file = COMPANY_PATH . "/reporting/reports_custom.php";
				if (file_exists($file)) {
					include_once($file);
				}
			}
	}