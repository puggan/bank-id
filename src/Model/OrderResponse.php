<?php

	namespace Puggan\BankID\Model;

	/**
	 * Class OrderResponse
	 * @package Puggan\BankID\Model
	 *
	 * @property string orderRef
	 * @property string autoStartToken
	 */
	class OrderResponse
	{
		public $orderRef;
		public $autoStartToken;

		/**
		 * OrderResponse constructor.
		 *
		 * @param mixed[] $data
		 */
		public function __construct(array $data = [])
		{
			foreach($data as $key => $value)
			{
				$this->$key = $value;
			}
		}
	}
