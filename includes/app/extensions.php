<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/11/11
	 * Time: 7:48 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Extensions
	{
		public static function get_access($id)
		{
			global $installed_extensions;
			$ext               = $installed_extensions[$id];
			$security_sections = $security_areas = array();
			if (isset($ext['acc_file'])) {
				/** @noinspection PhpIncludeInspection */
				include(PATH_TO_ROOT . ($ext['type'] == 'plugin' ? '/modules/' : '/') . $ext['path'] . '/' . $ext['acc_file']);
			}
			return array($security_areas, $security_sections);
		}

		public static function add_access()
		{
			global $security_areas, $security_sections;
			$installed_extensions = Config::get('extensions.installed');
			/** @noinspection PhpUnusedLocalVariableInspection */
			foreach ($installed_extensions as $extid => $ext) {
				$scode       = 100;
				$acode       = 100;
				$accext      = static::get_access($extid);
				$extsections = $accext[1];
				$extareas    = $accext[0];
				$extcode     = $extid << 16;
				$trans       = array();
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
					$area[0]               = $extcode | $section | ($acode++);
					$security_areas[$code] = $area;
				}
			}
		}
	}
