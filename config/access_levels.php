<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	/*
	 Security sections groups various areas on both functionality and privilege levels.
	 Often analytic inquires are available only for management, and configuration
	 for administration or management staff. This is why we have those three
	 section type inside near every ADV module.

	 Section codes 0-99 are reserved for core ADV functionalities.
	 Every security section can contain up to 256 different areas.
	 External modules can extend security roles system by adding rows to
	 $security_sections and $security_areas using section codes >=100.
	 Security areas and sections created by extension modules/plugins
	 have dynamically assigned 3-byte integer codes. The highest byte is zero
	 for sections/areas defined in this file, and extid+1 for those defined
	 by extensions
 */
	global $security_sections, $security_areas;
	$security_sections = array(
		SS_SADMIN => "System administration",
		SS_SETUP => "Company setup",
		SS_SPEC => "Special maintenance",
		SS_SALES_C => "Sales configuration",
		SS_SALES => "Sales transactions",
		SS_SALES_A => "Sales related reports",
		SS_PURCH_C => "Purchase configuration",
		SS_PURCH => "Purchase transactions",
		SS_PURCH_A => "Purchase analytics",
		SS_ITEMS_C => "Inventory configuration",
		SS_ITEMS => "Inventory operations",
		SS_ITEMS_A => "Inventory analytics",
		SS_MANUF_C => "Manufacturing configuration",
		SS_MANUF => "Manufacturing transations",
		SS_MANUF_A => "Manufacturing analytics",
		SS_DIM_C => "Dimensions configuration",
		SS_DIM => "Dimensions",
		SS_GL_C => "Banking & GL configuration",
		SS_GL => "Banking & GL transactions",
		SS_GL_A => "Banking & GL analytics",
		SS_ADV => "Advanced"
	);
	/*
		 This table stores security areas available in ADV.
		 Key is area identifier used to check user rights, values are
		 codes stored for each role in security_roles table and description used
		 in roles editor.

		 Set of allowed access areas codes is retrieved during user login from
		 security_roles table, and cached in user profile.

		 Special value SA_OPEN is used for publicly available pages like login/logout.
	 */
	$security_areas = array(
//
//	Advanced
//
		SA_ADVANCED => array(SS_ADV | 1, "Advanced"),
//
//	Site administration
//
		SA_CREATECOMPANY => array(SS_SADMIN | 1, "Install/update companies"),
		SA_CREATELANGUAGE => array(SS_SADMIN | 2, "Install/update languages"),
		SA_CREATEMODULES => array(SS_SADMIN | 3, "Install/upgrade modules"),
		SA_SOFTWAREUPGRADE => array(SS_SADMIN | 4, "Software upgrades"),
//
//	Company setup
//
		SA_SETUPCOMPANY => array(SS_SETUP | 1, "Company parameters"),
		SA_SECROLES => array(SS_SETUP | 2, "Access levels edition"),
		SA_USERS => array(SS_SETUP | 3, "Users setup"),
		SA_POSSETUP => array(SS_SETUP | 4, "Point of sales definitions"),
		SA_PRINTERS => array(SS_SETUP | 5, "Printers configuration"),
		SA_PRINTPROFILE => array(SS_SETUP | 6, "Print profiles"),
		SA_PAYTERMS => array(SS_SETUP | 7, "Payment terms"),
		SA_SHIPPING => array(SS_SETUP | 8, "Shipping ways"),
		SA_CRSTATUS => array(SS_SETUP | 9, "Credit status definitions changes"),
		SA_INVENTORYLOCATION => array(SS_SETUP | 10, "Inventory locations changes"),
		SA_INVENTORYMOVETYPE => array(SS_SETUP | 11, "Inventory movement types"),
		SA_WORKCENTRES => array(SS_SETUP | 12, "Manufacture work centres"),
		SA_FORMSETUP => array(SS_SETUP | 13, "Forms setup"),
//
// Special and common functions
//
		SA_VOIDTRANSACTION => array(SS_SPEC | 1, "Voiding transactions"),
		SA_BACKUP => array(SS_SPEC | 2, "Database backup/restore"),
		SA_VIEWPRINTTRANSACTION => array(SS_SPEC | 3, "Common view/print transactions interface"),
		SA_ATTACHDOCUMENT => array(SS_SPEC | 4, "Attaching documents"),
		SA_SETUPDISPLAY => array(SS_SPEC | 5, "Display preferences"), //???
		SA_CHGPASSWD => array(SS_SPEC | 6, "Password changes"), //???
//
// Sales related functionality
//
		SA_SALESTYPES => array(SS_SALES_C | 1, "Sales types"),
		SA_SALESPRICE => array(SS_SALES_C | 2, "Sales prices edition"),
		SA_SALESMAN => array(SS_SALES_C | 3, "Sales staff maintenance"),
		SA_SALESAREA => array(SS_SALES_C | 4, "Sales areas maintenance"),
		SA_SALESGROUP => array(SS_SALES_C | 5, "Sales groups changes"),
		SA_STEMPLATE => array(SS_SALES_C | 6, "Sales templates"),
		SA_SRECURRENT => array(SS_SALES_C | 7, "Recurrent invoices definitions"),
		SA_SALESTRANSVIEW => array(SS_SALES | 1, "Sales transactions view"),
		SA_CUSTOMER => array(SS_SALES | 2, "Sales customer and branches changes"),
		SA_CUSTOMER_CREDIT => array(SS_SALES | 12, "Sales customer credit changes"),
		SA_SALESQUOTE => array(SS_SALES | 10, "Sales quotations"),
		SA_SALESORDER => array(SS_SALES | 3, "Sales orders edition"),
		SA_SALESDELIVERY => array(SS_SALES | 4, "Sales deliveries edition"),
		SA_SALESINVOICE => array(SS_SALES | 5, "Sales invoices edition"),
		SA_SALESCREDITINV => array(SS_SALES | 6, "Sales credit notes against invoice"),
		SA_SALESCREDIT => array(SS_SALES | 7, "Sales freehand credit notes"),
		SA_SALESPAYMNT => array(SS_SALES | 8, "Customer payments entry"),
		SA_SALESREFUND => array(SS_SALES | 11, "Customer refund entry"),
		SA_SALESALLOC => array(SS_SALES | 9, "Customer payments allocation"),
		SA_SALESANALYTIC => array(SS_SALES_A | 1, "Sales analytical reports"),
		SA_SALESBULKREP => array(SS_SALES_A | 2, "Sales document bulk reports"),
		SA_PRICEREP => array(SS_SALES_A | 3, "Sales prices listing"),
		SA_SALESMANREP => array(SS_SALES_A | 4, "Sales staff listing"),
		SA_CUSTBULKREP => array(SS_SALES_A | 5, "Customer bulk listing"),
		SA_CUSTSTATREP => array(SS_SALES_A | 6, "Customer status report"),
		SA_CUSTPAYMREP => array(SS_SALES_A | 7, "Customer payments report"),
		SA_CUSTREFUNDREP => array(SS_SALES_A | 8, "Customer refund report"),
//
// Purchase related functions
//
		SA_PURCHASEPRICING => array(SS_PURCH_C | 1, "Purchase price changes"),
		SA_SUPPTRANSVIEW => array(SS_PURCH | 1, "Supplier transactions view"),
		SA_SUPPLIER => array(SS_PURCH | 2, "Suppliers changes"),
		SA_PURCHASEORDER => array(SS_PURCH | 3, "Purchase order entry"),
		SA_GRN => array(SS_PURCH | 4, "Purchase receive"),
		SA_SUPPLIERINVOICE => array(SS_PURCH | 5, "Supplier invoices"),
		SA_GRNDELETE => array(SS_PURCH | 9, "Deleting GRN items during invoice entry"),
		SA_SUPPLIERCREDIT => array(SS_PURCH | 6, "Supplier credit notes"),
		SA_SUPPLIERPAYMNT => array(SS_PURCH | 7, "Supplier payments"),
		SA_SUPPLIERALLOC => array(SS_PURCH | 8, "Supplier payments allocations"),
		SA_SUPPLIERANALYTIC => array(SS_PURCH_A | 1, "Supplier analytical reports"),
		SA_SUPPBULKREP => array(SS_PURCH_A | 2, "Supplier document bulk reports"),
		SA_SUPPPAYMREP => array(SS_PURCH_A | 3, "Supplier payments report"),
//
// Inventory 
//
		SA_ITEM => array(SS_ITEMS_C | 1, "Stock items add/edit"),
		SA_SALESKIT => array(SS_ITEMS_C | 2, "Sales kits"),
		SA_ITEMCATEGORY => array(SS_ITEMS_C | 3, "Item categories"),
		SA_UOM => array(SS_ITEMS_C | 4, "Units of measure"),
		SA_ITEMSSTATVIEW => array(SS_ITEMS | 1, "Stock status view"),
		SA_ITEMSTRANSVIEW => array(SS_ITEMS | 2, "Stock transactions view"),
		SA_FORITEMCODE => array(SS_ITEMS | 3, "Foreign item codes entry"),
		SA_LOCATIONTRANSFER => array(SS_ITEMS | 4, "Inventory location transfers"),
		SA_INVENTORYADJUSTMENT => array(SS_ITEMS | 5, "Inventory adjustments"),
		SA_REORDER => array(SS_ITEMS_A | 1, "Reorder levels"),
		SA_ITEMSANALYTIC => array(SS_ITEMS_A | 2, "Items analytical reports and inquiries"),
		SA_ITEMSVALREP => array(SS_ITEMS_A | 3, "Inventory valuation report"),
//
// Manufacturing module 
//
		SA_BOM => array(SS_MANUF_C | 1, "Bill of Materials"),
		SA_MANUFTRANSVIEW => array(SS_MANUF | 1, "Manufacturing operations view"),
		SA_WORKORDERENTRY => array(SS_MANUF | 2, "Work order entry"),
		SA_MANUFISSUE => array(SS_MANUF | 3, "Material issues entry"),
		SA_MANUFRECEIVE => array(SS_MANUF | 4, "Final product receive"),
		SA_MANUFRELEASE => array(SS_MANUF | 5, "Work order releases"),
		SA_WORKORDERANALYTIC => array(SS_MANUF_A | 1, "Work order analytical reports and inquiries"),
		SA_WORKORDERCOST => array(SS_MANUF_A | 2, "Manufacturing cost inquiry"),
		SA_MANUFBULKREP => array(SS_MANUF_A | 3, "Work order bulk reports"),
		SA_BOMREP => array(SS_MANUF_A | 4, "Bill of materials reports"),
//
// Dimensions
//
		SA_DIMTAGS => array(SS_DIM_C | 1, "Dimension tags"),
		SA_DIMTRANSVIEW => array(SS_DIM | 1, "Dimension view"),
		SA_DIMENSION => array(SS_DIM | 2, "Dimension entry"),
		SA_DIMENSIONREP => array(SS_DIM | 3, "Dimension reports"),
//
// Banking and General Ledger
//
		SA_ITEMTAXTYPE => array(SS_GL_C | 1, "Item tax type definitions"),
		SA_GLACCOUNT => array(SS_GL_C | 2, "GL accounts edition"),
		SA_GLACCOUNTGROUP => array(SS_GL_C | 3, "GL account groups"),
		SA_GLACCOUNTCLASS => array(SS_GL_C | 4, "GL account classes"),
		SA_QUICKENTRY => array(SS_GL_C | 5, "Quick GL entry definitions"),
		SA_CURRENCY => array(SS_GL_C | 6, "Currencies"),
		SA_BANKACCOUNT => array(SS_GL_C | 7, "Bank accounts"),
		SA_TAXRATES => array(SS_GL_C | 8, "Tax rates"),
		SA_TAXGROUPS => array(SS_GL_C | 12, "Tax groups"),
		SA_FISCALYEARS => array(SS_GL_C | 9, "Fiscal years maintenance"),
		SA_GLSETUP => array(SS_GL_C | 10, "Company GL setup"),
		SA_GLACCOUNTTAGS => array(SS_GL_C | 11, "GL Account tags"),
		SA_BANKTRANSVIEW => array(SS_GL | 1, "Bank transactions view"),
		SA_GLTRANSVIEW => array(SS_GL | 2, "GL postings view"),
		SA_EXCHANGERATE => array(SS_GL | 3, "Exchange rate table changes"),
		SA_PAYMENT => array(SS_GL | 4, "Bank payments"),
		SA_DEPOSIT => array(SS_GL | 5, "Bank deposits"),
		SA_BANKTRANSFER => array(SS_GL | 6, "Bank account transfers"),
		SA_RECONCILE => array(SS_GL | 7, "Bank reconciliation"),
		SA_JOURNALENTRY => array(SS_GL | 8, "Manual journal entries"),
		SA_BANKJOURNAL => array(SS_GL | 11, "Journal entries to bank related accounts"),
		SA_BUDGETENTRY => array(SS_GL | 9, "Budget edition"),
		SA_STANDARDCOST => array(SS_GL | 10, "Item standard costs"),
		SA_GLANALYTIC => array(SS_GL_A | 1, "GL analytical reports and inquiries"),
		SA_TAXREP => array(SS_GL_A | 2, "Tax reports and inquiries"),
		SA_BANKREP => array(SS_GL_A | 3, "Bank reports and inquiries"),
		SA_GLREP => array(SS_GL_A | 4, "GL reports and inquiries"),
	);

/*
	 This function should be called whenever we want to extend core access level system
	 with new security areas and/or sections i.e.:
	 . on any page with non-standard security areas
	 . in security roles editor
	 The call should be placed between session.inc inclusion and Page::start() call.
	 Up to 155 security sections and 155 security areas for any extension can be installed.
 */
/*
		 Helper function to retrieve extension access definitions in isolated environment.
	 */