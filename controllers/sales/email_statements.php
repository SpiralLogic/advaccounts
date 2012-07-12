<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_("Email Statements"), SA_OPEN);
  $sql = "SELECT DISTINCT db.*, b.email , b.phone FROM debtor_balances db, branches b WHERE db.debtor_id = b.debtor_id AND LENGTH(b.email)>0 AND b.branch_ref = 'Accounts' AND Balance>0";
  DB::query($sql, "The customer details could not be retrieved");
  $rows         = DB::fetchAll();
  $view         = new View('sales/email_statements');
  $view['rows'] = $rows;
  $view->render();
  $js = <<<JS
$(function() {
$("#send").click(sendstatements);
})
var tosend = document.getElementsByClassName('email');
tosend = toArray(tosend);

sendstatements = function() {
var v = tosend.pop(), postVars = {
REP_ID:108,
PARAM_0:0,
PARAM_1:'',
PARAM_2:1,
PARAM_3:'',
PARAM_4:0,
PARAM_5:0,
PARAM_6:0,
PARAM_7:0
};

if (!v) return;
		if (!v.checked) sendstatements();
		postVars['PARAM_0']=v.value;
$.post('/reporting/prn_redirect.php',postVars,function(data) {
console.log(data);
$('#table').after(data);
		sendstatements();
});

}
function toArray(obj) {
 var array = [];
 // iterate backwards ensuring that length is an UInt32
 for (var i = obj.length >>> 0; i--;) {
 array[i] = obj[i];
 }
 return array;
}
JS;
  JS::beforeload($js);
  Page::end();
