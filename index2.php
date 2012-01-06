<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include ('bootstrap.php');
	$_GET['start'] = isset($_GET['start']) ? $_GET['start'] : 0;
	echo '<html><head><style>
body,table,img,td,div {
margin:0;padding:0;border-width: 0;

}
table {
border-collapse: collapse;
}
	td {
	 height:68pt;
	 max-height:68pt;
	 min-height:68pt;
overflow:hidden;
font-size:8pt;
text-align:left;
background-color:rgba(0,0,0,.1);


}
td.barcode {
text-align:center;
	width:68pt;
	min-width:68pt;
	max-width:68pt;
}
td.space{
width:4pt;
}
td.desc {

	width:109.5pt;
	min-width:109.5pt;
	max-width:109.5pt;

}
td.desc span {
font-weight: bold;
font-size: larger;
}
div  {
left:-10pt;
display:inline-block;
padding-top:11pt;
 }

	 table{
	 text-align:left;
 vertical-align: middle ;

	 }
	</style></head><body>';
	$items = array(
		'Bar-Cone450r'=>'125',);

	$result = DB::select('s.stock_id', 's.description')->from('stock_master s');
	$items = array_flip($items);
	foreach ($items as $item) {
		$result->or_where("s.stock_id LIKE ", $item);
	}
	$result =$result ->fetch()->all();
	$i = 0;
	$j = 0;
	$count = 1;
	$items = array_flip($items);

	echo '<div class="page-break"><table ><tbody><tr>';
	while ($item = array_pop($result)) {
		if ($count < $items[$item['stock_id']]) {
			array_push($result, $item);
			$count++;
		}
		else {
			$count = 1;
		}
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<td class="barcode"><IMG SRC="data:image/gif;base64,
		' . $image . '">' . '</td><td class="desc"><span>' . $item['stock_id'] . '</span><br> ' .
		$item['description']
		 .
		 '</td>';
		if ($i == 2) {
			$i = 0;
			if ($j == 10) {
				echo '</tr></table></div><div><table><tr>';
				$j = 0;
				continue;
			}
			else {
				echo '</tr><tr>';
				$j++;
			}
		}
		else {
			echo '<td class="space"></td>';
			$i++;
		}
	}
	echo '</table></div></body><script type="text/javascript">
	function breakeveryheader(){
	for (i=0; i<document.getElementsByTagName("div").length; i++){
	document.getElementsByTagName("div")[i].style.pageBreakBefore="always";}
	}
breakeveryheader();
	</script></html>';

?>
