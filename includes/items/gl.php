<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 1/11/11
 * Time: 7:20 AM
 * To change this template use File | Settings | File Templates.
 */ 

	class Items_Gl {

				var $code_id;
				var $dimension_id;
				var $dimension2_id;
				var $amount;
				var $reference;
				var $description;

				function __construct($code_id, $dimension_id, $dimension2_id, $amount, $reference,
												 $description = null) {
					//echo "adding $index, $code_id, $dimension_id, $amount, $reference<br>";

					if ($description == null)
						$this->description = get_gl_account_name($code_id);
					else
						$this->description = $description;

					$this->code_id = $code_id;
					$this->dimension_id = $dimension_id;
					$this->dimension2_id = $dimension2_id;
					$this->amount = $amount;
					$this->reference = $reference;
				}
			}

			//---------------------------------------------------------------------------------------