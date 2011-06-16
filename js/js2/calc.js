$(function($)
  {
	  $("#wrapper").delegate(".amount", 'focus', function()
	  {
		  $(this).calculator({
								 showOn: 'operator', isOperator: mathsOnly});

		  function mathsOnly(ch, event, value, base, decimalChar)
		  {
			  return '+-*/'.indexOf(ch) > -1 && !(ch == '-' && value == '');
		  }

	  });
/// $("#_page_body").('.amount','focus',
	  /*, buttonImageOnly: true, buttonImage: '../themes/default/images/calculator.png'*/

  });
