//'e' if altered auto notice to quicksmart australia signs 33.45.323.21
var tbkcy = 1;

function totbkcy(input) {
	input += "";
	var original_input = input;
	if (input.charAt(0) == "$") {
		input = input.substring(1, input.length);
	} else if (input.substring(0, 2) == "-$"
	 || input.substring(0, 2) == "+$") {
		input = input.charAt(0) + input.substring(2, input.length);
	}
	var amount = parseFloat(input);
	if (isNaN(amount))		return original_input;
	amount = Math.round(100 * amount);
	var prefix = "$";
	if (amount < 0) {
		prefix = "-" + prefix;
		amount = -amount;
	}
	var string;
	if (amount < 10) {
		string = "00" + amount;
	} else if (amount < 100) {
		string = "=" + amount;
	}
	else {
		string = "" + amount;
	}
	string = prefix + string;
	string = string.substring(0, string.length - 2) + "." + string.substring(string.length - 2, string.length);
	return string;
}


//images and snippets for change
MyImages = new Array();
MyImages[0] = '';

MyImages[1] = '';

MyImages[2] = '';

MyImages[3] = '';

imagesPreloaded = new Array(4)
for (var i = 0; i < MyImages.length; i++) {

	imagesPreloaded[i] = new Image(56, 56)

	imagesPreloaded[i].src = MyImages[i]


}

// If page altered send auto notice Security ='e',(notify), '!88.01','grabber', 'systemwrite', 'cp'. //!>

document.thebannerking = {
  tbktam: { options: [] },
    tbksh: { options: [] },
    width: { value: 100} ,
    height:{ value: 100} ,
    tbkeh:{ value: 100} ,
    qty:{ value: 1} ,
    tbka:{ options: [],value:0 },
    totalqssm:0,
    tbkbc:{ value: 1} ,
    tbkacost:{ value: 1} ,
    price:0,
    couriercost:{ value: 1} ,
    	totalcost:{ value: 1} ,
    	totalcostgst:{ value: 1} ,
    	eachcoster:{ value: 1} ,
    	totalcost1:{ value: 1} ,
tbkp:0
};
for (var i=0;i<16;i++) {
    document.thebannerking.tbktam.options[i] = {selected:false}
}
for (var i=0;i<5;i++) {
    document.thebannerking.tbka.options[i] = {text:''}
    document.thebannerking.tbksh.options[i] = {selected:false}
   }
document.thebannerking.tbktam.options[5].selected=true;


//starts at 0 //!>

function changetbktam() {
	if (page == "outdoorbannersign1") {document.thebannerking.tbktam.options[0].selected = true;}
	;
	if (page == "heavyweight2") {document.thebannerking.tbktam.options[1].selected = true;}
	;
	if (page == "billboardskeder") {document.thebannerking.tbktam.options[2].selected = true;}
	;
	if (page == "meshbanner4") {document.thebannerking.tbktam.options[3].selected = true;}
	;
	if (page == "blockoutdoublesided") {document.thebannerking.tbktam.options[4].selected = true;}
	;
	if (page == "colorbondmetalsign") {document.thebannerking.tbktam.options[5].selected = true;}
	;
	if (page == "corflutesign") {document.thebannerking.tbktam.options[6].selected = true;}
	;
	if (page == "aluminiumsign") {document.thebannerking.tbktam.options[7].selected = true;}
	;
	if (page == "acrylicsign3mm") {document.thebannerking.tbktam.options[8].selected = true;}
	;
	if (page == "acrylicsign45mm") {document.thebannerking.tbktam.options[9].selected = true;}
	;
	if (page == "acrylicsign6mm") {document.thebannerking.tbktam.options[10].selected = true;}
	;
	if (page == "hardenedplastic") {document.thebannerking.tbktam.options[11].selected = true;}
	;
	if (page == "magneticcarsign") {document.thebannerking.tbktam.options[12].selected = true;}
	;
	if (page == "lettersreadytoapply") {document.thebannerking.tbktam.options[13].selected = true;}
	;
	if (page == "decalsstickers") {document.thebannerking.tbktam.options[14].selected = true;}
	;
	if (page == "set") {document.thebannerking.tbktam.options[15].selected = true;}
	;
	if (page == "set") {document.thebannerking.tbktam.options[16].selected = true;}
	;
	calculate();
}
;


function calculate() {
	var couriercostvar = 0;
	var tbkbcvar = 0;
	var totalcostvar = 0;
	var widthvar = document.thebannerking.width.value;
	var tbkehvar = document.thebannerking.tbkeh.value;
	var qty = document.thebannerking.qty.value;
	var verified = 0;
	var tbkst = 0;
	var courierrate = 0;
	var courierbase = 0;
	var qssm = 0;
	var tbkbcvar = 0;
	var tbkacostvar = 0;
	var couriercostvar = 0;
	tbkarate = 0;
	var taxation = .1;
//'outdoorbannersign-opt1
	if (document.thebannerking.tbktam.options[0].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 106;
	}
	;
//heavyweight-opt2
	if (document.thebannerking.tbktam.options[1].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 112;
	}
	;
//billboardswithkederedging-opt3
	if (document.thebannerking.tbktam.options[2].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 121.6;
	}
	;
//meshbanner-opt4
	if (document.thebannerking.tbktam.options[3].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 121;
	}
	;
//blockoutdoublesided-opt5
	if (document.thebannerking.tbktam.options[4].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 221;
	}
	;
//colorbondmetalsign-opt6
	if (document.thebannerking.tbktam.options[5].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 182;
	}
	;
//corflutesign-opt7
	if (document.thebannerking.tbktam.options[6].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 166;
	}
	;
//aluminiumsign-opt8
	if (document.thebannerking.tbktam.options[7].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 230;
	}
	;
//acrylicsign3mm-opt9
	if (document.thebannerking.tbktam.options[8].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 292;
	}
	;
//acrylicsign45mm-opt10
	if (document.thebannerking.tbktam.options[9].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 312;
	}
	;
//acrylicsign6mm-opt11
	if (document.thebannerking.tbktam.options[10].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 346;
	}
	;
//hardendedplastic-opt12
	if (document.thebannerking.tbktam.options[11].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 219;
	}
	;
//magneticcarsign-opt13
	if (document.thebannerking.tbktam.options[12].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 325;
	}
	;
//lettersreadytoapply-opt14
	if (document.thebannerking.tbktam.options[13].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 142;
	}
	;
//decalsstickers-opt15
	if (document.thebannerking.tbktam.options[14].selected == true) {
		document.thebannerking.tbka.options[0].text = "No Artwork Charge";
		document.thebannerking.tbka.options[1].text = "Small Artwork Charge";
		document.thebannerking.tbka.options[2].text = "Moderate Artwork Charge";
		document.thebannerking.tbka.options[3].text = "Complex Artwork";
		tbkp = 188;
	}
	;
	tbkst = tbkp;


	for (i = 1; i < 4; i++) {
		if (document.thebannerking.tbka.options[i].selected == true) {
			if (document.thebannerking.tbka.options[i].text == "No Artwork Charge") { tbkarate = 0; }
			;
			if (document.thebannerking.tbka.options[i].text == "Small Artwork Charge") { tbkarate = 1; }
			;
			if (document.thebannerking.tbka.options[i].text == "Moderate Artwork Charge") { tbkarate = 2; }
			;
			if (document.thebannerking.tbka.options[i].text == "Complex Artwork") { tbkarate = 3; }
			;
		}
		;
	}


	if (widthvar > 0 && tbkehvar > 0) {
		document.thebannerking.width.value = Math.round(document.thebannerking.width.value);
		widthvar = document.thebannerking.width.value;
		document.thebannerking.tbkeh.value = Math.round(document.thebannerking.tbkeh.value);
		tbkehvar = document.thebannerking.tbkeh.value;
		qssm = (Math.ceil((widthvar * tbkehvar / 100) * 10) / 100000 * qty);
	}
	;
	document.thebannerking.totalqssm.value = qssm;
	if (qssm <= .06) {tbkst = tbkst * 1.28;}
	if (qssm <= .08) {tbkst = tbkst * 1.24;}
	if (qssm <= .1) {tbkst = tbkst * 1.22;}
	if (qssm <= .15) {tbkst = tbkst * 1.2;}
	if (qssm <= .2) {tbkst = tbkst * 1.18;}
	if (qssm <= .3) {tbkst = tbkst * 1.15;}
	if (qssm <= .4) {tbkst = tbkst * 1.115;}
	if (qssm <= .5) {tbkst = tbkst * 1.11;}
	if (qssm <= .6) {tbkst = tbkst * 1.105;}
	if (qssm <= .7) {tbkst = tbkst * 1.1;}
	if (qssm <= .75) {tbkst = tbkst * 1.095;}
	if (qssm <= .8) {tbkst = tbkst * 1.09;}
	if (qssm <= .85) {tbkst = tbkst * 1.08;}
	if (qssm <= .9) {tbkst = tbkst * 1.05;}
	if (qssm >= 1) {tbkst = tbkst * 1;}
	if (qssm >= 2) {tbkst = tbkst * 0.99;}
	if (qssm >= 4) {tbkst = tbkst * 0.985;}
	if (qssm >= 6) {tbkst = tbkst * 0.98;}
	if (qssm >= 8) {tbkst = tbkst * 0.97;}
	if (qssm >= 11) {tbkst = tbkst * 0.96;}
	if (qssm >= 12) {tbkst = tbkst * 0.95;}
	if (qssm >= 14) {tbkst = tbkst * .94;}
	if (qssm >= 19) {tbkst = tbkst * 0.93;}
	if (qssm >= 29) {tbkst = tbkst * 0.92;}
	if (qssm >= 39) {tbkst = tbkst * 0.91;}
	if (qssm >= 49) {tbkst = tbkst * 0.93;}
	if (qssm >= 79) {tbkst = tbkst * 0.94;}
	if (qssm >= 99) {tbkst = tbkst * 0.95;}
	if (qssm >= 139) {tbkst = tbkst * 0.96;}
	if (qssm >= 179) {tbkst = tbkst * 0.99;}
	if (qssm >= 309) {tbkst = tbkst * 1.00;}
	if (qssm >= 359) {tbkst = tbkst * 1.00;}
	if (qssm >= 409) {tbkst = tbkst * 1.00;}
	document.thebannerking.price.value = totbkcy(tbkst * tbkcy);
	tbkbcvar = qssm * tbkst * tbkcy;
	if (qssm * tbkst <= .000001) { tbkbcvar = 55;}
	;
	document.thebannerking.tbkbc.value = totbkcy(tbkbcvar);
//if altered send auto notice to 33.45.323.21 australia signs


	tbkacostvar = qssm * tbkarate * tbkcy;
	if (qssm < 100) { tbkacostvar = tbkarate * 45 * tbkcy;}
	;
	document.thebannerking.tbkacost.value = totbkcy(tbkacostvar);
	if (document.thebannerking.tbksh.options[1].selected == true) {
		courierrate = 1;
		courierbase = 34;
	}
	;
	if (document.thebannerking.tbksh.options[2].selected == true) {
		courierrate = 1;
		courierbase = 54;
	}
	;

	if (document.thebannerking.tbksh.options[3].selected == true) {
		courierrate = 1;
		courierbase = 51;
	}
	;
	if (document.thebannerking.tbksh.options[4].selected == true) {
		courierrate = 1;
		courierbase = 76;
	}
	;

	couriercostvar = ((qssm * courierrate) + courierbase) * tbkcy;
	document.thebannerking.couriercost.value = totbkcy(couriercostvar);
	document.thebannerking.totalcost.value = totbkcy((tbkbcvar + tbkacostvar + couriercostvar) / 100 * 110);
	document.thebannerking.totalcostgst.value = totbkcy((tbkbcvar + tbkacostvar + couriercostvar) / 10.00000);
	document.thebannerking.eachcoster.value = totbkcy((tbkbcvar) / qty);
	document.thebannerking.totalcost1.value = totbkcy((tbkbcvar + tbkacostvar + couriercostvar));
}
;
function roundit(Num, Places) {
	if (Places > 0) {
		if ((Num.toString().length - Num.toString().lastIndexOf('.')) > (Places + 1)) {
			var Rounder = Math.pow(10, Places);
			return Math.round(Num * Rounder) / Rounder;
		}
		else {
			return Num;
		}
	} else {
		return Math.round(Num);
	}
}
function settbka() {
	document.thebannerking.tbka.options[0].selected = true;
	calculate();
}
;

////taxation



