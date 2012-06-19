<?php

/**
 * @author nicolaas @ sunnysideup . co . nz
 */

class AutomaticallyCreatedOrderDecorator extends DataObjectDecorator {

	function extraStatics() {
		return array(
			'db' => array(
				"OrderDate" => "Date", //date at which the order should be placed
				"OrderDateInteger" => "Int" //date at which the order should be placed AS integer
			),
			'has_one' => array(
				'RepeatOrder' => 'RepeatOrder'
			)
		);
	}

	function updateCMSFields(&$fields) {
		$fields->removeByName("OrderDate");
		$fields->removeByName("OrderDateInteger");
		$fields->removeByName("RepeatOrderID");
	}

	function FuturePast() {
		$currentTime = strtotime(Date("Y-m-d"));
		$orderTime = strtotime($this->owner->OrderDate);
		if($currentTime > $orderTime) {
			return "past";
		}
		elseif($currentTime == $orderTime) {
			return "current";
		}
		else {
			return "future";
		}
	}



}
