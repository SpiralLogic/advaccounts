/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:49 PM
 * To change this template use File | Settings | File Templates.
 */
var item;
function getItems(id) {

    $.post("search.php",
    {id: id}, function(data) {
        item = data;
        var content = "<table  style='margin:0 auto;'>";

        $.each(data, function(i, data) {
             content += "<tr><td style='font-weight:bold;'>"+i+"</td><td width='20px'></td><td>"+data+"</td></tr>";
            
        });
        $("#item").append(content+"</table>");
    }, 'json')
}