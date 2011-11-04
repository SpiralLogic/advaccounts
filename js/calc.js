$(function($)
  {
	  $("#wrapper").delegate(".amount", 'focus', function()
	  {
			var value = $(this).val();

			value=((value[0]=='-') ? '-': '')+value.replace(/[^0-9\.]/g,'');
			$(this).val(value);
		  $(this).calculator({
								 useThemeRoller: true, showOn: 'operator', isOperator: mathsOnly,constrainInput:false});
 
		  function mathsOnly(ch, event, value, base, decimalChar)
		  {

			  return '+-*/'.indexOf(ch) > -1 && !(ch == '-' && (value == '' || value == '0.00'));
		  }

	  });

  });
