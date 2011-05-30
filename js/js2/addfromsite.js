/**
 * User: Eli Sklar
 * Date: 30/05/11 - 9:50 AM
 */
$=jQuery;
code = $('.product_code').text();
$.getJSON('http://advaccounts/sales/sales_order_remote.php?jsoncallback=?',
          {
	          item: code
          },
		function(data) {
			console.log('Added to order: '+data);
		}
);


//javascript:var%20s=document.createElement('script');s.setAttribute('src',%20'https://advanced.sorijen.net.au:2223/js/js2/addfromsite.js');document.getElementsByTagName('body')[0].appendChild(s);;void(0);