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
				Renderer::end_page();
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

		/*
					 This function should be called whenever we want to extend core access level system
					 with new security areas and/or sections i.e.:
					 . on any page with non-standard security areas
					 . in security roles editor
					 The call should be placed between session.inc inclusion and Renderer::page() call.
					 Up to 155 security sections and 155 security areas for any extension can be installed.
				 */
		static function add_access_extensions() {
			global $security_areas, $security_sections;
			$installed_extensions = Config::get(null, null, 'installed_extensions');
			foreach ($installed_extensions as $extid => $ext) {
				$scode = 100;
				$acode = 100;
				$accext = Security::get_access_extensions($extid);
				$extsections = $accext[1];
				$extareas = $accext[0];
				$extcode = $extid << 16;

				$trans = array();
				foreach ($extsections as $code => $name) {
					$trans[$code] = $scode << 8;
					// reassign section codes
					$security_sections[$trans[$code] | $extcode] = $name;
					$scode++;
				}
				foreach ($extareas as $code => $area) {
					$section = $area[0] & 0xff00;
					// extension modules:
					// if area belongs to nonstandard section
					// use translated section codes and
					// preserve lower part of area code
					if (isset($trans[$section])) {
						$section = $trans[$section];
					}
					// otherwise assign next available
					// area code >99
					$area[0] = $extcode | $section | ($acode++);
					$security_areas[$code] = $area;
				}
			}
		}

		/*
					 Helper function to retrieve extension access definitions in isolated environment.
				 */
		static function get_access_extensions($id) {
			global $security_areas, $installed_extensions;

			$ext = $installed_extensions[$id];

			$security_sections = $security_areas = array();

			if (isset($ext['acc_file']))
				include(PATH_TO_ROOT . ($ext['type'] == 'plugin' ? '/modules/' : '/') . $ext['path'] . '/' . $ext['acc_file']);

			return array($security_areas, $security_sections);
		}
	}
