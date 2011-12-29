/*
 *  BarCode Coder Library (BCC Library)
 *  BCCL Version 2.0
 *    
 *  Porting : jQuery barcode plugin 
 *  Version : 2.0.2
 *   
 *  Date  : March 01, 2011
 *  Author  : DEMONTE Jean-Baptiste <jbdemonte@gmail.com>
 *            HOUREZ Jonathan
 *             
 *  Web site: http://barcode-coder.com/
 *  dual licence :  http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html
 *                  http://www.gnu.org/licenses/gpl.html
 *
 *  Managed :
 *     
 *    standard 2 of 5 (std25)
 *    interleaved 2 of 5 (int25)
 *    ean 8 (ean8)
 *    ean 13 (ean13)   
 *    code 11 (code11)
 *    code 39 (code39)
 *    code 93 (code93)
 *    code 128 (code128)  
 *    codabar (codabar)
 *    msi (msi)
 *    datamatrix (datamatrix)
 *  
 *  Output :
 *   
 *    CSS (compatible with any browser)
 *    SVG inline (not compatible with IE)
 *    BMP inline (not compatible with IE)      
 *    CANVAS html 5 (not compatible with IE)
 * 
 *  Changelog :
 *    
 *    1.1 - 2009/05/26 
 *      std25 fixed
 *    1.2 - 2009/09/10
 *      parseInt replaced by intval (nb: parseInt("09") => 0)
 *      code128 fixed (C Table analyse) - Thanks to Vadim for the bug report
 *    1.3 - 2009/09/26
 *      bmp and svg image renderer added   
 *    1.3.2 - 2009/10/03
 *      manage int and string formated values for barcode width/height
 *    1.3.3 - 2009/10/17
 *      no wait document is ready to add plugin
 *    2.0.1 - 2010/09/05
 *      CSS fixed to print easily - Thanks to L�o West for this fix
 *      datamatrix added - Jonathan Hourez join developper team
 *      canvas renderer added    
 *      code cleaned
 *      fontSize become an integer 
 *    2.0.2 - 2011-03-01
 *     integration to jquery updated
 *     table B of code 128 fixed (\\ instead of \) thanks to jmcarson
 */

(function ($) {
  
  var barcode = {
    settings:{
      barWidth: 1,
      barHeight: 50,
      moduleSize: 5,
      showHRI: true,
      addQuietZone: true,
      marginHRI: 5,
      bgColor: "#FFFFFF",
      color: "#000000",
      fontSize: 10,
      output: "css",
      posX: 0,
      posY: 0
    },
    intval: function(val){
      var type = typeof( val );
      if (type == 'string'){
        val = val.replace(/[^0-9-.]/g, "");
        val = parseInt(val * 1, 10);
        return isNaN(val) || !isFinite(val) ? 0 : val;
      }
      return type == 'number' && isFinite(val) ? Math.floor(val) : 0;
    },

    datamatrix: {   
      encoding:["101010011", "101011001", "101001011", "110010101",
                "101101001", "110101001", "100101011", "100101101",
                "100110101", "110100101", "101001101", "101100101",
                "1101011011", "1101101011", "1101101101", "1011011011",
                "1011001001", "1010010011", "1001001011", "1010011001"],
      lengthRows:       [ 10, 12, 14, 16, 18, 20, 22, 24, 26,  // 24 squares et 6 rectangular
                          32, 36, 40, 44, 48, 52, 64, 72, 80,  88, 96, 104, 120, 132, 144,
                          8, 8, 12, 12, 16, 16],
      lengthCols:       [ 10, 12, 14, 16, 18, 20, 22, 24, 26,  // Number of columns for the entire datamatrix
                          32, 36, 40, 44, 48, 52, 64, 72, 80, 88, 96, 104, 120, 132, 144,
                          18, 32, 26, 36, 36, 48],
      mappingRows:      [ 8, 10, 12, 14, 16, 18, 20, 22, 24,  // Number of rows for the mapping matrix
                          28, 32, 36, 40, 44, 48, 56, 64, 72, 80, 88, 96, 108, 120, 132,
                          6, 6, 10, 10, 14, 14],
      mappingCols:      [ 8, 10, 12, 14, 16, 18, 20, 22, 24,  // Number of columns for the mapping matrix
                          28, 32, 36, 40, 44, 48, 56, 64, 72, 80, 88, 96, 108, 120, 132,
                          16, 28, 24, 32, 32, 44],
      dataCWCount:      [ 3, 5, 8, 12,  18,  22,  30,  36,  // Number of data codewords for the datamatrix
                          44, 62, 86, 114, 144, 174, 204, 280, 368, 456, 576, 696, 816, 1050, 
                          1304, 1558, 5, 10, 16, 22, 32, 49],
      solomonCWCount:   [ 5, 7, 10, 12, 14, 18, 20, 24, 28, // Number of Reed-Solomon codewords for the datamatrix
                          36, 42, 48, 56, 68, 84, 112, 144, 192, 224, 272, 336, 408, 496, 620,
                          7, 11, 14, 18, 24, 28],
      dataRegionRows:   [ 8, 10, 12, 14, 16, 18, 20, 22, // Number of rows per region
                          24, 14, 16, 18, 20, 22, 24, 14, 16, 18, 20, 22, 24, 18, 20, 22,
                          6,  6, 10, 10, 14, 14],
      dataRegionCols:   [ 8, 10, 12, 14, 16, 18, 20, 22, // Number of columns per region
                          24, 14, 16, 18, 20, 22, 24, 14, 16, 18, 20, 22, 24, 18, 20, 22,
                          16, 14, 24, 16, 16, 22],
      regionRows:       [ 1, 1, 1, 1, 1, 1, 1, 1, // Number of regions per row
                          1, 2, 2, 2, 2, 2, 2, 4, 4, 4, 4, 4, 4, 6, 6, 6,
                          1, 1, 1, 1, 1, 1],
      regionCols:       [ 1, 1, 1, 1, 1, 1, 1, 1, // Number of regions per column
                          1, 2, 2, 2, 2, 2, 2, 4, 4, 4, 4, 4, 4, 6, 6, 6,
                          1, 2, 1, 2, 2, 2],
      interleavedBlocks:[ 1, 1, 1, 1, 1, 1, 1, 1, // Number of blocks
                          1, 1, 1, 1, 1, 1, 2, 2, 4, 4, 4, 4, 6, 6, 8, 8,
                          1, 1, 1, 1, 1, 1],
      logTab:           [ -255, 255, 1, 240, 2, 225, 241, 53, 3,  // Table of log for the Galois field
                          38, 226, 133, 242, 43, 54, 210, 4, 195, 39, 114, 227, 106, 134, 28, 
                          243, 140, 44, 23, 55, 118, 211, 234, 5, 219, 196, 96, 40, 222, 115, 
                          103, 228, 78, 107, 125, 135, 8, 29, 162, 244, 186, 141, 180, 45, 99, 
                          24, 49, 56, 13, 119, 153, 212, 199, 235, 91, 6, 76, 220, 217, 197, 
                          11, 97, 184, 41, 36, 223, 253, 116, 138, 104, 193, 229, 86, 79, 171, 
                          108, 165, 126, 145, 136, 34, 9, 74, 30, 32, 163, 84, 245, 173, 187, 
                          204, 142, 81, 181, 190, 46, 88, 100, 159, 25, 231, 50, 207, 57, 147, 
                          14, 67, 120, 128, 154, 248, 213, 167, 200, 63, 236, 110, 92, 176, 7, 
                          161, 77, 124, 221, 102, 218, 95, 198, 90, 12, 152, 98, 48, 185, 179, 
                          42, 209, 37, 132, 224, 52, 254, 239, 117, 233, 139, 22, 105, 27, 194, 
                          113, 230, 206, 87, 158, 80, 189, 172, 203, 109, 175, 166, 62, 127, 
                          247, 146, 66, 137, 192, 35, 252, 10, 183, 75, 216, 31, 83, 33, 73, 
                          164, 144, 85, 170, 246, 65, 174, 61, 188, 202, 205, 157, 143, 169, 82, 
                          72, 182, 215, 191, 251, 47, 178, 89, 151, 101, 94, 160, 123, 26, 112, 
                          232, 21, 51, 238, 208, 131, 58, 69, 148, 18, 15, 16, 68, 17, 121, 149, 
                          129, 19, 155, 59, 249, 70, 214, 250, 168, 71, 201, 156, 64, 60, 237, 
                          130, 111, 20, 93, 122, 177, 150],
      aLogTab:          [ 1, 2, 4, 8, 16, 32, 64, 128, 45, 90, // Table of aLog for the Galois field
                          180, 69, 138, 57, 114, 228, 229, 231, 227, 235, 251, 219, 155, 27, 54, 
                          108, 216, 157, 23, 46, 92, 184, 93, 186, 89, 178, 73, 146, 9, 18, 36, 
                          72, 144, 13, 26, 52, 104, 208, 141, 55, 110, 220, 149, 7, 14, 28, 56, 
                          112, 224, 237, 247, 195, 171, 123, 246, 193, 175, 115, 230, 225, 239, 
                          243, 203, 187, 91, 182, 65, 130, 41, 82, 164, 101, 202, 185, 95, 190, 
                          81, 162, 105, 210, 137, 63, 126, 252, 213, 135, 35, 70, 140, 53, 106, 
                          212, 133, 39, 78, 156, 21, 42, 84, 168, 125, 250, 217, 159, 19, 38, 76, 
                          152, 29, 58, 116, 232, 253, 215, 131, 43, 86, 172, 117, 234, 249, 223, 
                          147, 11, 22, 44, 88, 176, 77, 154, 25, 50, 100, 200, 189, 87, 174, 113, 
                          226, 233, 255, 211, 139, 59, 118, 236, 245, 199, 163, 107, 214, 129, 
                          47, 94, 188, 85, 170, 121, 242, 201, 191, 83, 166, 97, 194, 169, 127, 
                          254, 209, 143, 51, 102, 204, 181, 71, 142, 49, 98, 196, 165, 103, 206, 
                          177, 79, 158, 17, 34, 68, 136, 61, 122, 244, 197, 167, 99, 198, 161, 
                          111, 222, 145, 15, 30, 60, 120, 240, 205, 183, 67, 134, 33, 66, 132, 
                          37, 74, 148, 5, 10, 20, 40, 80, 160, 109, 218, 153, 31, 62, 124, 248, 
                          221, 151, 3, 6, 12, 24, 48, 96, 192, 173, 119, 238, 241, 207, 179, 75, 
                          150, 1],
      champGaloisMult: function(a, b){  // MULTIPLICATION IN GALOIS FIELD GF(2^8)
        if(!a || !b) return 0;
        return this.aLogTab[(this.logTab[a] + this.logTab[b]) % 255];
      },
      champGaloisDoub: function(a, b){  // THE OPERATION a * 2^b IN GALOIS FIELD GF(2^8)
        if (!a) return 0;
        if (!b) return a;
        return this.aLogTab[(this.logTab[a] + b) % 255];
      },
      champGaloisSum: function(a, b){ // SUM IN GALOIS FIELD GF(2^8)
        return a ^ b;
      },
      selectIndex: function(dataCodeWordsCount, rectangular){ // CHOOSE THE GOOD INDEX FOR TABLES
        if ((dataCodeWordsCount<1 || dataCodeWordsCount>1558) && !rectangular) return -1;
        if ((dataCodeWordsCount<1 || dataCodeWordsCount>49) && rectangular)  return -1;
        
        var n = 0;
        if ( rectangular ) n = 24;
        
        while (this.dataCWCount[n] < dataCodeWordsCount) n++;
        return n;
      },
      encodeDataCodeWordsASCII: function(text) {
        var dataCodeWords = new Array();
        var n = 0, i, c;
        for (i=0; i<text.length; i++){
          c = text.charCodeAt(i);
          if (c > 127) {  
            dataCodeWords[n] = 235;
            c = c - 127;
            n++;
          } else if ((c>=48 && c<=57) && (i+1<text.length) && (text.charCodeAt(i+1)>=48 && text.charCodeAt(i+1)<=57)) {
            c = ((c - 48) * 10) + ((text.charCodeAt(i+1))-48);
            c += 130;
            i++;
          } else c++; 
          dataCodeWords[n] = c;
          n++;
        }
        return dataCodeWords;
      },
      addPadCW: function(tab, from, to){    
        if (from >= to) return ;
        tab[from] = 129;
        var r, i;
        for (i=from+1; i<to; i++){
          r = ((149 * (i+1)) % 253) + 1;
          tab[i] = (129 + r) % 254;
        }
      },
      calculSolFactorTable: function(solomonCWCount){ // CALCULATE THE REED SOLOMON FACTORS
        var g = new Array();
        var i, j;
        
        for (i=0; i<=solomonCWCount; i++) g[i] = 1;
        
        for(i = 1; i <= solomonCWCount; i++) {
          for(j = i - 1; j >= 0; j--) {
            g[j] = this.champGaloisDoub(g[j], i);  
            if(j > 0) g[j] = this.champGaloisSum(g[j], g[j-1]);
          }
        }
        return g;
      },
      addReedSolomonCW: function(nSolomonCW, coeffTab, nDataCW, dataTab, blocks){ // Add the Reed Solomon codewords
        var temp = 0;    
        var errorBlocks = nSolomonCW / blocks;
        var correctionCW = new Array();
        
        var i,j,k;
        for(k = 0; k < blocks; k++) {      
          for (i=0; i<errorBlocks; i++) correctionCW[i] = 0;
          
          for (i=k; i<nDataCW; i=i+blocks){    
            temp = this.champGaloisSum(dataTab[i], correctionCW[errorBlocks-1]);
            for (j=errorBlocks-1; j>=0; j--){     
              if ( !temp ) {
                correctionCW[j] = 0;
              } else { 
                correctionCW[j] = this.champGaloisMult(temp, coeffTab[j]);
              }
              if (j>0) correctionCW[j] = this.champGaloisSum(correctionCW[j-1], correctionCW[j]);
            }
          }
          // Renversement des blocs calcules
          j = nDataCW + k;
          for (i=errorBlocks-1; i>=0; i--){
            dataTab[j] = correctionCW[i];
            j=j+blocks;
          }
        }
        return dataTab;
      },
      getBits: function(entier){ // Transform integer to tab of bits
        var bits = new Array();
        for (var i=0; i<8; i++){
          bits[i] = entier & (128 >> i) ? 1 : 0;
        }
        return bits;
      },
      next: function(etape, totalRows, totalCols, codeWordsBits, datamatrix, assigned){ // Place codewords into the matrix
        var chr = 0; // Place of the 8st bit from the first character to [4][0]
        var row = 4;
        var col = 0;
        
        do {
          // Check for a special case of corner
          if((row == totalRows) && (col == 0)){
            this.patternShapeSpecial1(datamatrix, assigned, codeWordsBits[chr], totalRows, totalCols);  
            chr++;
          } else if((etape<3) && (row == totalRows-2) && (col == 0) && (totalCols%4 != 0)){
            this.patternShapeSpecial2(datamatrix, assigned, codeWordsBits[chr], totalRows, totalCols);
            chr++;
          } else if((row == totalRows-2) && (col == 0) && (totalCols%8 == 4)){
            this.patternShapeSpecial3(datamatrix, assigned, codeWordsBits[chr], totalRows, totalCols);
            chr++;
          }
          else if((row == totalRows+4) && (col == 2) && (totalCols%8 == 0)){
            this.patternShapeSpecial4(datamatrix, assigned, codeWordsBits[chr], totalRows, totalCols);
            chr++;
          }
          
          // Go up and right in the datamatrix
          do {
            if((row < totalRows) && (col >= 0) && (assigned[row][col]!=1)) {
              this.patternShapeStandard(datamatrix, assigned, codeWordsBits[chr], row, col, totalRows, totalCols);
              chr++;
            }
            row -= 2;
            col += 2;      
          } while ((row >= 0) && (col < totalCols));
          row += 1;
          col += 3;
          
          // Go down and left in the datamatrix
          do {
            if((row >= 0) && (col < totalCols) && (assigned[row][col]!=1)){
              this.patternShapeStandard(datamatrix, assigned, codeWordsBits[chr], row, col, totalRows, totalCols);
              chr++;
            }
            row += 2;
            col -= 2;
          } while ((row < totalRows) && (col >=0));
          row += 3;
          col += 1;
        } while ((row < totalRows) || (col < totalCols));
      },
      patternShapeStandard: function(datamatrix, assigned, bits, row, col, totalRows, totalCols){ // Place bits in the matrix (standard or special case)
        this.placeBitInDatamatrix(datamatrix, assigned, bits[0], row-2, col-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[1], row-2, col-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[2], row-1, col-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[3], row-1, col-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[4], row-1, col, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[5], row, col-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[6], row, col-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[7], row,  col, totalRows, totalCols);
      },  
      patternShapeSpecial1: function(datamatrix, assigned, bits, totalRows, totalCols ){
        this.placeBitInDatamatrix(datamatrix, assigned, bits[0], totalRows-1,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[1], totalRows-1,  1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[2], totalRows-1,  2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[3], 0, totalCols-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[4], 0, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[5], 1, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[6], 2, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[7], 3, totalCols-1, totalRows, totalCols);
      },
      patternShapeSpecial2: function(datamatrix, assigned, bits, totalRows, totalCols ){
        this.placeBitInDatamatrix(datamatrix, assigned, bits[0], totalRows-3,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[1], totalRows-2,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[2], totalRows-1,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[3], 0, totalCols-4, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[4], 0, totalCols-3, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[5], 0, totalCols-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[6], 0, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[7], 1, totalCols-1, totalRows, totalCols);
      },  
      patternShapeSpecial3: function(datamatrix, assigned, bits, totalRows, totalCols ){
        this.placeBitInDatamatrix(datamatrix, assigned, bits[0], totalRows-3,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[1], totalRows-2,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[2], totalRows-1,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[3], 0, totalCols-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[4], 0, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[5], 1, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[6], 2, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[7], 3, totalCols-1, totalRows, totalCols);
      },
      patternShapeSpecial4: function(datamatrix, assigned, bits, totalRows, totalCols ){
        this.placeBitInDatamatrix(datamatrix, assigned, bits[0], totalRows-1,  0, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[1], totalRows-1, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[2], 0, totalCols-3, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[3], 0, totalCols-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[4], 0, totalCols-1, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[5], 1, totalCols-3, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[6], 1, totalCols-2, totalRows, totalCols);
        this.placeBitInDatamatrix(datamatrix, assigned, bits[7], 1, totalCols-1, totalRows, totalCols);
      },
      placeBitInDatamatrix: function(datamatrix, assigned, bit, row, col, totalRows, totalCols){ // Put a bit into the matrix
        if (row < 0) {
          row += totalRows;
          col += 4 - ((totalRows+4)%8);
        }
        if (col < 0) {
          col += totalCols;
          row += 4 - ((totalCols+4)%8);
        }
        if (assigned[row][col] != 1) {
          datamatrix[row][col] = bit;
          assigned[row][col] = 1;
        }
      },
      addFinderPattern: function(datamatrix, rowsRegion, colsRegion, rowsRegionCW, colsRegionCW){ // Add the finder pattern
        var totalRowsCW = (rowsRegionCW+2) * rowsRegion;
        var totalColsCW = (colsRegionCW+2) * colsRegion;
        
        var datamatrixTemp = new Array();
        datamatrixTemp[0] = new Array();
        for (var j=0; j<totalColsCW+2; j++){
          datamatrixTemp[0][j] = 0;
        }
        for (var i=0; i<totalRowsCW; i++){
          datamatrixTemp[i+1] = new Array();
          datamatrixTemp[i+1][0] = 0;
          datamatrixTemp[i+1][totalColsCW+1] = 0;
          for (var j=0; j<totalColsCW; j++){
            if (i%(rowsRegionCW+2) == 0){
              if (j%2 == 0){
                datamatrixTemp[i+1][j+1] = 1;
              } else { 
                datamatrixTemp[i+1][j+1] = 0;
              }
            } else if (i%(rowsRegionCW+2) == rowsRegionCW+1){ 
              datamatrixTemp[i+1][j+1] = 1;
            } else if (j%(colsRegionCW+2) == colsRegionCW+1){
              if (i%2 == 0){
                datamatrixTemp[i+1][j+1] = 0;
              } else {
                datamatrixTemp[i+1][j+1] = 1;
              }
            } else if (j%(colsRegionCW+2) == 0){ 
              datamatrixTemp[i+1][j+1] = 1;
            } else{
              datamatrixTemp[i+1][j+1] = 0;
              datamatrixTemp[i+1][j+1] = datamatrix[i-1-(2*(parseInt(i/(rowsRegionCW+2))))][j-1-(2*(parseInt(j/(colsRegionCW+2))))];
            }
          }
        }
        datamatrixTemp[totalRowsCW+1] = new Array();
        for (var j=0; j<totalColsCW+2; j++){
          datamatrixTemp[totalRowsCW+1][j] = 0;
        }
        return datamatrixTemp;
      },
      getDigit: function(text, rectangular){
        var dataCodeWords = this.encodeDataCodeWordsASCII(text); // Code the text in the ASCII mode
        var dataCWCount = dataCodeWords.length;
        var index = this.selectIndex(dataCWCount, rectangular); // Select the index for the data tables
        var totalDataCWCount = this.dataCWCount[index]; // Number of data CW
        var solomonCWCount = this.solomonCWCount[index]; // Number of Reed Solomon CW 
        var totalCWCount = totalDataCWCount + solomonCWCount; // Number of CW      
        var rowsTotal = this.lengthRows[index]; // Size of symbol
        var colsTotal = this.lengthCols[index];
        var rowsRegion = this.regionRows[index]; // Number of region
        var colsRegion = this.regionCols[index];
        var rowsRegionCW = this.dataRegionRows[index];
        var colsRegionCW = this.dataRegionCols[index];
        var rowsLengthMatrice = rowsTotal-2*rowsRegion; // Size of matrice data
        var colsLengthMatrice = colsTotal-2*colsRegion;
        var blocks = this.interleavedBlocks[index];  // Number of Reed Solomon blocks
        var errorBlocks = (solomonCWCount / blocks); 
        var dataBlocks = (totalDataCWCount / blocks);
        
        this.addPadCW(dataCodeWords, dataCWCount, totalDataCWCount); // Add codewords pads
        
        var g = this.calculSolFactorTable(errorBlocks); // Calculate correction coefficients
        
        this.addReedSolomonCW(solomonCWCount, g, totalDataCWCount, dataCodeWords, blocks); // Add Reed Solomon codewords
        
        var codeWordsBits = new Array(); // Calculte bits from codewords
        for (var i=0; i<totalCWCount; i++){
          codeWordsBits[i] = this.getBits(dataCodeWords[i]);
        }
        
        var datamatrix = new Array(); // Put data in the matrix
        var assigned = new Array();
        
        for (var i=0; i<colsLengthMatrice; i++){
          datamatrix[i] = new Array();
          assigned[i] = new Array();
        }
        
        // Add the bottom-right corner if needed
        if ( ((rowsLengthMatrice * colsLengthMatrice) % 8) == 4) {
          datamatrix[rowsLengthMatrice-2][colsLengthMatrice-2] = 1;
          datamatrix[rowsLengthMatrice-1][colsLengthMatrice-1] = 1;
          datamatrix[rowsLengthMatrice-1][colsLengthMatrice-2] = 0;
          datamatrix[rowsLengthMatrice-2][colsLengthMatrice-1] = 0;
          assigned[rowsLengthMatrice-2][colsLengthMatrice-2] = 1;
          assigned[rowsLengthMatrice-1][colsLengthMatrice-1] = 1;
          assigned[rowsLengthMatrice-1][colsLengthMatrice-2] = 1;
          assigned[rowsLengthMatrice-2][colsLengthMatrice-1] = 1;
        }
        
        // Put the codewords into the matrix
        this.next(0,rowsLengthMatrice,colsLengthMatrice, codeWordsBits, datamatrix, assigned);
        
        // Add the finder pattern
        datamatrix = this.addFinderPattern(datamatrix, rowsRegion, colsRegion, rowsRegionCW, colsRegionCW);
        
        return datamatrix;
      }
    },
    // little endian convertor
    lec:{
      // convert an int
      cInt: function(value, byteCount){
        var le = '';
        for(var i=0; i<byteCount; i++){
          le += String.fromCharCode(value & 0xFF);
          value = value >> 8;
        }
        return le;
      },
      // return a byte string from rgb values 
      cRgb: function(r,g,b){
        return String.fromCharCode(b) + String.fromCharCode(g) + String.fromCharCode(r);
      },
      // return a byte string from a hex string color
      cHexColor: function(hex){
        var v = parseInt('0x' + hex.substr(1));
        var b = v & 0xFF;
        v = v >> 8;
        var g = v & 0xFF;
        var r = v >> 8;
        return(this.cRgb(r,g,b));
      }
    },
    hexToRGB: function(hex){
      var v = parseInt('0x' + hex.substr(1));
      var b = v & 0xFF;
      v = v >> 8;
      var g = v & 0xFF;
      var r = v >> 8;
      return({r:r,g:g,b:b});
    },
    // test if a string is a hexa string color (like #FF0000)
    isHexColor: function (value){
      var r = new RegExp("#[0-91-F]", "gi");
      return  value.match(r);
    },
    // encode data in base64
    base64Encode: function(value) {
      var r = '', c1, c2, c3, b1, b2, b3, b4;
      var k = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
      var i = 0;
      while (i < value.length) {
        c1 = value.charCodeAt(i++);
        c2 = value.charCodeAt(i++);
        c3 = value.charCodeAt(i++);
        b1 = c1 >> 2;
        b2 = ((c1 & 3) << 4) | (c2 >> 4);
        b3 = ((c2 & 15) << 2) | (c3 >> 6);
        b4 = c3 & 63;
        if (isNaN(c2)) b3 = b4 = 64;
        else if (isNaN(c3)) b4 = 64;
        r += k.charAt(b1) + k.charAt(b2) + k.charAt(b3) + k.charAt(b4);
      }
      return r;
    },
    // convert a bit string to an array of array of bit char
    bitStringTo2DArray: function( digit ){
      var d = []; d[0] = [];
      for(var i=0; i<digit.length; i++) d[0][i] = digit.charAt(i);
      return(d);
    },
    // clear jQuery Target
    resize: function($container, w){
      $container
        .css("padding", "0px")
        .css("overflow", "auto")
        .css("width", w + "px")
        .html("");
        return $container;
    },
    // bmp barcode renderer
    digitToBmpRenderer: function($container, settings, digit, hri, mw, mh){
      var lines = digit.length;
      var columns = digit[0].length;
      var i = 0;
      var c0 = this.isHexColor(settings.bgColor) ? this.lec.cHexColor(settings.bgColor) : this.lec.cRgb(255,255,255);
      var c1 = this.isHexColor(settings.color) ? this.lec.cHexColor(settings.color) : this.lec.cRgb(0,0,0);
      var bar0 = '';
      var bar1 = '';
        
      // create one bar 0 and 1 of "mw" byte length 
      for(i=0; i<mw; i++){
        bar0 += c0;
        bar1 += c1;
      }
      var bars = '';
    
      var padding = (4 - ((mw * columns * 3) % 4)) % 4; // Padding for 4 byte alignment ("* 3" come from "3 byte to color R, G and B")
      var dataLen = (mw * columns + padding) * mh * lines;
    
      var pad = '';
      for(i=0; i<padding; i++) pad += '\0';
      
      // Bitmap header
      var bmp = 'BM' +                            // Magic Number
                this.lec.cInt(54 + dataLen, 4) +  // Size of Bitmap size (header size + data len)
                '\0\0\0\0' +                      // Unused
                this.lec.cInt(54, 4) +            // The offset where the bitmap data (pixels) can be found
                this.lec.cInt(40, 4) +            // The number of bytes in the header (from this point).
                this.lec.cInt(mw * columns, 4) +  // width
                this.lec.cInt(mh * lines, 4) +    // height
                this.lec.cInt(1, 2) +             // Number of color planes being used
                this.lec.cInt(24, 2) +            // The number of bits/pixel
                '\0\0\0\0' +                      // BI_RGB, No compression used
                this.lec.cInt(dataLen, 4) +       // The size of the raw BMP data (after this header)
                this.lec.cInt(2835, 4) +          // The horizontal resolution of the image (pixels/meter)
                this.lec.cInt(2835, 4) +          // The vertical resolution of the image (pixels/meter)
                this.lec.cInt(0, 4) +             // Number of colors in the palette
                this.lec.cInt(0, 4);              // Means all colors are important
      // Bitmap Data
      for(var y=lines-1; y>=0; y--){
        var line = '';
        for (var x=0; x<columns; x++){
          line += digit[y][x] == '0' ? bar0 : bar1;
        }
        line += pad;
        for(var k=0; k<mh; k++){
          bmp += line;
        }
      }
      // set bmp image to the container
      var object = document.createElement('object');
      object.setAttribute('type', 'image/bmp');
      object.setAttribute('data', 'data:image/bmp;base64,'+ this.base64Encode(bmp));
      this.resize($container, mw * columns + padding).append(object);
                      
    },
    // bmp 1D barcode renderer
    digitToBmp: function($container, settings, digit, hri){
      var w = barcode.intval(settings.barWidth);
      var h = barcode.intval(settings.barHeight);
      this.digitToBmpRenderer($container, settings, this.bitStringTo2DArray(digit), hri, w, h);
    },
    // bmp 2D barcode renderer
    digitToBmp2D: function($container, settings, digit, hri){
      var s = barcode.intval(settings.moduleSize);
      this.digitToBmpRenderer($container, settings, digit, hri, s, s);
    },
    // css barcode renderer
    digitToCssRenderer : function($container, settings, digit, hri, mw, mh){
      var lines = digit.length;
      var columns = digit[0].length;
      var content = "";
      var bar0 = "<div style=\"float: left; font-size: 0px; background-color: " + settings.bgColor + "; height: " + mh + "px; width: &Wpx\"></div>";    
      var bar1 = "<div style=\"float: left; font-size: 0px; width:0; border-left: &Wpx solid " + settings.color + "; height: " + mh + "px;\"></div>";
  
      var len, current;
      for(var y=0; y<lines; y++){
        len = 0;
        current = digit[y][0];
        for (var x=0; x<columns; x++){
          if ( current == digit[y][x] ) {
            len++;
          } else {
            content += (current == '0' ? bar0 : bar1).replace("&W", len * mw);
            current = digit[y][x];
            len=1;
          }
        }
        if (len > 0){
          content += (current == '0' ? bar0 : bar1).replace("&W", len * mw);
        }
      }  
      if (settings.showHRI){
        content += "<div style=\"clear:both; width: 100%; background-color: " + settings.bgColor + "; color: " + settings.color + "; text-align: center; font-size: " + settings.fontSize + "px; margin-top: " + settings.marginHRI + "px;\">"+hri+"</div>";
      }
      this.resize($container, mw * columns).html(content);
    },
    // css 1D barcode renderer  
    digitToCss: function($container, settings, digit, hri){
      var w = barcode.intval(settings.barWidth);
      var h = barcode.intval(settings.barHeight);
      this.digitToCssRenderer($container, settings, this.bitStringTo2DArray(digit), hri, w, h);
    },
    // css 2D barcode renderer
    digitToCss2D: function($container, settings, digit, hri){
      var s = barcode.intval(settings.moduleSize);
      this.digitToCssRenderer($container, settings, digit, hri, s, s);
    },
    // svg barcode renderer
    digitToSvgRenderer: function($container, settings, digit, hri, mw, mh){
      var lines = digit.length;
      var columns = digit[0].length;
      
      var width = mw * columns;
      var height = mh * lines;
      if (settings.showHRI){
        var fontSize = barcode.intval(settings.fontSize);
        height += barcode.intval(settings.marginHRI) + fontSize;
      }
      
      // svg header
      var svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' + width + '" height="' + height + '">';
      
      // background
      svg += '<rect width="' +  width + '" height="' + height + '" x="0" y="0" fill="' + settings.bgColor + '" />';
      
      var bar1 = '<rect width="&W" height="' + mh + '" x="&X" y="&Y" fill="' + settings.color + '" />';
      
      var len, current;
      for(var y=0; y<lines; y++){
        len = 0;
        current = digit[y][0];
        for (var x=0; x<columns; x++){
          if ( current == digit[y][x] ) {
            len++;
          } else {
            if (current == '1') {
              svg += bar1.replace("&W", len * mw).replace("&X", (x - len) * mw).replace("&Y", y * mh);
            }
            current = digit[y][x];
            len=1;
          }
        }
        if ( (len > 0) && (current == '1') ){
          svg += bar1.replace("&W", len * mw).replace("&X", (columns - len) * mw).replace("&Y", y * mh);
        }
      }
      
      if (settings.showHRI){
        svg += '<g transform="translate(' + Math.floor(width/2) + ' 0)">';
        svg += '<text y="' + (height - Math.floor(fontSize/2)) + '" text-anchor="middle" style="font-family: Arial; font-size: ' + fontSize + 'px;" fill="' + settings.color + '">' + hri + '</text>';
        svg += '</g>';
      }
      // svg footer
      svg += '</svg>';
      
      // create a dom object, flush container and add object to the container
      var object = document.createElement('object');
      object.setAttribute('type', 'image/svg+xml');
      object.setAttribute('data', 'data:image/svg+xml,'+ svg);
      this.resize($container, width).append(object);
    },
    // svg 1D barcode renderer
    digitToSvg: function($container, settings, digit, hri){
      var w = barcode.intval(settings.barWidth);
      var h = barcode.intval(settings.barHeight);
      this.digitToSvgRenderer($container, settings, this.bitStringTo2DArray(digit), hri, w, h);
    },
    // svg 2D barcode renderer
    digitToSvg2D: function($container, settings, digit, hri){
      var s = barcode.intval(settings.moduleSize);
      this.digitToSvgRenderer($container, settings, digit, hri, s, s);
    },
    
    // canvas barcode renderer
    digitToCanvasRenderer : function($container, settings, digit, hri, xi, yi, mw, mh){
      var canvas = $container.get(0);
      if ( !canvas || !canvas.getContext ) return; // not compatible
      
      var lines = digit.length;
      var columns = digit[0].length;
      
      var ctx = canvas.getContext('2d');
      ctx.lineWidth = 1;
      ctx.lineCap = 'butt';
      ctx.fillStyle = settings.bgColor;
      ctx.fillRect (xi, yi, columns * mw, lines * mh);
      
      ctx.fillStyle = settings.color;
      
      for(var y=0; y<lines; y++){
        var len = 0;
        var current = digit[y][0];
        for(var x=0; x<columns; x++){
          if (current == digit[y][x]) {
            len++;
          } else {
            if (current == '1'){
              ctx.fillRect (xi + (x - len) * mw, yi + y * mh, mw * len, mh);
            }
            current = digit[y][x];
            len=1;
          }
        }
        if ( (len > 0) && (current == '1') ){
          ctx.fillRect (xi + (columns - len) * mw, yi + y * mh, mw * len, mh);
        }
      }
      if (settings.showHRI){
        var dim = ctx.measureText(hri);
        ctx.fillText(hri, xi + Math.floor((columns * mw - dim.width)/2), yi + lines * mh + settings.fontSize + settings.marginHRI);
      }
    },
    // canvas 1D barcode renderer
    digitToCanvas: function($container, settings, digit, hri){
      var w  = barcode.intval(settings.barWidth);
      var h = barcode.intval(settings.barHeight);
      var x = barcode.intval(settings.posX);
      var y = barcode.intval(settings.posY);
      this.digitToCanvasRenderer($container, settings, this.bitStringTo2DArray(digit), hri, x, y, w, h);
    },
    // canvas 2D barcode renderer
    digitToCanvas2D: function($container, settings, digit, hri){
      var s = barcode.intval(settings.moduleSize);
      var x = barcode.intval(settings.posX);
      var y = barcode.intval(settings.posY);
      this.digitToCanvasRenderer($container, settings, digit, hri, x, y, s, s);
    }
  };
  
  $.fn.extend({
    barcode: function(datas, settings) {
      var digit = "",
          hri   = "",
          code  = "",
          crc   = true,
          rect  = false,
          b2d   = false;
      
      if (typeof(datas) == "string"){
        code = datas;
      } else if (typeof(datas) == "object"){
        code = typeof(datas.code) == "string" ? datas.code : "";
        crc = typeof(datas.crc) != "undefined" ? datas.crc : true;
        rect = typeof(datas.rect) != "undefined" ? datas.rect : false;
      }
      if (code == "") return(false);
      
      if (typeof(settings) == "undefined") settings = [];
      for(var name in barcode.settings){
        if (settings[name] == undefined) settings[name] = barcode.settings[name];
      }
      

          digit = barcode.datamatrix.getDigit(code, rect);
          hri = code;
          b2d = true;

      if (digit.length == 0) return($(this));
      
      // Quiet Zone
      if ( !b2d && settings.addQuietZone) digit = "0000000000" + digit + "0000000000";
      
      var $this = $(this);
      var fname = 'digitTo' + settings.output.charAt(0).toUpperCase() + settings.output.substr(1) + (b2d ? '2D' : '');
      if (typeof(barcode[fname]) == 'function') {
        barcode[fname]($this, settings, digit, hri);
      }
      
      return($this);
    }
  });

}(jQuery));