<?php


class RepeatOrderForm extends Form {

	public function __construct($controller, $name, $orderID = null, $update = false) {

		if($update) {
			$RepeatOrder = DataObject::get_by_id('RepeatOrder', $orderID);
			//we have to make sure that order items are loaded into memory...
			$items = $RepeatOrder->OrderItems();
			$order = $controller->BlankOrder();
			$RepeatOrderID = $RepeatOrder->ID;
		}
		else {
			$order = $orderID ? DataObject::get_by_id('Order', $orderID) : $controller->BlankOrder();
			$items = $order->Items();
			$RepeatOrder = null;
			$RepeatOrderID = 0;
		}
		$items = $order->Items();
		$fields = new FieldSet();
		$fields->push(new HeaderField('AlternativesHeader', 'Products'));

		$products = DataObject::get('Product');
		$productsMap = $products->map('ID', 'Title', ' ');
		if($RepeatOrder) {
			//$fields->push($this->complexTableField($RepeatOrder));
		}
		if($items) {
			foreach($items as $item) {
				$fields->push(new DropdownField('Product[ID]['.$item->getProductID().']', "Preferred Product", $productsMap, $item->getProductID()));
				$fields->push(new NumericField('Product[Quantity]['.$item->getProductID().']', " ... quantity", $item->Quantity));
				$fields->push(new DropdownField('_Alternatives['.$item->getProductID().'][0]', " ... alternative 1", $productsMap));
				$fields->push(new DropdownField('_Alternatives['.$item->getProductID().'][1]', " ... alternative 2", $productsMap));
				$fields->push(new DropdownField('_Alternatives['.$item->getProductID().'][2]', " ... alternative 3", $productsMap));
				$fields->push(new DropdownField('_Alternatives['.$item->getProductID().'][3]', " ... alternative 4", $productsMap));
				$fields->push(new DropdownField('_Alternatives['.$item->getProductID().'][4]', " ... alternative 5", $productsMap));
			}
		}

		$fields->push(new HeaderField('DetailsHeader', 'Repeat Order Details'));
		$fields->push(new ListboxField('PaymentMethod', 'Payment Method', RepeatOrder::get_payment_methods(), null, count(RepeatOrder::get_payment_methods())));
		$fields->push(new DateField('Start', 'Start'));
		$fields->push(new DateField('End', 'End'));
		$fields->push(new ListboxField('Period', 'Period', RepeatOrder::get_period_fields(), null, count(RepeatOrder::get_period_fields())));

		$fields->push(new ListboxField(
			'DeliveryDay',
			'Delivery day:',
			$source = array_combine(
				RepeatOrder::get_delivery_days(),
				RepeatOrder::get_delivery_days()
			),
			null,
			count(RepeatOrder::get_delivery_days()),
			false
		));

		$fields->push(new TextareaField('Notes', 'Notes'));
		if($order->ID) $fields->push(new HiddenField('OrderID', 'OrderID', $order->ID));
		if($RepeatOrder) $fields->push(new HiddenField('RepeatOrderID', 'RepeatOrderID', $RepeatOrder->ID));

		$actions = new FieldSet();

		if($RepeatOrder) {
			$actions->push(new FormAction('doSave', 'Save'));
		}
		else {
			$actions->push(new FormAction('doCreate', 'Create'));
		}

		$required["Start"] = 'Start';
		$required["End"] = 'End';
		$required["Period"] = 'Period';
		$required["DeliveryDay"] = 'DeliveryDay';

		$requiredFields = new RequiredFields($required);

		parent::__construct($controller, $name, $fields, $actions, $requiredFields);

		if($RepeatOrder) {
			$this->loadDataFrom(array(
				'Start' => $RepeatOrder->Start,
				'End' => $RepeatOrder->End,
				'Period' => $RepeatOrder->Period,
				'Notes' => $RepeatOrder->Notes,
				'DeliveryDay' => $RepeatOrder->DeliveryDay,
				'PaymentMethod' => $RepeatOrder->PaymentMethod,
				'_Alternatives' => unserialize($RepeatOrder->Alternatives),
			));
		}
	}

	/**
	 * Create a new stadning
	 */
	public function doCreate($data, $form, $request) {
		$memberID = Member::currentUserID();

		if(isset($data['OrderID'])) {
			$order = DataObject::get_one('Order', 'Order.ID = \''.$data['OrderID'].'\' AND MemberID = \''.$memberID.'\'');
		}
		else {
			$order = $form->Controller()->BlankOrder();
		}
		if($order) {
			$params = $this->dataCheck($data);
			$RepeatOrder = RepeatOrder::createFromOrder($order, $params);
			$orderItems = $RepeatOrder->OrderItems();
			if($orderItems) {
				foreach($orderItems as $orderItem) {
					if(isset($data["Product"]["ID"][$orderItem->ProductID])) {$newProductID = $data["Product"]["ID"][$orderItem->ProductID];}
					if(isset($data["Product"]["Quantity"][$orderItem->ProductID])) {$newQuantity = $data["Product"]["Quantity"][$orderItem->ProductID];}
					$change = false;
					if($newProductID != $orderItem->ProductID && $newProductID) {$orderItem->ProductID = $newProductID; $change = true;}
					if($newQuantity != $orderItem->ProductID && ($newQuantity || $newQuantity === 0)) {$orderItem->Quantity = $newQuantity; $change = true;}
					if($change) {
						$orderItem->write();
					}
				}
			}

			Director::redirect(RepeatOrdersPage::get_Repeat_order_link('view', $RepeatOrder->ID));
		}
		else {
			Director::redirectBack();
		}

		return true;
	}

	/**
	 * Save the changes
	 */
	public function doSave($data, $form, $request) {
		Versioned::reading_stage('Stage');

		$memberID = Member::currentUserID();

		$RepeatOrder = DataObject::get_one('RepeatOrder', 'RepeatOrder.ID = \''.$data['RepeatOrderID'].'\' AND MemberID = \''.$memberID.'\'');

		if($RepeatOrder) {
			$params = array();

			//stop versioning while we make alterations
			RepeatOrder::$update_versions = false;

			$orderItems = $RepeatOrder->OrderItems();

			if($orderItems) {
				foreach($orderItems as $orderItem) {
					$orderItem->delete();
				}
			}
			$orderItems = $form->Controller()->BlankOrder()->Items();
			if($orderItems) {
				foreach($orderItems as $orderItem) {
					$RepeatOrderItem = new RepeatOrder_OrderItem();
					$RepeatOrderItem->OrderID = $RepeatOrder->ID;
					$RepeatOrderItem->OrderVersion = $RepeatOrder->Version;
					$RepeatOrderItem->ProductID = $data["Product"]["ID"][$orderItem->ProductID];
					$RepeatOrderItem->Quantity = $data["Product"]["Quantity"][$orderItem->ProductID];
					$RepeatOrderItem->write();
				}
			}

			//start versioning again
			RepeatOrder::$update_versions = true;

			$params = $this->dataCheck($data);

			$RepeatOrder->update($params);
			$RepeatOrder->Status = 'Pending';
			$RepeatOrder->write();
			Session::set('RepeatOrder', null);
		}

		Director::redirect(RepeatOrdersPage::get_Repeat_order_link('view', $RepeatOrder->ID));

		return true;
	}

	protected function dataCheck($data) {
		if(isset($data['Start'])) {$params['Start'] = $data['Start'];} else {$params["Start"] = Date("Y-m-d");}
		if(isset($data['End'])) {$params['End'] = $data['End'];} else {$params["End"] = Date("Y-m-d", strtotime("+1 year"));}
		if(isset($data['Period'])) {$params['Period'] = $data['Period'];} else {$data['Period'] = RepeatOrder::default_period_key();}
		if(isset($data['DeliveryDay'])) {$params['DeliveryDay'] = $data['DeliveryDay'];} else {$data['DeliveryDay'] = RepeatOrder::default_delivery_day_key();}
		if(isset($data['PaymentMethod'])) {$params['PaymentMethod'] = $data['PaymentMethod'];} else {$data['PaymentMethod'] = RepeatOrder::default_payment_method_key();}
		if(isset($data['Notes'])) $params['Notes'] = $data['Notes'];
		return Convert::raw2sql($params);
	}

	function complexTableField($controller) {
		$t = new HasManyComplexTableField(
			$controller,
			$name = "OrderItems",
			$sourceClass = "RepeatOrder_OrderItem",
			$fieldList = array("Quantity" => "Quantity"),
			$detailFormFields = null,
			$sourceFilter = "RepeatOrder_OrderItem.OrderID = ".$controller->ID,
			$sourceSort = "",
			$sourceJoin = ""
		);
		return $t;
	}

}
