<?php

class RepeatOrderModifier extends OrderModifier {

	protected static $is_chargable = false;

	public static function show_form() {
		return true;
	}

	public static function get_form($controller) {
		$fields = new FieldSet();

		$orderID = Session::get('RepeatOrder');

		$createLink = RepeatOrdersPage::get_Repeat_order_link('create');

		if($orderID && Member::currentMember()) {
			$order = DataObject::get_by_id('RepeatOrder', $orderID);

			$updateLink = RepeatOrdersPage::get_Repeat_order_link('update', $orderID);
			$cancelLink = RepeatOrdersPage::get_Repeat_order_link('cancel', $orderID);

			if($order->CanModify()) {
				 $fields->push(new LiteralField('modifyRepeatOrder',
<<<HTML
					<div class="Actions"><input id="ModifyRepeatOrderUpdate"  class="action" type="button" value="Save changes to your Repeat Order #$orderID" onclick="window.location='{$updateLink}';" /></div>
HTML
					)
				);

			}
			else {
				$fields->push(new LiteralField('createRepeatOrder',
<<<HTML
						<div class="Actions"><input id="ModifyRepeatOrderCreate" class="action" type="button" value="Create a new Repeat Order" onclick="window.location='{$createLink}';" /></div>
HTML
					)
				);
			}
			Requirements::customScript("jQuery(document).ready(function(){jQuery(\"input[name='action_processOrder']\").hide();});", "hide_action_processOrder");
		}
		else if(Member::currentMember()) {
			if(!Session::get("DraftOrderID")) {
				$fields->push(new LiteralField('createRepeatOrder',
<<<HTML
					<div class="Actions"><input  id="ModifyRepeatOrderCreate" class="action" type="button" value="Turn this Order into a Repeat Order" onclick="window.location='{$createLink}';" /></div>
HTML
					)
				);
				$page = DataObject::get_one("RepeatOrdersPage");
				if($page) {
					$fields->push(new LiteralField("whatAreRepeatOrders",
<<<HTML
					<div id="WhatAreRepeatOrders">$page->WhatAreRepeatOrders</div>
HTML
					));
				}
			}
			else {
					$fields->push(new LiteralField("whatAreRepeatOrders",
<<<HTML
					<div id="WhatAreRepeatOrders">This order is based on a Repeat Order.</div>
HTML
					));
			}
		}
		else {
			$page = DataObject::get_one("RepeatOrdersPage");
			if($page) {
				$fields->push(new LiteralField("whatAreRepeatOrders",
<<<HTML
					<div id="WhatAreRepeatOrders">$page->OnceLoggedInYouCanCreateRepeatOrder</div>
HTML
				));
			}
		}
		return new OrderModifierForm($controller, 'ModifierForm', $fields, new FieldSet());
	}

	public function LiveCalculationValue() {
		return 0;
	}

	public function CanBeRemoved() {
		return false;
	}

	public function ShowInTable() {
		return false;
	}

	public function LiveName(){
		return "";
	}

}
