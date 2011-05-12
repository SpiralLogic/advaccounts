<?php
/*
 * jQuery File Upload Plugin PHP Example 4.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://creativecommons.org/licenses/MIT/
 */
	ini_set('post_max_size', '3M');
	ini_set('upload_max_filesize', '3M');
	error_reporting(E_ALL | E_STRICT);
	class UploadHandler {
		private $upload_id;
		private $order_no;

		function __construct($order_no) {
			$this->order_no = $order_no;

		}

		private function make_dir() {
			$old = umask(0);
			//@mkdir($this->upload_dir, 0777);
			umask($old);
		}

		private function get_file_object($upload_id) {
			/*			$file_path = $this->upload_dir . $file_name;
						if ($file_name[0] === '.' || !is_file($file_path)) {
							return null;
						}

						$file = new stdClass();
						$file->name = $file_name;
						$file->size = filesize($file_path);
						return $file;*/
			$sql = "SELECT * FROM upload WHERE id = {$upload_id} LIMIT 1";
			$result = db_query($sql, 'Could not query uploads');
			$result = db_fetch_assoc($result);
			$file = new stdClass();
			$file->name = $result ['filename'];
			$file->type = $result ['type'];
			$file->size = $result ['size'];
			return $file;

		}

		public function get() {
			$info = array();
			$upload_id = (isset($_REQUEST['id'])) ? stripslashes($_REQUEST['id']) : null;
			if ($upload_id) {
				$sql = "SELECT UNCOMPRESS(content) as content,type FROM upload WHERE `id` = {$upload_id}";
				$result = db_query($sql, 'Could not retrieve file');
				$result = db_fetch_assoc($result);
				header('Cache-Control: no-cache, must-revalidate');
				header('Content-type: ' . $result['type']);
				$content = stripslashes($result['content']);
				echo $content;
			}
			else {
				$sql = "SELECT `id`,`filename` as name, `size` ,`type` FROM upload WHERE `order_no` = {$this->order_no}";
				$result = db_query($sql, 'Could not retrieve upload information');
				if (db_num_rows($result) < 1)
					return;
				while ($row = db_fetch_assoc($result)) {
					$info[] = $row;
				}
				header('Cache-Control: no-cache, must-revalidate');
				header('Content-type: application/json');
				echo json_encode($info);
			}
		}

		public function post() {
			$headers = getallheaders();
			$file_request = isset($_FILES['file']) ? $_FILES['file'] : null;
			$uploaded_file = $file_request ? $file_request['tmp_name'] : null;
			$file = new stdClass();
			$file->name = basename(isset($headers['X-File-Name']) ? $headers['X-File-Name'] : $file_request['name']);
			$file->type = isset($headers['X-File-Type']) ? $headers['X-File-Type'] : $file_request['type'];
			$file->size = intval(isset($headers['X-File-Size']) ? $headers['X-File-Size'] : $file_request['size']);
			if ($file->name) {
				//$file_path = $this->upload_dir . $file->name;
				//$append_file = is_file($file_path) && $file->size > filesize($file_path);
				clearstatcache();
				if ($uploaded_file && is_uploaded_file($uploaded_file)) {
					// multipart/formdata uploads (POST method uploads)
					//if ($append_file) {
					/*file_put_contents(
																		 $file_path,
																		 fopen($uploaded_file, 'r'),
																		 FILE_APPEND
																 );
					 }
					 else {
						 move_uploaded_file($uploaded_file, $file_path);
				 }*/
					$fp = fopen($uploaded_file, 'r');
					$content = fread($fp, filesize($uploaded_file));
				}
				else {
					// Non-multipart uploads (PUT method support)
					//file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
					$fp = fopen('php://input', 'rb');
					$content = tmpfile();
					while (!feof($fp)) {
						fwrite($content, fread($fp, 8192));
					}
				}
				fclose($fp);
				$content = addslashes($content);
				//	$file->size = filesize($file_path);
				begin_transaction();
				$sql = "INSERT INTO upload (`filename`,`size`,`type`,`order_no`,`content`) VALUES ('{$file->name}','{$file->size}','{$file->type}','{$this->order_no}', COMPRESS('{$content}'))";
				db_query($sql, 'Could not insert file into database');
				$upload_id = db_insert_id();
				$file->id = $this->upload_id = $upload_id;
				//$sql = "INSERT INTO upload_to_order(upload_id,order_no) VALUES (`{$upload_id}`,`($this->order_no}`)";
				//db_query($sql);
				commit_transaction();

			}
			if (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] === 'XMLHttpRequest') {
				header('Content-type: application/json');

			}
			echo json_encode($file);
		}

		public function delete() {
			$name = isset($_REQUEST['file']) ? ($_REQUEST['file']) : null;
			$id = isset($_REQUEST['id']) ? ($_REQUEST['id']) : null;
			$sql = "DELETE FROM upload WHERE `id` = {$id} AND `filename` = '{$name}'";
			$result = db_query($sql, 'Could not delete file');
			header('Content-type: application/json');
			echo json_encode($result);
		}
	}
