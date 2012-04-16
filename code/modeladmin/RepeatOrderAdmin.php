<?php

class RepeatOrderAdmin extends ModelAdmin {

}

class RepeatOrderAdmin_CollectionController extends ModelAdmin_CollectionController{
	//manages everything to do with overall control (e.g. search form, import, etc...)
	public function SearchForm() {
		$form = parent::SearchForm();
		$fields = $form->Fields();
		$source = ArrayLib::valuekey(RepeatOrder::get_delivery_days()); //note, valuekey is a SS function
		$fields->replaceField("DeliveryDay", new DropdownField("DeliveryDay", "Delivery Day", $source, null, null, "(Any)"));
		$source = RepeatOrder::get_period_fields();
		$fields->replaceField("Period", new DropdownField("Period", "Period", $source, null, null, "(Any)"));
		return $form;
	}
}
