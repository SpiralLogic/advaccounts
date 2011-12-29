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
	 width:181.3pt;
	 min-width:90.65pt;
	 max-width:90.65pt;
	 height:70pt;
	 max-height:70pt;
	 min-height:70pt;
padding:0;margin:0;
overflow:hidden;
font-size:8pt;
text-align:center;
}img {
margin-top:3pt;
}
div  { display:block;
 }

	 table{
	 text-align:center;
vertical-align: baseline ;
margin:0;
padding:0;
	 }
	</style></head><body>';
	$result = DB::select('stock_id','description')->from('stock_master')->where("description LIKE ",
																																							'%cone%')->and_where("stock_id LIKE ",
																																															 'Bar%')->fetch()->all();
	$i = 0;
	$j = 0;
	$count=1;
	echo '<div class="page-break"><table ><tbody><tr>';
	while ($item = array_pop($result)) {
		if ($count<10) {array_push($result,$item);$count++;}else {$count=1;}
		$data = Barcode::create(array('code' => $item['stock_id'] . "\n" . $item['description']));
		$image = base64_encode($data);
		echo '<td ><IMG SRC="data:image/gif;base64,
		' . $image . '">' . '</td><td  >' . $item['stock_id'] . ' ' . $item['description'] . '</td>';
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