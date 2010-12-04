/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:49 PM
 * To change this template use File | Settings | File Templates.
 */
var item;
function getItem(id) {

    $.post("search.php",
    {id: id}, function(data) {
        item = data;
        var content = "<table  style='margin:0 auto;'><tr>";
var even = true;
        $.each(data, function(i, data) {



            content += "<td style='font-weight:bold;'>"+i+"</td><td width='20px'></td><td><input value='" + data + "' size=20></input></td><td width='20px'></td>";
            if (!even) {
                content += "</tr><tr>";
            }
            even = !even;
                    });
        $("#itemdetails").empty().append(content+"</tr></table>");
    }, 'json')
}