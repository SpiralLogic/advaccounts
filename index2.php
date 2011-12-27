<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include ('bootstrap.php');
	Page::start('Barcode Tests');
	JS::footerFile('/js/barcode.js');
	$fontSize = 10; // GD1 in px ; GD2 in point
	$marge = 10; // between barcode and hri in pixel
	$x = 125; // barcode center
	$y = 125; // barcode center
	$height = 200; // barcode height in 1D ; module size in 2D
	$width = 2; // barcode height in 1D ; not use in 2D
	$angle = 90; // rotation in degrees : nb : non horizontable barcode might not be usable because of pixelisation
	$code = 'Hi Sam do you think this will be helpful!'; // barcode, of course ;)
	$type = 'datamatrix';
	// -------------------------------------------------- //
	//            ALLOCATE GD RESSOURCE
	// -------------------------------------------------- //
	$im = imagecreatetruecolor(300, 300);
	$black = ImageColorAllocate($im, 0x00, 0x00, 0x00);
	$white = ImageColorAllocate($im, 0xff, 0xff, 0xff);
	$red = ImageColorAllocate($im, 0xff, 0x00, 0x00);
	$blue = ImageColorAllocate($im, 0x00, 0x00, 0xff);
	imagefilledrectangle($im, 0, 0, 300, 300, $white);
	// -------------------------------------------------- //
	//                      BARCODE
	// -------------------------------------------------- //
	$data = Barcode::gd($im, $black, $x, $y, $angle, array('code' => $code), $width, $height);
JS::onload(<<<JS
	$('#barcode').barcode('test');
JS
);
	?><div id="barcode"></div><?
	Renderer::end_page(true);
?>