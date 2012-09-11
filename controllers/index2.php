<?php
  $sql = "SELECT c.*, t.name as tax_name FROM stock_category c, item_tax_types t WHERE c.dflt_tax_type=t.id";
       if (false) {
         $sql .= " AND !c.inactive";
       }
       $results = DB::_query($sql);
       var_dump (DB::_fetchAll());
