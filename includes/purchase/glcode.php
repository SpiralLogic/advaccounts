<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 1/11/11
 * Time: 7:07 AM
 * To change this template use File | Settings | File Templates.
 */ 

	class Purchase_GLCode {

		var $Counter;
		var $gl_code;
		var $gl_act_name;
		var $gl_dim;
		var $gl_dim2;
		var $amount;
		var $memo_;

		function Purchase_GLCode($Counter, $gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_) {

			/* Constructor function to add a new gl_codes object with passed params */
			$this->Counter = $Counter;
			$this->gl_code = $gl_code;
			$this->gl_act_name = $gl_act_name;
			$this->gl_dim = $gl_dim;
			$this->gl_dim2 = $gl_dim2;
			$this->amount = $amount;
			$this->memo_ = $memo_;
		}
	}