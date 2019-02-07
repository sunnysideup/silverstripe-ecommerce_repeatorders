<?php

/**
 * @author michael@ sunnysideup . co . nz
 */

class RepeatOrder extends DataObject
{


    /**
     * Minimum of days in the future that the order is lodged.
     * @var int
     */
    private static $minimum_days_in_the_future = 1;

    /**
     * Standard SS variable
     */
    private static $db = array(
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
        'ItemsForSearchPurposes' => 'Text', //FOR SEARCH PURPOSES ONLY!
        'Notes' => 'Text'
    );

    /**
     * Standard SS variable
     */
    private static $has_one = array(
        'Member' => 'Member',
        'OriginatingOrder' => 'Order'
    );


    /**
     * Standard SS variable
     */
    private static $has_many = array(
        'OrderItems' => 'RepeatOrder_OrderItem', //products & quanitites
        'Orders' => 'Order'
    );

    /**
     * Standard SS variable.
     */
    private static $indexes = array(
        "Status" => true
    );

    /**
     * Standard SS variable
     */
    private static $casting = array(
        "OrderItemList" => "Text",
        "FirstOrderDate" => "Date",
        "LastOrderDate" => "Date",
        "TodaysOrderDate" => "Date",
        "NextOrderDate" => "Date",
        "FinalOrderDate" => "Date",
        "DeliverySchedule" => "Text"
    );


    /**
     * Standard SS variable
     */
    private static $searchable_fields = array(
        "ItemsForSearchPurposes" => "PartialMatchFilter",
        "Period" => "ExactMatchFilter",
        "DeliveryDay" => "ExactMatchFilter",
        "Status" => "ExactMatchFilter"
    );

    /**
     * Standard SS variable
     */
    private static $summary_fields = array(
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

    /**
     * Standard SS variable
     */
    private static $default_sort = 'Created DESC';


    /**
     * Dropdown options for Period
     * @var array 'strtotime period' > 'nice name'
     */
    private static $period_fields = array(
        '1 day' => 'Daily',
        '1 week' => 'Weekly',
        '2 weeks' => 'Fornightly',
        '1 month' => 'Monthly'
    );
    public static function set_period_fields($array)
    {
        self::$period_fields = $array;
    }
    public static function get_period_fields()
    {
        return self::$period_fields;
    }
    public static function default_period_key()
    {
        if ($a = self::get_period_fields()) {
            foreach ($a as $k => $v) {
                return $k;
            }
        }
    }

    /**
     * @var Array
     */
    protected static $schedule = array();


    /**
     * @array
     */
    private static $status_nice = array(
        'Pending' => 'Pending',
        'Active' => 'Active',
        'MemberCancelled' => 'Pending Cancellation',
        'AdminCancelled' => 'Cancelled',
        'Finished' => 'Finished',
    );

    /**
     * @var array
     */
    private static $delivery_days = array(
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    );
    private static function set_delivery_days($a)
    {
        self::$delivery_days = $a;
    }
    private static function get_delivery_days()
    {
        $array = array();
        $page = DataObject::get_one("RepeatOrdersPage");
        if ($page) {
            $array = explode(",", $page->OrderDays);
        }
        if (count($array)) {
            return $array;
        } else {
            return self::$delivery_days;
        }
    }
    public static function default_delivery_day_key()
    {
        $a = self::get_delivery_days();
        if (count($a)) {
            foreach ($a as $k => $v) {
                return $k;
            }
        }
    }

    /**
     * @var array
     */
    protected static $payment_methods = array(
        'DirectCreditPayment' => 'Direct Credit (payment into bank account)'
    );
    public static function set_payment_methods($a)
    {
        self::$payment_methods = $a;
    }
    public static function get_payment_methods()
    {
        return self::$payment_methods;
    }
    public static function default_payment_method_key()
    {
        $a = self::get_payment_methods();
        foreach ($a as $k => $v) {
            return $k;
        }
    }

    /**
     * Can it be edited, alias for canEdit
     * @return Boolean
     */
    public function CanModify($member = null)
    {
        return $this->canEdit();
    }

    /**
     * Link for viewing
     * @return String
     */
    public function Link()
    {
        return RepeatOrdersPage::get_repeat_order_link('view', $this->ID);
    }

    /**
     * Link for editing
     * @return String
     */
    public function ModifyLink()
    {
        return RepeatOrdersPage::get_repeat_order_link('modify', $this->ID);
    }


    /**
     * Link for cancelling
     * @return String
     */
    public function CancelLink()
    {
        return RepeatOrdersPage::get_repeat_order_link('cancel', $this->ID);
    }

    /**
     * Link for end of view / edit / cancel session
     * @return String
     */
    public function DoneLink()
    {
        $page = DataObject::get_one("RepeatOrdersPage");
        if (!$page) {
            $page = DataObject::get_one("CheckoutPage");
            if (!$page) {
                $page = DataObject::get_one("Page");
            }
        }
        return $page->Link();
    }

    /**
     * returns a list of actual orders that have been created from this repeat order.
     * @return DOS | Null
     */
    public function AutomaticallyCreatedOrders()
    {
        $orders = DataObject::get("Order", "RepeatOrderID = ".$this->ID, "OrderDate ASC");
        $dos = new DataObjectSet();
        if ($orders) {
            foreach ($orders as $order) {
                $dos->push($order);
            }
        }
        if ($dos && $dos->count()) {
            return $dos;
        }
    }

//====================================================================================================================================================================================

    /**
     * Create due draft orders
     */
    public static function create_automatically_created_orders()
    {
        set_time_limit(0); //might take a while with lots of orders
        //get all Repeat orders
        $repeatOrders = DataObject::get('RepeatOrder', 'Status = \'Active\'');
        if ($repeatOrders) {
            foreach ($repeatOrders as $repeatOrder) {
                $repeatOrder->addAutomaticallyCreatedOrders();
            }
        }
    }


    /**
     * adds the orders that
     */
    public function addAutomaticallyCreatedOrders()
    {
        //current time + period is less than LastCreated and less then end
        $today = (strtotime(date('Y-m-d')));
        $firstOrderDate = $this->FirstOrderDate();
        if ($firstOrderDate) {
            $startTime = strtotime($firstOrderDate->format("Y-m-d"));
        }
        if (!$firstOrderDate) {
            $this->Status = 'Pending';
            $this->write();
            return;
        } else {
            $endTime = strtotime($this->dbObject("End")->format("Y-m-d"));
            if ($today > $endTime) {
                $this->Status = 'Finished';
                $this->write();
                return;
            } elseif ($startTime < $today) {
                $a = $this->workOutSchedule();
                if (count($a)) {
                    foreach ($a as $orderDateInteger => $orderDateLong) {
                        if (!$this->MemberID) {
                            USER_ERROR("Can not create Order without member linked in RepeatOrder #".$this->ID, E_USER_ERROR);
                        } elseif (!$orderDateInteger) {
                            USER_ERROR("Can not create Order without date for in RepeatOrder #".$this->ID, E_USER_ERROR);
                        } elseif ($orderDateInteger <= $today) {
                            $this->createOrderFromRepeatOrder($orderDateInteger);
                        }
                    }
                }
            }
        }
    }


    /**
     * creates order from repeatorder for a specific day.
     * IF it does not already exists.
     *
     */
    protected function createOrderFromRepeatOrder($orderDateInteger)
    {
        if ($order = DataObject::get_one("Order", "\"OrderDateInteger\" = '".$orderDateInteger."' AND \"RepeatOrderID\" = ".$this->ID)) {
            //do nothing
        } else {
            $order = new Order();
            $order->OrderDate = date("Y-m-d", $orderDateInteger);
            $order->OrderDateInteger = $orderDateInteger;
            $order->RepeatOrderID = $this->ID;
            $order->MemberID = $this->MemberID;
            $order->CustomerOrderNote = "Created as part of a repeating order.";
            $order->write();
            if ($this->OrderItems()) {
                foreach ($this->OrderItems() as $repeatOrderOrderItem) {
                    $product = DataObject::get_by_id('Product', $repeatOrderOrderItem->ProductID);
                    if ($product) {
                        //START CHECK AVAILABILITY
                        if (class_exists("ProductStockCalculatedQuantity")) {
                            $numberAvailable = ProductStockCalculatedQuantity::get_quantity_by_product_id($product->ID);
                            if ($numberAvailable < $repeatOrderOrderItem->Quantity) {
                                $alternatives = $repeatOrderOrderItem->AlternativesPerProduct();
                                $product = null;
                                if ($dos) {
                                    foreach ($alternatives as $alternative) {
                                        $stillLookingForAlternative = true;
                                        $numberAvailable = ProductStockCalculatedQuantity::get_quantity_by_product_id($alternative->ID);
                                        if ($numberAvailable > $repeatOrderOrderItem->Quantity && $stillLookingForAlternative) {
                                            $stillLookingForAlternative = false;
                                            $product = $alternative;
                                        }
                                    }
                                }
                            }
                        }
                        //END CHECK AVAILABILITY
                        if ($product) {
                            $newProductOrderItem = new Product_OrderItem();
                            $newProductOrderItem->addBuyableToOrderItem($product, $repeatOrderOrderItem->Quantity);
                            $newProductOrderItem->OrderID = $order->ID;
                            $newProductOrderItem->write();
                        }
                    } else {
                        USER_ERROR("Product does not exist", E_USER_WARNING);
                    }
                }
            } else {
                USER_ERROR("There are no order items", E_USER_WARNING);
            }
            //FINALISE!!!
            $order->write();
            $order->tryToFinaliseOrder();
        }
    }


/**
     * Create a RepeatOrder from a regular Order and its Order Items
     * @param Order $Order
     * @return RepeatOrder
     */
    public static function create_repeat_order_from_order(Order $Order)
    {
        $repeatOrder = new RepeatOrder();
        $repeatOrder->Status = 'Pending';
        $repeatOrder->MemberID = $Order->MemberID;
        $repeatOrder->write();
        $orderItems = $Order->Items();
        if ($orderItems) {
            foreach ($orderItems as $orderItem) {
                $buyable = $orderItem->Buyable();
                if ($buyable && $buyable instanceof Product) {
                    $repeatOrder_orderItem = new RepeatOrder_OrderItem();
                    $repeatOrder_orderItem->OrderID = $repeatOrder->ID;
                    $repeatOrder_orderItem->ProductID = $orderItem->BuyableID;
                    $repeatOrder_orderItem->Quantity = $orderItem->Quantity;
                    $repeatOrder_orderItem->write();
                }
            }
        }
        $repeatOrder->write();
        return $repeatOrder;
    }





//================================================================================================================================================================================

    /**
     * @return string
     */
    public function TableDeliveryDay()
    {
        return $this->DeliveryDay;
    }

    /**
     * @return string
     */
    public function TablePaymentMethod()
    {
        if (isset(self::$payment_methods[$this->PaymentMethod])) {
            return self::$payment_methods[$this->PaymentMethod];
        }
        return "";
    }

    /**
     * @return string
     */
    public function TableStatus()
    {
        return self::$status_nice[$this->Status];
    }


//==========================================================================================================================================================



    /**
     * CMS Fields for ModelAdmin, use different fields for adding/editing
     * @see sapphire/core/model/DataObject#getCMSFields($params)
     */
    public function getCMSFields()
    {
        if ($this->exists()) {
            $this->addAutomaticallyCreatedOrders();
            return $this->getCMSFields_edit();
        } else {
            return $this->getCMSFields_add();
        }
    }

    /**
     * CMS Fields to adding via ModelAdmin
     * @return FieldList
     */
    public function getCMSFields_add()
    {
        $fields = new FieldList(
            new TabSet('Root',
                new Tab('Main',
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
     * @return FieldList
     */
    public function getCMSFields_edit()
    {
        $firstCreated = "can not be computed";
        if ($firstCreatedObj = $this->FirstOrderDate()) {
            $firstCreated = $firstCreatedObj->Long();
        }
        $lastCreated = "no orders have been placed yet";
        if ($lastCreatedObj = $this->LastOrderDate()) {
            $lastCreated = $lastCreatedObj->Long();
        }
        $finalCreated = "can not be computed";
        if ($finalCreatedObj = $this->FinalOrderDate()) {
            $finalCreated = $finalCreatedObj->Long();
        }
        if ($this->Status == "Active") {
            $nextCreated = "can not be computed";
            if ($nextCreatedObj = $this->NextOrderDate()) {
                $nextCreated = $nextCreatedObj->Long();
            }
        } else {
            $nextCreated = "Repeat Order not active - no next date is available if the Repeat Order is not active.";
        }
        if (!$this->DeliveryDay) {
            $firstCreated = $finalCreated = $lastCreated = $nextCreated = "Please select a delivery day first.";
        }
        $fields = new FieldList(
            new TabSet('Root',
                new Tab('Main',
                    new LiteralField('Readonly[ID]', '<p>Repeat Order Number: '.$this->ID.'</p>'),
                    new LiteralField('Readonly[Member]',
<<<HTML
    <div class="field readonly " id="Readonly[Member]">
        <label for="Form_EditForm_Readonly-Member" class="left">Member</label>
        <div class="middleColumn">
            <span class="readonly" id="Form_EditForm_Readonly-Member">{$this->Member()->getTitle()} ({$this->Member()->Email}) </span>
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
        return $fields;
    }

    /**
     * CMS Fields for Popup
     * @return FieldList
     */
    public function getCMSFields_forPopup()
    {
        $fields = new FieldList(
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
                    $this->getCMSProductsTable()
                )
            )
        );
        return $fields;
    }



    /**
     * Get previous actual order table
     * @return ComplexTableField
     */
    public function getCMSPreviousOrders()
    {
        $table = new ComplexTableField(
            $controller = $this,
            $name = "PreviousOrders",
            $sourceClass = "Order",
            $fieldList = array(
                "Title" => "Summary",
                "Total" => "Total",
                "CustomerStatus" => "Status",
                "OrderDate" => "Planned Date",
                "RetrieveLink" => "RetrieveLink"
            ),
            $detailFormFields = null,
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
        $table->setPermissions(array("export", "show"));
        return $table;
    }

    /**
     * Get products table
     * @return ComplexTableField
     */
    public function getCMSProductsTable()
    {
        $table = new ComplexTableField(
            $this,
            'OrderItems',
            'RepeatOrder_OrderItem',
            array(
                'Product.Title' => 'Title',
                'Quantity' => 'Qty',
            )
        );

        $table->setShowPagination(false);
        $table->setAddTitle('Product');
        $table->setPermissions(array('add', 'edit', 'delete'));

        return $table;
    }




//========================================================================================================================================================================================================================================================================

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->ItemsForSearchPurposes = $this->OrderItemList();
    }

//===========================================================================================================================================================================================

    /**
     * List of products
     *
     * @return String
     */
    public function OrderItemList()
    {
        return $this->getOrderItemList();
    }
    public function getOrderItemList()
    {
        $a = array();
        if ($list = $this->OrderItems()) {
            foreach ($list as $item) {
                $a[] = $item->Quantity . " x " . $item->Title();
            }
        }
        if (!count($a)) {
            return "No products listed";
        }
        if (count($a) == 1) {
            return "Product: ".implode(", ", $a).".";
        }
        return "Products: ".implode(", ", $a).".";
    }

//===========================================================================================================================================================================================

    /**
     * The first order date
     *
     * @return Date | Null
     */
    public function FirstOrderDate()
    {
        return $this->getFirstOrderDate();
    }
    public function getFirstOrderDate()
    {
        $a = $this->workOutSchedule();
        if (count($a)) {
            foreach ($a as $orderDateInteger => $orderDateLong) {
                return Date::create($className = "Date", $value = Date("Y-m-d", $orderDateInteger));
            }
        }
    }

    /**
     * Last date that an order was placed
     *
     * @return Date | Null
     */
    public function LastOrderDate()
    {
        return $this->getLastOrderDate();
    }
    public function getLastOrderDate()
    {
        $a = $this->workOutSchedule();
        $today = strtotime(Date("Y-m-d"));
        $i = 0;
        if (count($a)) {
            foreach ($a as $orderDateInteger => $orderDateLong) {
                if ($orderDateInteger > $today && $i > 0 && $previousoOrderDateInteger < $today) {
                    return Date::create($className = "Date", $value = Date("Y-m-d", $previousoOrderDateInteger));
                }
                $previousoOrderDateInteger = $orderDateInteger;
                $i++;
            }
        }
    }

    /**
     * today's' date for the order - if ANY!
     *
     * @return Date | Null
     */
    public function TodaysOrderDate()
    {
        return $this->getTodaysOrderDate();
    }
    public function getTodaysOrderDate()
    {
        $a = $this->workOutSchedule();
        $today = strtotime(Date("Y-m-d"));
        if (count($a)) {
            foreach ($a as $orderDateInteger => $orderDateLong) {
                if ($orderDateInteger == $today) {
                    return Date::create($className = "Date", $value = Date("Y-m-d", $orderDateInteger));
                }
            }
        }
    }

    /**
     * Next date (from the viewpoint of today)
     *
     * @return Date | Null
     */
    public function NextOrderDate()
    {
        return $this->getNextOrderDate();
    }
    public function getNextOrderDate()
    {
        $a = $this->workOutSchedule();
        $today = strtotime(Date("Y-m-d"));
        if (count($a)) {
            foreach ($a as $orderDateInteger => $orderDateLong) {
                if ($orderDateInteger > $today) {
                    return Date::create($className = "Date", $value = Date("Y-m-d", $orderDateInteger));
                }
            }
        }
    }


    /**
     * Last Delivery Date
     *
     * @return Date | Null
     */
    public function FinalOrderDate()
    {
        return $this->getFinalOrderDate();
    }
    public function getFinalOrderDate()
    {
        $a = $this->workOutSchedule();
        if (count($a)) {
            foreach ($a as $orderDateInteger => $orderDateLong) {
                //do nothing wait for last one...
            }
            if ($orderDateInteger) {
                return Date::create($className = "Date", $value = Date("Y-m-d", $orderDateInteger));
            }
        }
    }

    /**
     * List of delivery dates
     *
     * @return String
     */
    public function DeliverySchedule()
    {
        return $this->getDeliverySchedule();
    }
    public function getDeliverySchedule()
    {
        $a = $this->workOutSchedule();
        if (count($a)) {
            return implode("; ", $a);
        }
    }


    /**
     * Work out the delivery schedule
     * @return Array
     */
    protected function workOutSchedule()
    {
        //caching value for quicker response
        if (!isset(self::$schedule[$this->ID])) {
            $a = array();
            if ($this->Period && $this->End && $this->Start && $this->DeliveryDay && $this->Status == "Active") {
                $startTime = strtotime($this->Start);
                if (Date("l", $startTime) == $this->DeliveryDay) {
                    $firstTime = $startTime;
                } else {
                    $phrase = "Next ".$this->DeliveryDay;
                    $firstTime = strtotime($phrase, $startTime);
                }
                $endTime = strtotime($this->End);
                $nextTime = $firstTime;
                if ($firstTime && $nextTime && $endTime) {
                    if ($firstTime < $endTime) {
                        $i = 0;
                        while ($nextTime <= $endTime && $i < 999) {
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


    /**
     * Are there any orders scheduled for the future
     * @return Boolean
     */
    public function HasFutureOrders()
    {
        if ($this->NextOrderDate()) {
            return true;
        }
    }

    /**
     * Are there any orders scheduled for today
     * @return Boolean
     */
    public function HasAnOrderToday()
    {
        if ($this->TodaysOrderDate()) {
            return true;
        }
    }

//===========================================================================================================================================================================================

    public function canView($member = null)
    {
        $member = Member::currentUser();
        if ($member) {
            if ($member->IsShopAdmin()) {
                return true;
            }
            if ($this->MemberID == $member->ID) {
                return true;
            }
        }
        return false;
    }

    public function canEdit($member = null)
    {
        if (in_array($this->Status, array('Pending', 'Active'))) {
            return $this->canView($member);
        } else {
            return false;
        }
    }

    public function canDelete($member = null)
    {
        if (in_array($this->Status, array('Pending'))) {
            return $this->canView($member);
        } else {
            return false;
        }
    }
}
