<?php

class RepeatOrderModifier extends OrderModifier {


	public static $singular_name = "Repeat Order Modifier";
		function i18n_singular_name() { return _t("RepeatOrderModifier.REPEATORDERMODIFIER", "Repeat Order Modifier");}

	public static $plural_name = "Repeat Order Modifiers";
		function i18n_plural_name() { return _t("RepeatOrderModifier.REPEATORDERMODIFIERS", "Repeat Order Modifiers");}


	/**
	 * standard OrderModifier Method
	 * Should we show a form in the checkout page for this modifier?
	 */
	public function showForm() {
		return $this->Order()->Items();
	}

	public function getModifierForm($optionalController = null, $optionalValidator = null) {

		$fields = new FieldSet();
		$fields->push($this->headingField());
		$fields->push($this->descriptionField());
		$orderID = Session::get('RepeatOrder');
		$createLink = RepeatOrdersPage::get_repeat_order_link('createorder');
		if($orderID && Member::currentMember()) {
			$order = DataObject::get_by_id('RepeatOrder', $orderID);
			$updateLink = RepeatOrdersPage::get_repeat_order_link('update', $orderID);
			$cancelLink = RepeatOrdersPage::get_repeat_order_link('cancel', $orderID);
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
		return new RepeatOrderModifier_Form($optionalController, 'RepeatOrderModifier', $fields, new FieldSet(), $optionalValidator);

	}

	public function LiveCalculatedTotal() {
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


class RepeatOrderModifier_Form extends OrderModifierForm {
	/**
	 *
	 */
	function __construct($optionalController = null, $name,FieldSet $fields, FieldSet $actions,$optionalValidator = null) {
		parent::__construct($optionalController, $name,$fields,$actions,$optionalValidator);
		Requirements::javascript("ecommerce_modifier_example/javascript/ModifierExample.js");
	}

	public function submit($data, $form) {
	}
}
