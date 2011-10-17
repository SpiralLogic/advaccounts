<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/10/11
	 * Time: 7:22 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Security {

		static function check_page($page_security) {

			if (!$_SESSION["wa_current_user"]->can_access_page($page_security)) {
				echo "<center><br><br><br><b>";
				echo _("The security settings on your account do not permit you to access this function");
				echo "</b>";
				echo "<br><br><br><br></center>";
				end_page();
				exit;
			}
		}

		/*
			 Helper function for setting page security level depeding on
			 GET start variable and/or some value stored in session variable.
			 Before the call $page_security should be set to default page_security value.
		 */
		static function set_page($value = null, $trans = array(), $gtrans = array()) {
			global $page_security;
			// first check is this is not start page call
			foreach ($gtrans as $key => $area) {
				if (isset($_GET[$key])) {
					$page_security = $area;
					return;
				}
			}
			// then check session value
			if (isset($trans[$value])) {
				$page_security = $trans[$value];
				return;
			}
		}


		//	Removing magic quotes from nested arrays/variables

		//============================================================================
		static function strip_quotes($data) {
			if (get_magic_quotes_gpc()) {
				if (is_array($data)) {
					foreach ($data as $k => $v) {
						$data[$k] = self::strip_quotes($data[$k]);
					}
				} else {
					return stripslashes($data);
				}
			}
			return $data;
		}
	}
