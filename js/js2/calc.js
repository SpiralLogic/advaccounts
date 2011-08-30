$(function($)
  {
	  $("#wrapper").delegate(".amount", 'focus', function()
	  {
			$(this).val($(this).val().replace(/[^0-9\.]/g,''));
		  $(this).calculator({
								 useThemeRoller: true, showOn: 'operator', isOperator: mathsOnly,constrainInput:false});
 
		  function mathsOnly(ch, event, value, base, decimalChar)
		  {
			  return '+-*/'.indexOf(ch) > -1 && !(ch == '-' && value == '');
		  }

	  });

  });
