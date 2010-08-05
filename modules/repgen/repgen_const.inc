<?php
/* Include Datei repgen_const.inc for PHP Report Generator
   Bauer, 22.1.2002
   Version 0.2
*/

/*   REPGEN constants. For other language copy the file <language>.inc over repgen_const.inc
*/
// used in repgen_main
define("CREATE","Create a Report"); // button create
define("SELECT","Select a report for work"); // button select
define("FIRST","we need some data, please insert:");

// used in repgen_select
define ("CHANGE" , "Edit");  // Constant for change button value
define ("DELETE" , "Delete"); // Constant for delete Butteon value
define ("COPY" , "Copy"); // Constant for copy Button value
define ("COPYV" , "Cp of"); // Constant for copy-text
define ("SEL_SELECT","If you want to change or delete a stored report, then
choose beneath.");
define ("SEL_COLOR","(Green are the Blocks, orange the Functions and gray the reports shown.)");
                                           // table heads
define ("SHORT" , "Shortname");      // Head of 1. col
define ("LONG" , "Long name");       // Head of 2. col
define ("AUTHOR" , "Author");               // Head od 3. col
define ("CREATIONDATE" , "Creation date");    // Head of 4. col
define ("DESCRIPT","Selection of the Reports");   // Head of page
define ("LOGOUT","Exit the program");  // Logout of program
define ("NEW_","Create a new report");  // Create new Report
define ("NEWBLOCK","Create a new Block");  // Create new Block
define ("NEWFUNCT","Create a new Function");  // Create new function

// used in repgen_create
define ("CREATE_BLOCK","Common Data of a Block"); // common data of block
define ("ALTER_BLOCK","Common Data of the Block "); // common data of block
define ("ID_BLOCK", "ID-Number of the Block");                  // Label of ID
define ("CREATE_FUNCT","Common Data of a Function "); // common data of function
define ("ALTER_FUNCT","Common Data of the Function "); // common data of function
define ("ID_FUNCT", "ID-Number of the Function");                  // Label of ID

define ("CREATE_HEAD","Common data of the report"); // common data of report
define ("ALTER_HEAD","Common data of the old report "); // common data of report
define ("ID" , "ID-Number of the report");                  // Label of ID
define ("DATE" , "Creation Date");                     // Label of DAte
define ("PRINT_FORMAT" , "Printer Orientation");        // Label of Printer
define ("REPORT_TYPE" , "Report type");              // Label of Report type
define ("SQL" , "SQL-Statement for record selection");         // Label of Paper-width
define ("GROUP_NAME" , "Name of the group field");         // Label of Group
define ("GROUP_TYPE" , "Group type");              // Label of Group type
define ("GROUP_ERROR" , "Error: Group type = 'New Page on group change' and no field!");  // Error message of missing Group field
define ("NO_PAGE", "No new page when group changes"); // Label of nopage-select
define ("NEW_PAGE", "New Page on group change"); // Label of newpage-select
define ("TEST_SEL" , "Test the SQL-Statement");         // Value of Test-SQL Statement-Button
define ("PAGE_REC", "Page per record");                  // Label of report_type single
define ("LINE_REC", "Line per record");                  // Label of report_type class
define ("GRID_REC", "Line per record with grid");                  // Label of report_type classtable
define ("BEAM_REC", "Line per record with bar");                  // Label of report_type classbeam
define ("BEAMGRID_REC", "Line per record with bar and grid");     // Label of report_type classgrid
define ("SELECT_CR", "Back to Report Selection(without storage)"); // Label of back-button
define ("PAGE_STORE" , "Store and back to Report Selection");         // Value of Store-Button
define ("PAGE_TEST" , "Test of the Function");         // Value of Test-Button
define ("PAGE_STRINGS" , "Page Definition for Strings");         // Value of button page_strings
define ("PAGE_GRAPHICS" , "Page Definition for Graphics");         // Value of button page_graphics
define ("PHP_ERROR", "PHP-Error in Function: ");            // Error message of PHP-Error
define ("PHP_OK", "PHP: Function is OK. The result is: ");           // Message 'ok' of function
define ("ERR_FIELD", "The field \$field is not part of the function.");           // Message missing $field
define ("A4FORMAT1", "Paper Format");              // Paperformat
define ("ID_ERROR", "ID-Number and SQL-statement must not be empty, or the short-name has been used before !!!!"); // Error Message: ID missing
define ("ID_ERROR_BLOCK", "The Short-Name of the Block  has been used before!!!!"); // Error Message: ID missing
define ("ERROR_FUNC", "The name of the Function must be equal to the short-name:"); // Error Message: Functionname <> shortname
define ("FUNC_DECL", "A Function has to be declared, which gives a String as <BR> as a result. The Function name has to be equal to the short-name!<BR>Please Test the function!<BR> If there is a fatal run-time error (e.g. redeclaration of a function) the system may die!!");
define ("NOTSTORED"," The values have NOT been stored!"); // Error -> not stored
define ("SQL_ERROR"," has an Error");  // SQL Error message
define ("SQL_ERROR1"," SQL Error: SQL Statement is empty!");  // SQL Error message
// used in repgen_test_sel
define ("SQL_STATEMENT","You have written this SQL-Statement: ");
define ("SQL_ERG","The first 10 records of the result set are: ");

// used in repgen_strings and repgen_graphics
define ("ITEM_DEF","Definition of a new Item of the report ");
define ("ITEM_CHAR","for Characters ");
define ("ITEM_LINE","for Graphics ");
define ("ALTERNATIVE", " Or as Alternative in Mode Detail ");
define ("ORDER", " Order");
define ("NUMBER","Number of Char");
define ("AND_", "AND");
define ("ALIGN", "Alignment");
define ("ELEMENT","Elementtype");
define ("VALUE_", "Value");
define ("WIDTH", "Width in points");
define ("DBFELD","DB-Field");
define ("ITEM_HEAD","Table of stored Items ");
define ("OPTIONAL","Optional");
define ("SUBSTRING","Substring");
define ("FROM","from");
define ("TO","to");
define ("TOTAL","Use only, if DB-Field is numeric");

       //        Headers of the table columns
define ("IT_TYP","Type ");
define ("IT_ART","Mode ");
define ("IT_FONT","Font ");
define ("IT_FONT_SIZE","FontSize ");
define ("IT_ORD","Order");
define ("IT_LEN","Number of Ch. ");
define ("IT_X1","X ");
define ("IT_X2","X2/Width ");
define ("IT_Y1","Y ");
define ("IT_Y2","Y2/Height ");
define ("IT_WIDTH","Line-width");
define ("IT_STRING","String/Fieldname ");
define ("IT_LINE","Line-width ");
define ("IT_STORE","                 Store Item                   ");
define ("IT_BACK"  ,"         Back to select a report             ");
define ("IT_PRINT", "Show the report");    // Button druck
define ("IT_HELP", "X1/Y1 and X2/Y2 are the ends of a line. <BR> X1/Y1 and width/height give the dimensions of a rectangle.");
define ("ERROR_EMPTY","Please insert a value in X or the Number of Ch. and Type !");
define ("ERROR_EMPTY_LINE","Insert please values for X1/Y1 and X2/Y2 and line-width!");
define ("ERROR_ORDER","A value for Order etc. must only be inserted, if Mode=Detail and no value for Y has been given!");
define ("ERROR_XY","Please insert a value for X and Y!");
define ("ERROR_MIX","In lines of mode 'Detail' the items with X/Y-values and Orders must not be mixed!");
define ("ERROR_VALUE","No Value for Item-type 'String' has been declared!");
define ("ERROR_TO","Error in Substring: To-Value is lower than From-Value!");
define ("ERROR_TOTAL","Error in Total: Total can only be used with mode 'DE'!");

define ("BGCOLOR1", "#CCCCCC");   // Background1 of tables
define ("BGCOLOR2", "#DDDDDD");   // Background2 of tables
define ("BGCOLORH", "#D3DCE3");   // Background of Header of tables

// used in repgen_del
define ("BACK" , "Back to report selection(without deletion)");  // Constant for back button value
define ("DEL_BACK" , "D E L E T E"); // Constant for delete Butteon value
define("DEL_REALLY","Do you really want to delete ");  
define("DEL_DELETE","?");
define("DEL_REPORT","the report ");
define("DEL_BLOCK","the block ");
define("DEL_FUNC","the function ");

?>