<?php

class RepeatOrderAdmin extends ModelAdmin {

	public static $managed_models = array(
		'RepeatOrder'
	);

	public static $url_segment = 'Repeat-orders';

	public static $menu_title = 'Repeat Orders';

	public static $collection_controller_class = 'RepeatOrderAdmin_CollectionController';


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
