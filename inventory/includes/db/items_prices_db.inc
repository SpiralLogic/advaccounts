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
function add_item_price($stock_id, $sales_type_id, $curr_abrev, $price)
{
	$sql = "INSERT INTO prices (stock_id, sales_type_id, curr_abrev, price)
		VALUES (".db_escape($stock_id).", ".db_escape($sales_type_id)
		.", ".db_escape($curr_abrev).", ".db_escape($price).")";
	
	db_query($sql,"an item price could not be added");		
}

function update_item_price($price_id, $sales_type_id, $curr_abrev, $price)
{
	$sql = "UPDATE prices SET sales_type_id=".db_escape($sales_type_id).",
		curr_abrev=".db_escape($curr_abrev).", 
		price=".db_escape($price)." WHERE id=".db_escape($price_id);
	
	db_query($sql,"an item price could not be updated");		
}

function delete_item_price($price_id)
{
	$sql="DELETE FROM prices WHERE id= ".db_escape($price_id);
	db_query($sql,"an item price could not be deleted");			
}

function get_prices($stock_id)
{
	$sql = "SELECT sales_types.sales_type, prices.*
		FROM prices, sales_types
		WHERE prices.sales_type_id = sales_types.id
		AND stock_id=".db_escape($stock_id)
		." ORDER BY curr_abrev, sales_type_id";	
	
	return db_query($sql,"item prices could not be retreived");
}

function get_stock_price($price_id)
{
	$sql = "SELECT * FROM prices WHERE id=".db_escape($price_id);
	
	$result = db_query($sql,"price could not be retreived");
	
	return db_fetch($result);
}

?>