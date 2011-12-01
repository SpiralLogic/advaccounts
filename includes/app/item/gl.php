<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 1/11/11
	 * Time: 7:20 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Item_Gl
	{
		public $code_id;
		public $dimension_id;
		public $dimension2_id;
		public $amount;
		public $reference;
		public $description;

		function __construct($code_id, $dimension_id, $dimension2_id, $amount, $reference,
												 $description = null)
		{
			//echo "adding $index, $code_id, $dimension_id, $amount, $reference<br>";
			if ($description == null) {
				$this->description = GL_Account::get_name($code_id);
} else {
				$this->description = $description;
			}
			$this->code_id       = $code_id;
			$this->dimension_id  = $dimension_id;
			$this->dimension2_id = $dimension2_id;
			$this->amount        = $amount;
			$this->reference     = $reference;
		}
	}

