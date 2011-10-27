<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/10/11
	 * Time: 3:45 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB_Utils extends DBOld {

		public static function create($connection) {
			$db = mysql_connect($connection["host"],
				$connection["dbuser"], $connection["dbpassword"]);
			if (!mysql_select_db($connection["dbname"], $db)) {
				$sql = "CREATE DATABASE " . $connection["dbname"] . "";
				if (!mysql_query($sql))
					return 0;
				mysql_select_db($connection["dbname"], $db);
			}
			return $db;
		}

		public static function import($filename, $connection = null, $force = true) {
			$db = DBOld::getInstance();

			$allowed_commands     = array(
				"create"							 => 'table_queries',
				"alter table"					=> 'table_queries',
				"insert"							 => 'data_queries',
				"update"							 => 'data_queries',
				"drop table if exists" => 'drop_queries'
			);
			$ignored_mysql_errors = array( //errors ignored in normal (non forced) mode
				'1022', // duplicate key
				'1050', // Table %s already exists
				'1060', // duplicate column name
				'1061', // duplicate key name
				'1062', // duplicate key entry
				'1091' // can't drop key/column check if exists
			);
			$data_queries         = array();
			$drop_queries         = array();
			$table_queries        = array();
			$sql_errors           = array();

			ini_set("max_execution_time", "180");
			DBOld::query("SET foreign_key_checks=0");

			// uncrompress gziped backup files
			if (strpos($filename, ".gz") || strpos($filename, ".GZ"))
				$lines = DB_Utils::ungzip("lines", $filename);
			elseif (strpos($filename, ".zip") || strpos($filename, ".ZIP"))
				$lines = DB_Utils::unzip("lines", $filename);
			else
				$lines = file("" . $filename);

			// parse input file
			$query_table = '';
			foreach ($lines as $line_no => $line)
			{
				$line = trim($line);

				if ($query_table == '') { // check if line begins with one of allowed queries
					foreach ($allowed_commands as $cmd => $table)
					{
						if (strtolower(substr($line, 0, strlen($cmd))) == $cmd) {
							$query_table      = $table;
							${$query_table}[] = array('', $line_no + 1);
							break;
						}
					}
				}
				if ($query_table != '') // inside allowed query
				{
					$table = $query_table;
					if (substr($line, -1) == ';') // end of query found
					{
						$line = substr($line, 0, strlen($line) - 1); // strip ';'
						$query_table = '';
					}
					${$table}[count(${$table}) - 1][0] .= $line . "\n";
				}
			}
			/*
						 { 	// for debugging purposes

						 $f = fopen(PATH_TO_ROOT.'/tmp/dbimport.txt', 'w+');
						 fwrite($f, print_r($drop_queries,true) ."\n");
						 fwrite($f, print_r($table_queries,true) ."\n");
						 fwrite($f, print_r($data_queries,true));
						 fclose($f);
						 }
					 */
			// execute drop tables if exists queries
			if (is_array($drop_queries)) {
				foreach ($drop_queries as $drop_query)
				{
					if (!DBOld::query($drop_query[0])) {
						if (!in_array(DBOld::error_no(), $ignored_mysql_errors) || !$force)
							$sql_errors[] = array(DBOld::error_msg($db), $drop_query[1]);
					}
				}
			}

			// execute create tables queries
			if (is_array($table_queries)) {
				foreach ($table_queries as $table_query)
				{
					if (!DBOld::query($table_query[0])) {
						if (!in_array(DBOld::error_no(), $ignored_mysql_errors) || !$force) {
							$sql_errors[] = array(DBOld::error_msg($db), $table_query[1]);
						}
					}
				}
			}

			// execute insert data queries
			if (is_array($data_queries)) {
				foreach ($data_queries as $data_query)
				{
					if (!DBOld::query($data_query[0])) {
						if (!in_array(DBOld::error_no(), $ignored_mysql_errors) || !$force)
							$sql_errors[] = array(DBOld::error_msg($db), $data_query[1]);
					}
				}
			}

			DBOld::query("SET foreign_key_checks=1");

			if (count($sql_errors)) {
				// display first failure message; the rest are probably derivative
				$err = $sql_errors[0];
				ui_msgs::display_error(sprintf(_("SQL script execution failed in line %d: %s"),
						$err[1], $err[0]));
				return false;
			} else
				return true;
			//$shell_command = C_MYSQL_PATH . " -h $host -u $user -p{$password} $dbname < $filename";
			//shell_exec($shell_command);
		}

		// returns the content of the gziped $path backup file. use of $mode see below
		public static function ungzip($mode, $path) {
			$file_data = gzfile($path);
			// returns one string or an array of lines
			if ($mode != "lines")
				return implode("", $file_data);
			else
				return $file_data;
		}

		// returns the content of the ziped $path backup file. use of $mode see below
		public static function unzip($mode, $path) {

			$all = implode("", file($path));

			// convert path to name of ziped file
			$filename = preg_replace("/.*\//", "", $path);
			$filename = substr($filename, 0, strlen($filename) - 4);

			// compare filname in zip and filename from $_GET
			if (substr($all, 30, strlen($filename) - 4) . substr($all, 30 + strlen($filename) + 9, 4)
			 != $filename
			) {
				return ''; // exit if names differ
			}
			else
			{
				// get the suffix of the filename in hex
				$crc_bugfix = substr($all, 30, strlen($filename) + 13);
				$crc_bugfix = substr(substr($crc_bugfix, 0, strlen($crc_bugfix) - 4),
				 strlen($crc_bugfix) - 12 - 4);
				$suffix     = false;
				// convert hex to ascii
				for ($i = 0; $i < 12;)
				{
					$suffix .= chr($crc_bugfix[$i++] . $crc_bugfix[$i++] . $crc_bugfix[$i++]);
				}

				// remove central directory information (we have always just one ziped file)
				$comp = substr($all, -(strlen($all) - 30 - strlen($filename) - 13));
				$comp = substr($comp, 0, (strlen($comp) - 80 - strlen($filename) - 13));

				// fix the crc bugfix (see function save_to_file)
				$comp      = "x�" . $comp . $suffix;
				$file_data = gzuncompress($comp);
			}

			// returns one string or an array of lines
			if ($mode != "lines")
				return $file_data;
			else
				return explode("\n", $file_data);
		}

		public static function backup($conn, $ext = 'no', $comm = '') {
			$filename = $conn['dbname'] . "_" . date("Ymd_Hi") . ".sql";

			return DB_Utils::export($conn, $filename, $ext, $comm);
		}

		// generates a dump of $db database
		// $drop and $zip tell if to include the drop table statement or dry to pack
		public static function export($conn, $filename, $zip = 'no', $comment = '') {

			$error = false;
			// set max string size before writing to file
			$max_size = 1048576 * 2; // 2 MB
			// changes max size if value can be retrieved
			if (ini_get("memory_limit"))
				$max_size = 900000 * ini_get("memory_limit");

			// set backupfile name
			if ($zip == "gzip")
				$backupfile = $filename . ".gz";
			elseif ($zip == "zip")
				$backupfile = $filename . ".zip";
			else
				$backupfile = $filename;
			$company = DB_Company::get_pref('coy_name');

			//create comment
			$out = "# MySQL dump of database '" . $conn["dbname"] . "' on host '" . $conn["host"] . "'\n";
			$out .= "# Backup Date and Time: " . date("Y-m-d H:i") . "\n";
			$out .= "# Built by " . APP_TITLE . " " . VERSION . "\n";
			$out .= "# " . POWERED_URL . "\n";
			$out .= "# Company: " . html_entity_decode($company, ENT_QUOTES, $_SESSION['language']->encoding) . "\n";
			$out .= "# User: " . $_SESSION["wa_current_user"]->name . "\n\n";

			// write users comment
			if ($comment) {
				$out .= "# Comment:\n";
				$comment = preg_replace("'\n'", "\n# ", "# " . $comment);
				//$comment=str_replace("\n", "\n# ", $comment);
				foreach (explode("\n", $comment) as $line)
				{
					$out .= $line . "\n";
				}
				$out .= "\n";
			}

			//$out.="use ".$db.";\n"; we don't use this option.

			// get auto_increment values and names of all tables
			$res        = DBOld::query("show table status");
			$all_tables = array();
			while ($row = DBOld::fetch($res))
			{

				$all_tables[] = $row;
			}
			// get table structures
			foreach ($all_tables as $table)
			{
				$res1                      = DBOld::query("SHOW CREATE TABLE `" . $table['Name'] . "`");
				$tmp                       = DBOld::fetch($res1);
				$table_sql[$table['Name']] = $tmp["Create Table"];
			}

			// find foreign keys
			$fks = array();
			if (isset($table_sql)) {
				foreach ($table_sql as $tablenme => $table)
				{
					$tmp_table = $table;
					// save all tables, needed for creating this table in $fks
					while (($ref_pos = strpos($tmp_table, " REFERENCES ")) > 0)
					{
						$tmp_table        = substr($tmp_table, $ref_pos + 12);
						$ref_pos          = strpos($tmp_table, "(");
						$fks[$tablenme][] = substr($tmp_table, 0, $ref_pos);
					}
				}
			}
			// order $all_tables
			$all_tables = DB_Utils::order_sql_tables($all_tables, $fks);

			// as long as no error occurred
			if (!$error) {
				//while($row=mysql_fetch_array($res))
				foreach ($all_tables as $row)
				{
					$tablename             = $row['Name'];
					$auto_incr[$tablename] = $row['Auto_increment'];
					$out .= "\n\n";
					// export tables
					$out .= "### Structure of table `" . $tablename . "` ###\n\n";
					$out .= "DROP TABLE IF EXISTS `" . $tablename . "`;\n\n";
					$out .= $table_sql[$tablename];

					// add auto_increment value
					if ($auto_incr[$tablename])
						$out .= " AUTO_INCREMENT=" . $auto_incr[$tablename];
					$out .= " ;";
					$out .= "\n\n\n";

					// export data
					if (!$error) {
						$out .= "### Data of table `" . $tablename . "` ###\n\n";

						// check if field types are NULL or NOT NULL
						$res3 = DBOld::query("SHOW COLUMNS FROM `" . $tablename . "`");

						$field_null = array();
						for ($j = 0; $j < DBOld::num_rows($res3); $j++)
						{
							$row3         = DBOld::fetch($res3);
							$field_null[] = $row3[2] == 'YES' && $row3[4] === null;
						}

						$res2 = DBOld::query("SELECT * FROM `" . $tablename . "`");
						for ($j = 0; $j < DBOld::num_rows($res2); $j++)
						{
							$out .= "INSERT INTO `" . $tablename . "` VALUES (";
							$row2 = DBOld::fetch_row($res2);
							// run through each field
							for ($k = 0; $k < $nf = DBOld::num_fields($res2); $k++)
							{
								$out .= DBOld::escape($row2[$k], $field_null[$k]);
								if ($k < ($nf - 1))
									$out .= ", ";
							}
							$out .= ");\n";

							// if saving is successful, then empty $out, else set error flag
							if (strlen($out) > $max_size && $zip != "zip") {
								if (Files::save_to_file($backupfile, $zip, $out))
									$out = "";
								else
									$error = true;
							}
						}
						// an error occurred! Try to delete file and return error status
					}
					elseif ($error)
					{
						unlink(BACKUP_PATH . $backupfile);
						return false;
					}

					// if saving is successful, then empty $out, else set error flag
					if (strlen($out) > $max_size && $zip != "zip") {
						if (Files::save_to_file($backupfile, $zip, $out))
							$out = "";
						else
							$error = true;
					}
				}
				// an error occurred! Try to delete file and return error status
			}
			else
			{
				unlink(BACKUP_PATH . $backupfile);
				return false;
			}

			// if (mysql_error()) return "DB_ERROR";
			//mysql_close($con);

			//if ($zip == "zip")
			//	$zip = $time;
			if (Files::save_to_file($backupfile, $zip, $out)) {
				$out = "";
			}
			else
			{
				unlink(BACKUP_PATH . $backupfile);
				return false;
			}
			return $backupfile;
		}

		// orders the tables in $tables according to the constraints in $fks
		// $fks musst be filled like this: $fks[tablename][0]=needed_table1; $fks[tablename][1]=needed_table2; ...
		public static function order_sql_tables($tables, $fks) {
			// do not order if no contraints exist
			if (!count($fks))
				return $tables;

			// order
			$new_tables = array();
			$existing   = array();
			$modified   = true;
			while (count($tables) && $modified == true)
			{
				$modified = false;
				foreach ($tables as $key => $row)
				{
					// delete from $tables and add to $new_tables
					if (isset($fks[$row['Name']])) {
						foreach ($fks[$row['Name']] as $needed)
						{
							// go to next table if not all needed tables exist in $existing
							if (!in_array($needed, $existing))
								continue 2;
						}
					}
					// delete from $tables and add to $new_tables
					$existing[]   = $row['Name'];
					$new_tables[] = $row;
					prev($tables);
					unset($tables[$key]);
					$modified = true;
				}
			}

			if (count($tables)) {
				// probably there are 'circles' in the constraints, bacause of that no proper backups can be created yet
				// TODO: this will be fixed sometime later through using 'alter table' commands to add the constraints after generating the tables
				// until now, just add the lasting tables to $new_tables, return them and print a warning
				foreach ($tables as $row)
				{
					$new_tables[] = $row;
				}
				echo "<div class=\"red_left\">THIS DATABASE SEEMS TO CONTAIN 'RING CONSTRAINTS'. WA DOES NOT SUPPORT THEM. PROBABLY THE FOLOWING BACKUP IS DEFECT!</div>";
			}
			return $new_tables;
		}
	}
