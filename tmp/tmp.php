<?php  
$db_server = "localhost";    // hostname MySQL server  
$db_username = "fa";   // username MySQL server
$db_password = "1willenberg";   // password MySQL server
$db_name = "fa";       // database name
  
$pattern = "";          // search string
$new_pattern = "";  // replacement string,
                                // can be empty  
  
// login to MySQL server  
$link = mysql_connect( $db_server, $db_username, $db_password);  
  
if (!$link)  
{  
  die('Could not connect: ' . mysql_error());  
}  
  
// list all tables in the database containing the search pattern  
$sql = "SHOW TABLES FROM `" . $db_name . "`";  
$sql .= " LIKE '%" . $pattern . "%'";  
  
$result = mysql_query ( $sql, $link );  
if (!$result)  
{  
  die("Invalid query: " . mysql_error( $link ));  
}  
  
$renamed = 0;  
$failed = 0;  
  
while ( $row = mysql_fetch_array ($result) )  
{  
  // rename every table by replacing the search pattern   
  // with a new pattern  
  $table_name = $row[0];  
  $new_table_name = str_replace ( $pattern, $new_pattern, $table_name);  
  
  $sql = "RENAME TABLE `" . $db_name . "`.`" . $table_name . "`";  
  $sql .= " TO `" . $db_name . "`.`" . $new_table_name . "`";  
  
  $result_rename = mysql_query ( $sql, $link );  
  if ($result_rename)  
  {  
    echo "Table `" . $table_name . "` renamed to :`";  
    echo $new_table_name . "`.\n";  
    $renamed++;  
  }  
  else  
  {  
    // notify when the renaming failed and show reason why  
    echo "Renaming of table `" . $table_name . "` has failed: ";  
    echo mysql_error( $link ) . "\n";  
    $failed++;  
  }  
}  
  
echo $renamed . " tables were renamed, " . $failed . " failed.\n";  
  
// close connection to MySQL server  
mysql_close( $link );  
?>  