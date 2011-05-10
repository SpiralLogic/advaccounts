<!DOCTYPE html>

		<?php
$path_to_root='./';
	include_once('includes/session.inc');
	$sql = "CREATE TABLE upload(id INT NOT NULL AUTO_INCREMENT,
filename VARCHAR(30) NOT NULL,
type VARCHAR(30) NOT NULL,
size INT NOT NULL,
content MEDIUMBLOB NOT NULL,
PRIMARY KEY(id)
)";
	db_query($sql, 'fucked the job');


	?>