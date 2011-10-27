<?php
	/* Include Datei repgen_const.inc for PHP Report Generator
		Bauer, 22.1.2002
		Version 0.2
		Changed 19.11.2002 Version 0.44
 */

	/*   REPGEN constants. For other language copy the file language.inc over repgen_const.inc
 */
	// used in repgen_main. Next line
	// used in repgen_main
	//////////////////////////////////////////////////////////////////////////
	define("REPGENDIR", "/repgen/"); // Directory of Repgen, shoud be altered if you use another directory
	//////////////////////////////////////////////////////////////////////////
	define("CREATE", "       Erzeuge neue Liste           "); // button create
	define("SELECT", "Wähle eine Liste zur Bearbeitung aus"); // button select
	define("FIRST", "wir benötigen noch einige Daten, bitte geben Sie diese an:");

	// used in repgen_select
	define ("CHANGE", "Ändern"); // Constant for change button value
	define ("DELETE", "Löschen"); // Constant for delete Button value
	define ("COPY", "Kopieren"); // Constant for copy Button value
	define ("COPYV", "Kp von"); // Constant for copy-text
	define ("SEL_SELECT", "Wenn sie eine gespeicherte Liste bearbeiten oder löschen wollen, dann wählen sie bitte unten aus.");
	define ("SEL_COLOR", "(Grün werden die Blöcke, orange die Funktionen und grau die Listen angezeigt.)");
	// table heads
	define ("SHORT", "Kurzbezeichnung"); // Head of 1. col
	define ("LONG", "Langbezeichnung"); // Head of 2. col
	define ("AUTHOR", "Autor"); // Head od 3. col
	define ("CREATIONDATE", "Datum der letzten Bearbeitung"); // Head of 4. col
	define ("DESCRIPT", "Auswahl der Listen"); // Head of page
	define ("LOGOUT", "Beenden des Programms"); // Logout of program
	define ("NEW_", "Erzeugen einer neuen Liste"); // Create new Report
	define ("NEWBLOCK", "Erzeugen eines neuen Blocks"); // Create new Block
	define ("NEWFUNCT", "Erzeugen einer neuen Funktion"); // Create new function
	// used in repgen_create
	define ("CREATE_BLOCK", "Allgemeine Daten für einen Block"); // common data of block
	define ("ALTER_BLOCK", "Allgemeine Daten für den Block "); // common data of block
	define ("ID_BLOCK", "ID-Nummer des Blocks"); // Label of ID
	define ("CREATE_FUNCT", "Allgemeine Daten für eine Funktion"); // common data of funct
	define ("ALTER_BLOCK", "Allgemeine Daten für die Funktion "); // common data of funct
	define ("ID_BLOCK", "ID-Nummer der Funktion"); // Label of ID

	define ("CREATE_HEAD", "Allgemeine Daten für eine Liste"); // common data of report
	define ("ALTER_HEAD", "Allgemeine Daten für die Liste "); // common data of report
	define ("ID", "ID-Nummer der Liste"); // Label of ID

	define ("DATE", "Creation Date"); // Label of DAte
	define ("FUNKTION", "Funktion"); // Label of Function
	define ("PRINT_FORMAT", "Format des Druckers"); // Label of Printer
	define ("REPORT_TYPE", "Typ der Liste"); // Label of Report type
	define ("SQL", "SQL-Statement zur Satzauswahl"); // Label of Paper-width
	define ("GROUP_NAME", "Feldname des Feldes für Gruppenwechsel"); // Label of Group
	define ("GROUP_TYPE", "Typ der Gruppe"); // Label of Group type
	define ("GROUP_ERROR", "Fehler: Gruppentyp = 'Neue Seite bei Gruppenwechsel' und kein Gruppenfeld angegeben!"); // Error message of missing Group field
	define ("NO_PAGE", "Keine neue Seite bei Gruppenwechsel"); // Label of nopage-select
	define ("NEW_PAGE", "Neue Seite bei Gruppenwechsel"); // Label of newpage-select
	define ("TEST_SEL", "Testen des SQL-Statements"); // Value of Test-SQL Statement-Button
	define ("PAGE_REC", "Seite pro Satz"); // Label of report_type single
	define ("LINE_REC", "Zeile pro Satz"); // Label of report_type class
	define ("GRID_REC", "Zeile pro Satz mit Rahmen"); // Label of report_type classtable
	define ("BEAM_REC", "Zeile pro Satz mit Balken"); // Label of report_type classbeam
	define ("BEAMGRID_REC", "Zeile pro Satz mit Balken und Rahmen"); // Label of report_type classgrid
	define ("SELECT_CR", "Zurück zur Listenauswahl ohne speichern"); // Value of Select-Button
	define ("PAGE_STORE", "Speichern und zurück zur Listenauswahl"); // Value of Store-Button
	define ("PAGE_TEST", "Testen der Funktion"); // Value of Test-Button
	define ("PAGE_STRINGS", "Weiter zur Seitendefinition String"); // Value of button page_strings
	define ("PAGE_GRAPHICS", "Weiter zur Seitendefinition Graphic"); // Value of button page_graphics
	define ("PHP_ERROR", "PHP-Fehler in Funktion: "); // Error message of PHP-Error
	define ("PHP_OK", "PHP: Funktion ist fehlerfrei. Das Ergebnis lautet: "); // Message 'ok' of function
	define ("ERR_FIELD", "Das Feld \$field kommt in der Funktion nicht vor."); // Message missing $field
	define ("A4FORMAT1", "Papiergröße bestimmen"); // Paperformat
	define ("ID_ERROR", "Die Listen-Kurzbezeichnung und die SQL-Select Anweisung dürfen nicht leer sein oder die Kurzbezeichnung wird schon verwendet!!!!"); // Error Message: ID missing
	define ("ID_ERROR_BLOCK", "Die Block-Kurzbezeichnung schon verwendet!!!!"); // Error Message: ID missing
	define ("ERROR_FUNC", "Der Funktionsname muß gleich dem Kurznamen sein:"); // Error Message: Functionname <> shortname
	define ("FUNC_DECL", "Es muß eine Funktion angegeben werden, welche einen String als<BR>Ergebnis liefert. Der Funktionsname muß dem Kurznamen gleichen!<BR>Bitte testen Sie die Funktion!");
	define ("NOTSTORED", " Die Angaben wurden NICHT gespeichert!"); // Error -> not stored
	define ("SQL_ERROR", " ist fehlerhaft"); // SQL Error message
	define ("SQL_ERROR1", " SQL Fehler: Der Befehl ist leer!"); // SQL Error message
	// used in repgen_test_sel
	define ("SQL_STATEMENT", "Sie haben das folgende SQL-Statement eingegeben: ");
	define ("SQL_ERG", "Die ersten 10 Ergebnis-Sätze sind: ");

	// used in repgen_strings and repgen_graphics
	define ("ITEM_DEF", "Definition eines neuen Items der Liste ");
	define ("ITEM_CHAR", "für Zeichen ");
	define ("ITEM_LINE", "für Graphic ");
	define ("ALTERNATIVE", " oder alternativ ");
	define ("ORDER", "Reihenfolge");
	define ("NUMBER", "Zeichenzahl");
	define ("AND_", "und");
	define ("ALIGN", "Zentrierung");
	define ("ELEMENT", "Elementtyp");
	define ("VALUE_", "Wert");
	define ("WIDTH", "Breite in points");
	define ("DBFELD", "DB-Feld");
	define ("ITEM_HEAD", "Tabelle der schon gespeicherten Items ");
	define ("OPTIONAL", "Optional");
	define ("SUBSTRING", "Teilstring");
	define ("FROM", "von");
	define ("TO", "bis");
	define ("TOTAL", "Nur benutzen,wenn Feld numerisch ist");
	//        Headers of the table columns
	define ("IT_TYP", "Typ ");
	define ("IT_ART", "Art ");
	define ("IT_FONT", "Font ");
	define ("IT_FONT_SIZE", "FontSize ");
	define ("IT_LEN", "Zeichenzahl ");
	define ("IT_ORD", "Reihenfolge");
	define ("IT_X1", "X ");
	define ("IT_X2", "X2/Breite ");
	define ("IT_Y1", "Y ");
	define ("IT_Y2", "Y2/Höhe ");
	define ("IT_WIDTH", "Strichdicke");
	define ("IT_STRING", "String/Feldname ");
	define ("IT_LINE", "Liniendicke ");
	define ("IT_STORE", "                 Item speichern                   ");
	define ("IT_BACK", "         Zurück zur Listenauswahl           ");
	define ("IT_PRINT", "Anzeigen des Drucks"); // Button druck
	define ("IT_HELP", "X1/Y1 und X2/Y2 bilden die Enden einer Linie. <BR> X1/Y1 und Breite/Höhe sind die Abmessungen eines Rechtecks.");
	define ("ERROR_EMPTY", "Geben Sie bitte einen Wert in X oder Zeichenzahl und Elementtyp ein!");
	define ("ERROR_EMPTY_LINE", "Geben Sie bitte Werte für X1/Y1 und X2/Y2 und Strichdicke an!");
	define ("ERROR_ORDER", "Reihenfolge etc. darf nur bei Art = Detail und keinem Y-Wert eingegeben werden!");
	define ("ERROR_XY", "Geben Sie bitte für X und Y einen Wert ein!");
	define ("ERROR_MIX", "In Zeilen des Abschnitts Detail können Elemente mit X/Y - Werten und Reihenfolgenummern nicht gemischt werden!");
	define ("ERROR_VALUE", "Kein Wert für Elementtyp 'String' angegeben!");
	define ("ERROR_TO", "Fehler in Substring: Wert von Bis ist kleiner als Wert von Von:!");
	define ("ERROR_TOTAL", "Fehler bei Total: Total darf nur bei der Art 'DE' verwendet werden");

	define ("BGCOLOR1", "#CCCCCC"); // Background1 of tables
	define ("BGCOLOR2", "#DDDDDD"); // Background2 of tables
	define ("BGCOLORH", "#D3DCE3"); // Background of Header of tables

	// used in repgen_del
	define ("BACK", "Zurück zur Listenauswahl(ohne Löschen)"); // Constant for back button value
	define ("DEL_BACK", "            L Ö S C H E N           "); // Constant for delete Butteon value
	define("DEL_REALLY", "Wollen Sie wirklich ");
	define("DEL_DELETE", "löschen?");
	define("DEL_REPORT", "die Liste ");
	define("DEL_BLOCK", "den Block ");
	define("DEL_FUNC", "die Funktion ");

?>
