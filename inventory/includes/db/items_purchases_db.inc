<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

function add_item_purchasing_data($supplier_id, $stock_id, $price,
	$suppliers_uom, $conversion_factor, $supplier_description)
{
	$sql = "INSERT INTO  purch_data (supplier_id, stock_id, price, suppliers_uom,
		conversion_factor, supplier_description) VALUES (";
	$sql .= db_escape($supplier_id).", ".db_escape($stock_id). ", "
		.$price . ", ".db_escape($suppliers_uom ). ", "
		.$conversion_factor . ", "
		.db_escape($supplier_description) . ")";

	db_query($sql,"The supplier purchasing details could not be added");
}

function update_item_purchasing_data($selected_id, $stock_id, $price,
	$suppliers_uom, $conversion_factor, $supplier_description)
{
	$sql = "UPDATE  purch_data SET price=" . $price . ",
		suppliers_uom=".db_escape($suppliers_uom) . ",
		conversion_factor=" . $conversion_factor . ",
		supplier_description=" . db_escape($supplier_description) . "
		WHERE stock_id=".db_escape($stock_id) . " AND
		supplier_id=".db_escape($selected_id);
	db_query($sql,"The supplier purchasing details could not be updated");
}

function delete_item_purchasing_data($selected_id, $stock_id)
{
	$sql = "DELETE FROM  purch_data WHERE supplier_id=".db_escape($selected_id)."
		AND stock_id=".db_escape($stock_id);
	db_query($sql,"could not delete purchasing data");
}

function get_items_purchasing_data($stock_id)
{
    $sql = "SELECT  purch_data.*, suppliers.supp_name,suppliers.curr_code
		FROM  purch_data INNER JOIN  suppliers
		ON  purch_data.supplier_id= suppliers.supplier_id
		WHERE stock_id = ".db_escape($stock_id);

    return db_query($sql, "The supplier purchasing details for the selected part could not be retrieved");
}

function get_item_purchasing_data($selected_id, $stock_id)
{
	$sql = "SELECT  purch_data.*, suppliers.supp_name FROM  purch_data
		INNER JOIN  suppliers ON  purch_data.supplier_id= suppliers.supplier_id
		WHERE  purch_data.supplier_id=".db_escape($selected_id)."
		AND  purch_data.stock_id=".db_escape($stock_id);

	$result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

	return db_fetch($result);
}


?> 