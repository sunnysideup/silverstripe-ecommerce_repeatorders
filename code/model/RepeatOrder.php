<?php

/**
 * @author michael@ sunnysideup . co . nz
 */

class RepeatOrder extends DataObject {

	protected static $minimum_days_in_the_future = 1;
		static function set_minimum_days_in_the_future($v) {self::$minimum_days_in_the_future = intval($v); }
		static function get_minimum_days_in_the_future() {return self::$minimum_days_in_the_future;}

	public static $db = array(
		'Status' => "Enum('Pending, Active, MemberCancelled, AdminCancelled, Finished', 'Pending')",
		//dates
		'Start' => 'Date',
		'End' => 'Date',
		'Period' => 'Varchar',
		'DeliveryDay' => 'Text',
		//payment
		'PaymentMethod' => 'Varchar',
		"CreditCardOnFile" => "Boolean",
		"PaymentNote" => "Text",
		//computed values and notes
		'Alternatives' => 'Text', //Serialized Array
		'Items' => 'Text', //FOR SEARCH PURPOSES ONLY!
		'Notes' => 'Text'

	);

	public static $has_one = array(
		'Member' => 'Member',
	);

	public static $has_many = array(
		'OrderItems' => 'RepeatOrder_OrderItem', //products & quanitites
		'Orders' => 'Order'
	);

	public static $casting = array(
		"OrderItemList" => "Text",
		"FirstOrderDate" => "Date",
		"LastOrderDate" => "Date",
		"NextOrderDate" => "Date",
		"FinalOrderDate" => "Date",
		"DeliverySchedule" => "Text"
	);

	public static $searchable_fields = array(
		"Member.Email" => "PartialMatchFilter",
		"Member.Surname" => "PartialMatchFilter",
		"Items" => "PartialMatchFilter",
		"Period",
		"DeliveryDay",
		"Status"
	);

	public static $summary_fields = array(
		'ID' => 'Repeat Order ID',
		'Member.Surname' => 'Surname',
		'Member.Email' => 'Email',
		'OrderItemList' => 'Order Item List',
		'Start' => 'Start',
		'End' => 'End',
		'Period' => 'Period',
		'DeliveryDay' => 'Delivery Day',
		'Status' => 'Status'
	);

	public static $default_sort = 'Created DESC';

	public static $versioning = array(
		'Stage'
	);

	public static $extensions = array(
		"Versioned('Stage')"
	);

	/**
	 * Dropdown options for Period
	 * @var array 'strtotime period' > 'nice name'
	 */
	protected static $period_fields = array(
		'1 week' => 'Weekly',
		'2 weeks' => 'Fornightly',
		'1 month' => 'Monthly',
	);

	static function set_period_fields($array) { self::$period_fields = $array;}
	static function get_period_fields() { return self::$period_fields;}
	static function default_period_key() {if($a = self::get_period_fields()) {foreach($a as $k => $v) {return $k;}}}
	/**
	 * Should orderItems/orders call a write to update versions?
	 */
	public static $update_versions = true;

	protected static $schedule = array();

	protected static $status_nice = array(
		'Pending' => 'Pending',
		'Active' => 'Active',
		'MemberCancelled' => 'Pending Cancellation',
		'AdminCancelled' => 'Cancelled',
		'Finished' => 'Finished',
	);

	/**
	 * @var array
	 */
	protected static $delivery_days = array(
		'Monday',
		'Tuesday',
		'Wednesday',
		'Thursday',
		'Friday',
		'Saturday',
		'Sunday',
	);

	protected static $payment_methods = array(
		'DirectCreditPayment' => 'Direct Credit (payment into bank account)'
	);

	/**
	 * set the delivery days options
	 * @param $days array of days
	 * @return null
	 */
	public static function set_delivery_days($days = array()) {
		self::$delivery_days = $days;
	}

	public static function get_delivery_days() {
		$array = array();
		$page = DataObject::get_one("RepeatOrdersPage");
		if($page) {
			$array = explode(",", $page->OrderDays);
		}
		if(count($array)) {
			return $array;
		}
		else {
			return self::$delivery_days;
		}
	}

	public static function default_delivery_day_key() {
		$a = self::get_delivery_days();
		if(count($a)) {
			foreach($a as $k => $v) {
				return $k;
			}
		}
	}
	/**
	 * @param $days array of days
	 * @return null
	 */
	public static function set_payment_methods($payment_methods = array()) {
		self::$payment_methods = $payment_methods;
	}

	public static function get_payment_methods() {
		return self::$payment_methods;
	}

	public static function default_payment_method_key() {
		$a = self::get_payment_methods();
		foreach($a as $k => $v) {
			return $k;
		}
	}

	public function CanModify() {
		if(in_array($this->Status, array('Pending', 'Active'))) {
			return true;
		}
		else return false;
	}

	public function Link() {
		return RepeatOrdersPage::get_Repeat_order_link('view', $this->ID);
	}

	public function Period() {
		$a = self::get_period_fields();
		if(isset($a[$this->Period])) { return $a[$this->Period];}
	}

	public function ModifyLink() {
		return RepeatOrdersPage::get_Repeat_order_link('modify', $this->ID);
	}

	public function CancelLink() {
		return RepeatOrdersPage::get_Repeat_order_link('cancel', $this->ID);
	}

	public function DoneLink() {
		$page = DataObject::get_one("RepeatOrdersPage");
		if($page) {
			return $page->Link();
		}
		$page = DataObject::get_one("Page", "URLSegment 'home'");
		return $page->Link();
	}

	public function OutRepeatAutomaticallyCreatedOrders() {
		$orders = DataObject::get("Order", "RepeatOrderID = ".$this->ID, "OrderDate ASC");
		$dos = new DataObjectSet();
		if($orders) {
			foreach($orders as $order) {
				if(!$order->Completed()) {
					$dos->push($order);
				}
			}
		}
		return $dos;
	}

//====================================================================================================================================================================================

	/**
	 * Create due draft orders
	 */
	public static function create_automatically_created_orders() {
		Versioned::reading_stage('Stage');

		set_time_limit(0); //might take a while with lots of orders


		//get all Repeat orders
		$RepeatOrders = DataObject::get('RepeatOrder', 'Status = \'Active\'');

		RepeatOrder::$update_versions = false;
		if($RepeatOrders) {
			foreach($RepeatOrders as $RepeatOrder) {
				$RepeatOrder->addAutomaticallyCreatedOrders();
			}
		}
		RepeatOrder::$update_versions = true;
	}



	public function addAutomaticallyCreatedOrders() {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		//current time + period is less than LastCreated and less then end
		$currentTime = (strtotime(date('Y-m-d'))-1);
		$firstOrderDate = $this->FirstOrderDate();
		if($firstOrderDate) {
			$startTime = strtotime($firstOrderDate->format("Y-m-d"));
		}
		$endTime = strtotime($this->End);
		if(!$firstOrderDate) {
			$this->Status = 'Pending';
			$this->write();
		}
		elseif($currentTime > $endTime && $this->Status != "Finished") {
			$this->Status = 'Finished';
			$this->write();
		}
		elseif($startTime < $currentTime) {
			$a = $this->workOutSchedule();
			if(count($a)) {
				foreach($a as $orderDateInteger => $orderDateLong) {
					if($obj = DataObject::get_one("Order", "{$bt}OrderDateInteger{$bt} = ".$orderDateInteger." AND RepeatOrderID = ".$this->ID)) {
						//do nothing
					}
					else {
						$this->createAutomaticallyCreatedOrder($orderDateInteger, false);
					}
				}
			}
		}
	}

	/**
	 * Create a new AutomaticallyCreatedOrder from the RepeatOrder
	 * @return null
	 */
	public function createAutomaticallyCreatedOrder($orderDateInteger, $redirect = true) {
		//create AutomaticallyCreatedOrder order
		if($order = DataObject::get_one("Order", "OrderDateInteger = '".$orderDateInteger."' AND RepeatOrderID = ".$this->ID)) {
			//do nothing
		}
		elseif(!$this->MemberID){
			USER_ERROR("Can not create Order without member linked in RepeatOrder #".$this->ID, E_USER_ERROR);
		}
		elseif(!$orderDateInteger){
			USER_ERROR("Can not create Order without date for in RepeatOrder #".$this->ID, E_USER_ERROR);
		}
		else {
			$order = new Order();
			$order->OrderDate = date("Y-m-d", $orderDateInteger);
			$order->RepeatOrderID = $this->ID;
			$order->MemberID = $this->MemberID;
			$order->write();
		}

		if($redirect) Director::redirect($order->Link());
		return $order->ID;
	}



	/**
	 * Create a RepeatOrder from a regular Order and its Order Items
	 * @param Order $Order
	 * @param $params params
	 * @return RepeatOrder
	 */
	public static function createFromOrder(Order $Order, $params = array()) {
		Versioned::reading_stage('Stage');

		$RepeatOrder = new RepeatOrder();
		$RepeatOrder->Status = 'Pending';
		$RepeatOrder->MemberID = $Order->MemberID;
		$RepeatOrder->update($params);
		$RepeatOrder->write();

		$orderItems = $Order->Items();

		if($orderItems) {
			foreach($orderItems as $orderItem) {
				$RepeatOrderItem = new RepeatOrder_OrderItem();
				$RepeatOrderItem->OrderID = $RepeatOrder->ID;
				$RepeatOrderItem->OrderVersion = $RepeatOrder->Version;
				$RepeatOrderItem->ProductID = $orderItem->ProductID;
				$RepeatOrderItem->Quantity = $orderItem->Quantity;
				$RepeatOrderItem->write();
			}
		}

		$RepeatOrder->write();


		return $RepeatOrder;
	}





//================================================================================================================================================================================

	public function TableAlternatives() {
		$alternatives = unserialize($this->Alternatives);

		$products = new DataObjectSet();

		if(is_array($alternatives)) {
			foreach($alternatives as $id => $alternative) {
				$product = DataObject::get_by_id('Product', $id);

				$product->Alternatives = new DataObjectSet();

				if(is_array($alternative)) {
					foreach($alternative as $id) {
						if($id) {
							$alternativeProduct = DataObject::get_by_id('Product', $id);

							if($alternativeProduct) $product->Alternatives->push($alternativeProduct);
						}
					}
				}

				$products->push($product);
			}
		}

		return $products;
	}


	public function AlternativesPerProduct($productID) {
		$dos = $this->TableAlternatives();
		foreach($dos as $do) {
			if($do->ID = $productID) {
				return $do->Alternatives;
			}
		}
	}

	public function TableDeliveryDay() {
		return $this->DeliveryDay;
	}

	public function TablePaymentMethod() {
		if(isset(self::$payment_methods[$this->PaymentMethod])) {
			return self::$payment_methods[$this->PaymentMethod];
		}
	}

	public function TableStatus() {
		return self::$status_nice[$this->Status];
	}


//==========================================================================================================================================================



	/**
	 * CMS Fields for ModelAdmin, use different fields for adding/editing
	 * @see sapphire/core/model/DataObject#getCMSFields($params)
	 */
	public function getCMSFields() {
		if($this->ID) {
			return self::getCMSFields_edit();
		}
		else {
			return self::getCMSFields_add();
		}
	}

	/**
	 * CMS Fields to adding via ModelAdmin
	 * @return FieldSet
	 */
	public function getCMSFields_add() {
		$fields = new FieldSet(
			new TabSet('Root',
				new Tab('Main',
					new AutocompleteTextField('Email', 'Email (Auto complete)', 'admin/security/autocomplete/Email'),
					new ListboxField('PaymentMethod', 'Payment Method', self::get_payment_methods(), null, count(self::get_payment_methods())),
					new DateField('Start', 'Start'),
					new DateField('End', 'End (Optional)'),
					new ListboxField('Period', 'Period', self::get_period_fields(), null, count(self::get_period_fields())),
					new ListboxField(
						'DeliveryDay',
						'Delivery day:',
						$source = array_combine(
							self::get_delivery_days(),
							self::get_delivery_days()
						),
						$this->DeliveryDay,
						7,
						false
					),
					new TextareaField('Notes', 'Notes')
				)
			)
		);

		return $fields;
	}

	/**
	 * CMS Fields to adding via ModelAdmin
	 * @return FieldSet
	 */
	public function getCMSFields_edit() {
		$firstCreated = "can not be computed"; if ($firstCreatedObj = $this->FirstOrderDate()) {$firstCreated = $firstCreatedObj->Long();}
		$lastCreated = "no orders have been placed yet"; if ($lastCreatedObj = $this->LastOrderDate()) {$lastCreated = $lastCreatedObj->Long();}
		$finalCreated = "can not be computed"; if ($finalCreatedObj = $this->FinalOrderDate()) {$finalCreated = $finalCreatedObj->Long();}
		if($this->Status == "Active") {
			$nextCreated = "can not be computed"; if ($nextCreatedObj = $this->NextOrderDate()) {$nextCreated = $nextCreatedObj->Long();}
		}
		else {
			$nextCreated = "Repeat Order not active - no next date is available if the Repeat Order is not active.";
		}
		if(!$this->DeliveryDay) {
			$firstCreated = $finalCreated = $lastCreated = $nextCreated = "Please select a delivery day first.";
		}
		$page = DataObject::get_one("RepeatOrdersPage");
		if($page) {
			$RepeatOrdersPageLink = '<h3>Create Orders</h3><p><a href="'.$page->Link().'admin" target="_blank">please click here to create and execute actual orders for this Repeat order ....</a></p><h3>Review Orders</h3>';
		}
		else {
			$RepeatOrdersPageLink = '<p>You must create a Repeat Orders Page before you can execute orders.</p>';
		}
		$fields = new FieldSet(
			new TabSet('Root',
				new Tab('Main',
					new LiteralField('Readonly[ID]', '<p>Repeat Order Number: '.$this->ID.'</p>'),
					new LiteralField('Readonly[Member]',
<<<HTML
	<div class="field readonly " id="Readonly[Member]">
		<label for="Form_EditForm_Readonly-Member" class="left">Member</label>
		<div class="middleColumn">
			<span class="readonly" id="Form_EditForm_Readonly-Member">{$this->Member()->getTitle()} ({$this->Member()->Email}) <a target="_blank" href="admin/security/EditForm/field/Members/item/{$this->Member()->ID}/show">(view)</a></span>
			<input type="hidden" value="{$this->Member()->getTitle()} ({$this->Member()->Email})" name="Readonly[Member]"/>
		</div>
	</div>
HTML
					),
					new DropdownField('Status', 'Status', self::$status_nice),
					new DateField('Start', 'Start'),
					new DateField('End', 'End (Optional)'),
					new ListboxField('Period', 'Period', self::get_period_fields(), null, count(self::get_period_fields())),
					new ListboxField(
						'DeliveryDay',
						'Delivery day:',
						array_combine(
							self::get_delivery_days(),
							self::get_delivery_days()
						),
						$this->DeliveryDay,
						7,
						false
					),
					new TextareaField('Notes', 'Notes')
				),
				new Tab('Products',
					$this->getCMSProductsTable()
				),
				new Tab('Orders',
					new LiteralField("RepeatOrdersPageLink", $RepeatOrdersPageLink),
					$this->getCMSPreviousOrders(),
					new ReadonlyField("DeliveryScheduleFormatted", "Delivery Schedule", $this->DeliverySchedule()),
					new ReadonlyField("FirstCreatedFormatted", "First Order", $firstCreated),
					new ReadonlyField("LastCreatedFormatted", "Last Order", $lastCreated),
					new ReadonlyField("NextCreatedFormatted", "Next Order", $nextCreated),
					new ReadonlyField("FinalCreatedFormatted", "Final Order", $finalCreated)
				),
				new Tab('Payment',
					new CheckboxField("CreditCardOnFile", "Credit Card on File"),
					new ListboxField('PaymentMethod', 'Payment Method', self::get_payment_methods(), null, count(self::get_payment_methods())),
					new TextareaField('PaymentNote', 'Payment Note')
				)
			)
		);

		$alternatives = unserialize($this->Alternatives);
		$orderItems = $this->OrderItems();

		$products = DataObject::get('Product');
		$productsMap = array('');
		$productsMap = $products->map('ID', 'Title', ' ');

		if($orderItems) {
			foreach($orderItems as $orderItem) {
				$value = isset($alternatives[$orderItem->ProductID]) ? $alternatives[$orderItem->ProductID] : null;
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][0]', $orderItem->BuyableTitle(), $productsMap, $value[0]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][1]', '', $productsMap, $value[1]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][2]', '', $productsMap, $value[2]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][3]', '', $productsMap, $value[3]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][4]', '', $productsMap, $value[4]));
			}
		}

		/*
		to do: MAKE WORK
		$fields->addFieldToTab('Root', new Tab(
			'HistoryOfChanges',
			new HeaderField("HistoryTableHeader", "List of updates to the Repeat order"),
			$this->getCMSHistoryTable()
		));
		*/
		return $fields;
	}

	/**
	 * CMS Fields for Popup
	 * @return FieldSet
	 */
	public function getCMSFields_forPopup() {
		$fields = new FieldSet(
			new TabSet('Root',
				new Tab('Main',
					new ReadonlyField('Readonly[Member]', 'Member', $this->Member()->getTitle().' ('.$this->Member()->Email.')'),
					new DropdownField('Status', 'Status', self::$status_nice),
					new ListboxField('PaymentMethod', 'Payment Method', self::get_payment_methods(), null, count(self::get_payment_methods())),
					new DateField('Start', 'Start'),
					new DateField('End', 'End (Optional)'),
					new DropdownField('Period', 'Period', self::get_period_fields()),
					new TextField('DeliveryDay', 'Delivery Day'),
					new TextareaField('Notes', 'Notes')
				),
				new Tab('Products',
					$this->getCMSVersionedProductsTable()
				)
			)
		);

		$alternatives = unserialize($this->Alternatives);
		$orderItems = $this->getVersionedComponents('OrderItems');

		$products = DataObject::get('Product');
		$productsMap = array('');
		$productsMap = $products->map('ID', 'Title', ' ');

		if($orderItems) {
			foreach($orderItems as $orderItem) {
				$value = isset($alternatives[$orderItem->ProductID]) ? $alternatives[$orderItem->ProductID] : null;
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][0]', $orderItem->BuyableTitle(), $productsMap, $value[0]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][1]', '', $productsMap, $value[1]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][2]', '', $productsMap, $value[2]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][3]', '', $productsMap, $value[3]));
				$fields->addFieldToTab('Root.Alternatives', new DropdownField('_Alternatives['.$orderItem->ProductID.'][4]', '', $productsMap, $value[4]));
			}
		}

		return $fields;
	}

	/**
	 * Get products table
	 * @return ComplexTableField
	 */
	public function getCMSProductsTable() {
		$products = DataObject::get('Product');

		$fields = new TabSet('Root',
			new Tab('Main',
				new DropdownField('ProductID', 'Choose a product:', $products->map()),
				new TextField('Quantity', 'Quantity')
			)
		);

		$table = new ComplexTableField(
			$this,
			'OrderItems',
			'RepeatOrder_OrderItem',
			array(
				'Product.Title' => 'Title',
				'Quantity' => 'Qty',
			),
			new FieldSet($fields)
		);

		$table->setShowPagination(false);
		$table->setAddTitle('Product');
		$table->setPermissions(array('add', 'edit', 'delete'));

		return $table;
	}


	/**
	 * Get previous actual order table
	 * @return ComplexTableField
	 */
	public function getCMSPreviousOrders() {
		$table = new ComplexTableField(
			$controller = $this,
			$name = 'PreviousOrders',
			$sourceClass = 'Order',
			$fieldList = array(
				"ID" => "Order ID",
				"Total" => "Value",
				"OrderDate" => "OrderDate",
				"Status" => "Status",
				"ViewHTMLLink" => "Link"
			),
			$detailFormFields = new FieldSet(),
			$sourceFilter = "RepeatOrderID = ".$this->ID,
			$sourceSort = "OrderDateInteger DESC",
			$sourceJoin = ""
		);
		$table->setFieldCasting(array(
			'OrderDate' => 'Date->Long',
			'Total' => 'Currency->Nice'
		));
		$table->setShowPagination(false);
		$table->setAddTitle('Previous Orders');
		$table->setPermissions(array("export"));
		return $table;
	}



	/**
	 * Get products table for versioned popup
	 * @return ComplexTableField
	 */
	public function getCMSVersionedProductsTable() {
		$table = new ComplexTableField(
			$this,
			'OrderItems',
			'RepeatOrder_OrderItem',
			array(
				'Product.Title' => 'Title',
				'Quantity' => 'Qty',
			),
			'getCMSFields_forPopup'
		);

		$table->setCustomSourceItems($this->getVersionedComponents('OrderItems'));

		$table->setShowPagination(false);
		$table->setPermissions(array());

		return $table;
	}

	/**
	 * Repeating Order History
	 * @return unknown_type
	 */
	public function getCMSHistoryTable() {
		$table = new VersionedComplexTableField(
			$this,
			'RepeatOrders',
			'RepeatOrder',
			array(
				'LastEdited' => 'Date'
			),
			'getCMSFields_forPopup'
		);

		$table->setCustomSourceItems($this->allVersions());
		$table->setPermissions(array('show'));

		return $table;
	}

	public function getVersionedComponents($component) {
		$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
		$baseTable = ClassInfo::baseDataClass(self::$has_many[$component]);
		$query = singleton(self::$has_many[$component])->buildVersionSQL("{$bt}{$baseTable}{$bt}.OrderID = {$this->ID} AND {$bt}{$baseTable}{$bt}.OrderVersion = {$this->Version}");
		$result = singleton(self::$has_many[$component])->buildDataObjectSet($query->execute());

		return $result;
	}


//========================================================================================================================================================================================================================================================================

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		$currentTime = strtotime(date('Y-m-d')) + (60 * 60 * 24 * self::get_minimum_days_in_the_future());
		$startTime = strtotime($this->Start);
		$endTime = strtotime($this->End);


		if(($startTime + ( 7 * 60 * 60 * 24)) > $endTime) {
			$this->End = date('Y-m-d', $startTime + ( 7 * 60 * 60 * 24));
		}

		$this->Items = $this->OrderItemList();
		if(isset($_REQUEST['DeliveryDay'])) {
			$this->DeliveryDay = $_REQUEST['DeliveryDay'];
		}

		if(isset($_REQUEST['_Alternatives'])) {
			foreach($_REQUEST['_Alternatives'] as $key => $array) {
				for($i = 0; $i < 5; $i++) {
					$item = $_REQUEST["_Alternatives"][$key][$i];
					if($item > 0 && $i > 0) {
						for($j = ($i - 1); $j >= 0; $j--) {
							$previousItem = $_REQUEST["_Alternatives"][$key][$j];
							if($item == $previousItem) {
								$_REQUEST["_Alternatives"][$key][$i]  = 0;
							}
						}
					}
				}
			}
			$this->Alternatives = serialize($_REQUEST['_Alternatives']);
		}

		if($this->ID == false) {

			if(!isset($this->record['Version'])) {
				$this->record['Version'] = -1;
			}

			$_SQL = Convert::raw2sql($_POST);

			if($this->MemberID) {
				$member = DataObject::get_by_id('Member', $this->MemberID);
			}
			else {
				$member = DataObject::get_one('Member', 'Email = \''.$_SQL['Email'].'\'');
			}

			$data = array(); foreach($_POST as $k => $v) if($v) $data[$k] = $v;

			if($member) {
				$member->update($data);
			}
			else {
				$member = new Member();
				$member->update($data);
			}

			$member->write();

			if($this->MemberID == false) {
				$this->Notes = 'Manually Created by '.Member::currentMember()->Name;
				$this->MemberID = $member->ID;
			}

			self::$update_versions = false;
		}
	}

	/**
	 * Complex Versioning accross more than one DataObject
	 */
	public function onAfterWrite() {
		parent::onAfterWrite();

		if(self::$update_versions) {
			self::$update_versions = false;

			$OrderItems = DataObject::get('RepeatOrder_OrderItem', 'OrderID = '.$this->ID);

			if($OrderItems) foreach($OrderItems as $OrderItem) {
				if(
					$OrderItem->ID != $this->ignoreID &&
					$OrderItem->ClassName != $this->ignoreClass
				) {
					$OrderItem->OrderVersion = $this->Version;
					$OrderItem->write();
				}
			}

			self::$update_versions = true;
		}
		return true;
	}

//===========================================================================================================================================================================================

	function OrderItemList() {
		$a = array();
		if($list = $this->OrderItems()) {
			foreach($list as $item) {
				$a[] = $item->Quantity . " x " . $item->Title();
			}
		}
		if(!count($a)) {
			return "No products listed";
		}
		if(count($a) == 1) {
			return "Product: ".implode(", ", $a).".";
		}
		return "Products: ".implode(", ", $a).".";
	}

	function getOrderItemList() {
		return $this->OrderItemList();
	}

//===========================================================================================================================================================================================

	function FirstOrderDate() {
		$a = $this->workOutSchedule();
		if(count($a)) {
			foreach($a as $k => $v) {
				return Date::create($className = "Date", $value = Date("Y-m-d", $k));
			}
		}
	}

	function getFirstOrderDate() {
		return $this->FirstOrderDate();
	}

	function LastOrderDate() {
		$a = $this->workOutSchedule();
		$today = strtotime(Date("Y-m-d"));
		$i = 0;
		if(count($a)) {
			foreach($a as $k => $v) {
				if($k > $today && $i > 0 && $previousK < $today) {
					return Date::create($className = "Date", $value = Date("Y-m-d", $previousK));
				}
				$previousK = $k;
				$i++;
			}
		}
	}

	function getLastOrderDate() {
		return $this->LastOrderDate();
	}

	function NextOrderDate() {
		$a = $this->workOutSchedule();
		$today = strtotime(Date("Y-m-d"));
		if(count($a)) {
			foreach($a as $k =>$v) {
				if($k > $today) {
					return Date::create($className = "Date", $value = Date("Y-m-d", $k));
				}
			}
		}
	}

	function getNextOrderDate() {
		return $this->NextOrderDate();
	}

	function FinalOrderDate() {
		$a = $this->workOutSchedule();
		if(count($a)) {
			foreach($a as $k =>$v) {
				//do nothing wait for last one...
			}
			if($k) {
				return Date::create($className = "Date", $value = Date("Y-m-d", $k));
			}
		}
	}

	function getFinalOrderDate() {
		return $this->FinalOrderDate();
	}

	function DeliverySchedule() {
		$a = $this->workOutSchedule();
		if(count($a)) {
			return implode("; ", $a);
		}
	}

	function getDeliverySchedule() {
		return $this->DeliverySchedule();
	}

	protected function workOutSchedule() {
		if(!isset(self::$schedule[$this->ID])) {
			$a = array();
			if($this->Period && $this->End && $this->Start && $this->DeliveryDay) {
				$startTime = strtotime($this->Start);
				if(Date("l", $startTime) == $this->DeliveryDay) {
					$firstTime = $startTime;
				}
				else {
					$phrase = "Next ".$this->DeliveryDay;
					$firstTime = strtotime($phrase, $startTime);
				}
				$endTime = strtotime($this->End);
				$nextTime = $firstTime;
				if($firstTime && $nextTime && $endTime) {
					if($firstTime < $endTime) {
						$i = 0;
						while($nextTime <= $endTime && $i < 999) {
							$a[$nextTime] = Date("j F Y", $nextTime);
							$nextTime = strtotime("+ ".$this->Period, $nextTime);
							$i++;
						}
					}
				}
			}
			self::$schedule[$this->ID] = $a;
		}
		return self::$schedule[$this->ID];
	}

}

class RepeatOrder_OrderItem extends DataObject {

	public static $db = array(
		'Quantity' => 'Int',
		'OrderVersion' => 'Int',
	);

	public static $has_one = array(
		'Product' => 'Product',
		'Order' => 'RepeatOrder'
	);

	public static $versioning = array(
		'Stage'
	);

	public static $extensions = array(
		"Versioned('Stage')"
	);

	public function Title() {
		return $this->Product()->Title;
	}

	public function BuyableTitle() {
		return $this->Product()->Title;
	}

	public function Link() {
		return $this->Product()->Link();
	}

	public function ProductVersion() {
		return $this->Product()->Version;
	}

	public function getProductID() {
		return $this->Product()->ID;
	}

	/**
	 * Complex Versioning accross more than one DataObject
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if(RepeatOrder::$update_versions == true) {
			if($this->ID) {
				$this->Order()->ignoreID = $this->ID;
				$this->Order()->ignoreClassName = $this->ClassName;
				$this->Order()->write();
				$this->OrderVersion = $this->Order()->Version;
			}
		}
	}

	public function onAfterWrite() {
		parent::onAfterWrite();

	}

	public function onBeforeDelete() {
		parent::onBeforeDelete();

		if(RepeatOrder::$update_versions == true) {
			if($this->ID) {
				$this->Order()->ignoreID = $this->ID;
				$this->Order()->ignoreClassName = $this->ClassName;
				$this->Order()->write();
				$this->OrderVersion = $this->Order()->Version;
			}
		}
	}
}
