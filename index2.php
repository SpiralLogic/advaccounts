<!DOCTYPE html>

		<?php
  include('includes/ui2/html.inc');
/* mysql_pconnect('localhost','fa','1willenberg');
mysql_select_db('fa');
$sql = "SHOW tables";
$rs = mysql_query($sql);
while ($row = mysql_fetch_array($rs))
{
	$tbl = $row[0];
	if ($tbl=='0_debtor_trans' || $tbl=='0_stock_master' || $tbl=='0_debtor_trans_view') continue;
	$sql = "ALTER TABLE $tbl  ENGINE=INNODB";
	mysql_query($sql);
}*/
HTML::html(true);
HTML::head(true)->meta(array('charset' => 'UTF-8'));
HTML::title(true);
echo 'Hi Syed!';
HTML::title()->head;
HTML::table(true)->tr(true)->td(true);
echo "dsgsgs";
HTML::td()->tr->tr(true)->td(true);
echo "sfdgsrfg";
HTML::td()->tr->table;
HTML::img('teetst', array('src' => 'http://www.w3schools.com/images/w3schoolslogo.gif'));

HTML::html();

