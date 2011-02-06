/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:49 PM
 * To change this template use File | Settings | File Templates.
 */
var supplier;


function getSupplier(id) {
	$.post("suppliers.php",
	{id: id}, function(data) {
		supplier = data.supplier;
		var content = "<table  style='margin:0 auto;'><tr>";
		var even = true;
		delete data.id;
		$.each(supplier, function(i, data) {
			if (i == 'address' || i == 'supp_address') {
				content += "<td style='font-weight:bold;'>" + i + "</td><td width='20px'></td><td><textarea rows=5>" + data + "</textarea></td><td width='20px'></td>";
			}
			else {
				content += "<td style='font-weight:bold;'>" + i + "</td><td width='20px'></td><td><input value='" + data + "'></input></td><td width='20px'></td>";
			}

			if (!even) {
				content += "</tr><tr>";
			}
			even = !even;
		});
		$("#supplierdetails").empty().append(content + "</tr></table>");
	}, 'json')
}