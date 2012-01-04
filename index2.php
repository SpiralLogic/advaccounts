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
body {
margin:0;padding:0;
}

	td {
	 height:68.5pt;
	 max-height:68.5pt;
	 min-height:68.5pt;
padding:0;margin:0;
overflow:hidden;
font-size:8pt;
text-align:left;
}
td.barcode {
	width:68.5pt;
	min-width:68.5pt;
	max-width:68.5pt;

}
td.desc {
padding-left:5pt;
	width:112.65pt;
	min-width:112.65pt;
	max-width:112.65pt;

}
img {
padding-top:0;
}
div  { display:inline-block;
 }

	 table{
	 text-align:left;
 vertical-align: middle ;
margin:0;
padding:0;
	 }
	</style></head><body>';
	$result = DB::select('s.stock_id', 's.description', 'sp.supp_name')->from('stock_master s,
	purch_data pd, suppliers sp')->where("s.description LIKE ", '%cone%')->and_where("s.stock_id LIKE ", 'Bar%')
	 ->and_where("s.stock_id NOT LIKE ", 'Bar-Cone1000%')->and_where("s.stock_id NOT LIKE ", '%sleeve%')
	 ->and_where("s.description NOT LIKE ", '%Econo%')->and_where("s.stock_id NOT LIKE ", '%super%')
	 ->and_where("s.stock_id NOT LIKE ", '%700coloured%')->and_where("s.id =pd.stockid")->and_where("s.no_sale <>1")
	 ->and_where("pd.supplier_id =sp.supplier_id")->fetch()->all();
	$i = 0;
	$j = 0;
	$count = 1;
	echo '<div class="page-break"><table ><tbody><tr>';
	while ($item = array_pop($result)) {
		if ($count < 9) {
			array_push($result, $item);
			$count++;
		}
		else {
			$count = 1;
		}
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<td class="barcode"><IMG SRC="data:image/gif;base64,
		' . $image . '">' . '</td><td class="desc"><b>' . $item['stock_id'] . '</b><br> ' . $item['description'] . '</td>';
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