<?php

/**
 * @author michael@ sunnysideup . co . nz
 */

class RepeatOrder extends DataObject
{


    #######################
    ### Names Section
    #######################

    private static $singular_name = 'Repeat Order';

    public function i18n_singular_name()
    {
        return _t(self::class.'.SINGULAR_NAME', 'Repeat Order');
    }

    private static $plural_name = 'Repeat Orders';

    public function i18n_plural_name()
    {
        return _t(self::class.'.PLURAL_NAME', 'Repeat Orders');
    }



    /**
     * Minimum of days in the future that the order is lodged.
     * @var int
     */
    private static $minimum_days_in_the_future = 1;

    /**
     * Standard SS variable
     */
    private static $db = [
        'Status' => 'Enum(\'Pending, Active, MemberCancelled, AdminCancelled, Finished\', \'Pending\')',
        //dates
        'Start' => 'Date',
        'End' => 'Date',
        'Period' => 'Varchar',
        //payment
        'PaymentMethod' => 'Varchar',
        'CreditCardOnFile' => 'Boolean',
        'PaymentNote' => 'Text',
        //computed values and notes
        'ItemsForSearchPurposes' => 'Text', //FOR SEARCH PURPOSES ONLY!
        'Notes' => 'Text'
    ];

    /**
     * Standard SS variable
     */
    private static $has_one = [
        'Member' => 'Member',
        'OriginatingOrder' => 'Order'
    ];


    /**
     * Standard SS variable
     */
    private static $has_many = [
        'OrderItems' => 'RepeatOrder_OrderItem', //products & quanitites
        'Orders' => 'Order'
    ];

    /**
     * Standard SS variable.
     */
    private static $indexes = [
        'Status' => true,
        'Start' => true,
        'End' => true,
        'Period' => true
    ];

    /**
     * Standard SS variable
     */
    private static $casting = [
        'OrderItemList' => 'Text',
        'FirstOrderDate' => 'Date',
        'LastOrderDate' => 'Date',
        'TodaysOrderDate' => 'Date',
        'NextOrderDate' => 'Date',
        'FinalOrderDate' => 'Date',
        'DeliverySchedule' => 'Text',
        'HasFutureOrders' => 'Boolean'
    ];

    #######################
    ### Field Names and Presentation Section
    #######################

    /**
     * Standard SS variable
     */
    private static $searchable_fields = [
        'Status' => 'ExactMatchFilter',
        'Period' => 'PartialMatchFilter',
        //payment
        'PaymentMethod' => 'PartialMatchFilter',
        'CreditCardOnFile' => 'ExactMatchFilter',
        'PaymentNote' => 'PartialMatchFilter',
        //computed values and notes
        'Notes' => 'PartialMatchFilter',
        'ItemsForSearchPurposes' => 'PartialMatchFilter',
    ];

    /**
     * Standard SS variable
     */
    private static $summary_fields = [
        'ID' => 'Repeat Order ID',
        'Member.Surname' => 'Surname',
        'Member.Email' => 'Email',
        'HasFutureOrders.Nice' => 'Future Orders',
        'OrderItemList' => 'Order Item List',
        'Start' => 'Start',
        'End' => 'End',
        'Period' => 'Period',
        'Status' => 'Status'
    ];



    private static $field_labels = [
        'Status' => 'Status',
        //dates
        'Start' => 'Start Date',
        'End' => 'End Date',
        'Period' => 'Repeat Schedule',
        //payment
        'PaymentMethod' => 'Payment Type',
        'CreditCardOnFile' => 'Credit Card on File',
        'PaymentNote' => 'Payment Notes',
        'Notes' => 'Notes'
    ];



    /**
     * Standard SS variable
     */
    private static $default_sort = 'Created DESC';


    /**
     * Dropdown options for Period
     * @var array 'strtotime period' > 'nice name'
     */
    private static $period_fields = [
        '1 day' => 'Daily',
        '1 week' => 'Weekly',
        '2 weeks' => 'Fornightly',
        '1 month' => 'Monthly'
    ];

    /**
     * @return string
     */
    public static function default_period_key()
    {
        if ($a = Config::inst()->get('RepeatOrder', 'period_fields')) {
            foreach ($a as $k => $v) {
                return $k;
            }
        }

        return '';
    }


    /**
     * @var array
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
    protected static $payment_methods = array(
        'DirectCreditPayment' => 'Direct Credit (payment into bank account)'
    );

    /**
     *
     * @return string
     */
    public static function default_payment_method_key()
    {
        $a = Config::inst()->get('RepeatOrder', 'payment_methods');
        foreach ($a as $k => $v) {
            return $k;
        }
        return '';
    }

    /**
     * Can it be edited, alias for canEdit
     *
     * @return bool
     */
    public function canModify($member = null)
    {
        return $this->canEdit();
    }

    /**
     * Link for viewing
     * @return string
     */
    public function Link()
    {
        return RepeatOrdersPage::get_repeat_order_link('view', $this->ID);
    }

    /**
     * Link for editing
     * @return string
     */
    public function ModifyLink()
    {
        return RepeatOrdersPage::get_repeat_order_link('modify', $this->ID);
    }


    /**
     * Link for cancelling
     * @return string
     */
    public function CancelLink()
    {
        return RepeatOrdersPage::get_repeat_order_link('cancel', $this->ID);
    }

    /**
     * Link for end of view / edit / cancel session
     * @return string
     */
    public function DoneLink()
    {
        $checkoutPage = DataObject::get_one('CheckoutPage');
        if($checkoutPage) {
            return $checkoutPage->Link('checkoutstep/orderconfirmationandpayment');
        } else {
            $page = DataObject::get_one("RepeatOrdersPage");
            if (!$page) {
                $page = DataObject::get_one("CheckoutPage");
                if (!$page) {
                    $page = DataObject::get_one("Page");
                }
            }
            return $page->Link();
        }
    }

    /**
     * returns a list of actual orders that have been created from this repeat order.
     * @return ArrayList|null
     */
    public function AutomaticallyCreatedOrders()
    {
        $orders = Order::get()->filter(["RepeatOrderID" => $this->ID])->sort(["OrderDate" => "ASC"]);
        $dos = ArrayList::create();
        if ($orders) {
            foreach ($orders as $order) {
                $dos->push($order);
            }
        }
        if ($dos && $dos->count()) {
            return $dos;
        }
    }

    //===================================================
    //===================================================
    //===================================================

    /**
     * Create due draft orders
     */
    public static function create_automatically_created_orders()
    {
        set_time_limit(600); //might take a while with lots of orders
        //get all Repeat orders
        $repeatOrders = RepeatOrder::get()->filter(['Status' => 'Active']);
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
        } else {
            $this->Status = 'Pending';
            $this->write();

            return;
        }

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
                        continue;
                    } elseif (!$orderDateInteger) {
                        continue;
                    } elseif ($orderDateInteger <= $today) {
                        $this->createOrderFromRepeatOrder($orderDateInteger);
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
        $filter = ["OrderDateInteger" => $orderDateInteger, "RepeatOrderID" => $this->ID];
        $newOrder = Order::get()
            ->filter($filter)
            ->first();
        if ($newOrder) {
            //do nothing
        } else {
            $originatingOrder = $this->OriginatingOrder();
            if($originatingOrder && $originatingOrder->exists()) {
                $shoppingCart = ShoppingCart::singleton();
                $newOrder = Order::create($filter);
                $newOrder->OrderDate = date("Y-m-d", $orderDateInteger);
                $newOrder->MemberID = $this->MemberID;
                $newOrder->CustomerOrderNote = "Created as part of a repeating order.";
                $newOrder = $shoppingCart->CopyOrderOnly($originatingOrder, $newOrder);
                //load the order
                $newOrder->write();
                if ($this->OrderItems()) {
                    $buyables = [];
                    foreach ($this->OrderItems() as $repeatOrderOrderItem) {
                        $product = Product::get()->byID($repeatOrderOrderItem->ProductID);
                        if ($product) {
                            $buyables[] = $product;
                        }
                    }
                    if(count($buyables)) {
                        $newOrder = $shoppingCart->CopyBuyablesToNewOrder($newOrder, $buyables);
                    }
                }
            }
            //FINALISE!!!
            $newOrder->write();
            $newOrder->tryToFinaliseOrder();
        }
    }


/**
     * Create a RepeatOrder from a regular Order and its Order Items
     * @param Order $order
     * @return RepeatOrder
     */
    public static function create_repeat_order_from_order(Order $order)
    {
        if($order->MemberID) {
            $repeatOrder = RepeatOrder::create();
            $repeatOrder->OriginatingOrderID = $order->ID;
            $repeatOrder->Status = 'Pending';
            $repeatOrder->MemberID = $order->MemberID;
            $repeatOrder->write();
            $orderItems = $order->Items();
            if ($orderItems) {
                foreach ($orderItems as $orderItem) {
                    $buyable = $orderItem->Buyable();
                    if ($buyable && $buyable instanceof Product) {
                        $repeatOrder_orderItem = RepeatOrder_OrderItem::create();
                        $repeatOrder_orderItem->OrderID = $repeatOrder->ID;
                        $repeatOrder_orderItem->ProductID = $orderItem->BuyableID;
                        $repeatOrder_orderItem->Quantity = $orderItem->Quantity;
                        $repeatOrder_orderItem->write();
                    }
                }
            }
            $repeatOrder->write();

            return $repeatOrder;
        } else {
            user_error('No member for the order.');
        }
    }






    /**
     * @return string
     */
    public function TablePaymentMethod()
    {
        $methods = Config::inst()->get('RepeatOrder', 'payment_methods');
        if (isset($methods[$this->PaymentMethod])) {
            return $methods[$this->PaymentMethod];
        }
        return '';
    }

    /**
     * @return string
     */
    public function TableStatus()
    {
        $status = Config::inst()->get('RepeatOrder', 'status_nice');
        if (isset($status[$this->Status])) {
            return $status[$this->Status];
        }
        return '';
    }


//==========================================================================================================================================================



    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

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


        $fields->replaceField(
            'PaymentMethod',
            DropdownField::create(
                'PaymentMethod',
                'Payment Method',
                Config::inst()->get('RepeatOrder', 'payment_methods')
            )
        );
        $fields->replaceField(
            'Period',
            DropdownField::create(
                'Period',
                'Period',
                Config::inst()->get('RepeatOrder', 'period_fields')
            )
        );
        $fields->replaceField(
            'Status',
            DropdownField::create(
                'Status',
                'Status',
                Config::inst()->get('RepeatOrder', 'status_nice')
            )
        );

        $fields->addFieldsToTab(
            'Root.Details',
            [
                LiteralField::create(
                    'Readonly[ID]',
                    '<p>Repeat Order Number: '.$this->ID.'</p>'
                ),
                LiteralField::create(
                    'Readonly[Member]',
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
            ]
        );
        $fields->addFieldsToTab(
            'Root.Orders',
            [
                ReadonlyField::create("DeliveryScheduleFormatted", "Delivery Schedule", $this->DeliverySchedule()),
                ReadonlyField::create("FirstCreatedFormatted", "First Order", $firstCreated),
                ReadonlyField::create("LastCreatedFormatted", "Last Order", $lastCreated),
                ReadonlyField::create("NextCreatedFormatted", "Next Order", $nextCreated),
                ReadonlyField::create("FinalCreatedFormatted", "Final Order", $finalCreated)
            ]
        );


        return $fields;
    }


    //============
    //============
    //============

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->ItemsForSearchPurposes = $this->OrderItemList();
    }


    //============
    //============
    //============

    /**
     * List of products
     *
     * @return string
     */
    public function OrderItemList()
    {
        return $this->getOrderItemList();
    }
    /**
     *
     * @return string
     */
    public function getOrderItemList()
    {
        $a = [];
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
     * @return Date|null
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
     * @return Date|null
     */
    public function LastOrderDate()
    {
        return $this->getLastOrderDate();
    }

    /**
     * Last date that an order was placed
     *
     * @return Date|null
     */
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
     * @return Date|null
     */
    public function TodaysOrderDate()
    {
        return $this->getTodaysOrderDate();
    }

    /**
     * today's' date for the order - if ANY!
     *
     * @return Date|null
     */
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
     * @return Date|null
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
     * @return Date|null
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
     * @return string
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
     * @var Array
     */
    private static $_schedule = [];

    /**
     * Work out the delivery schedule
     * @return Array
     */
    protected function workOutSchedule()
    {
        //caching value for quicker response
        if (!isset(self::$_schedule[$this->ID])) {
            $a = [];
            if ($this->Period && $this->End && $this->Start &&  $this->Status == "Active") {
                $startTime = strtotime($this->Start);
                $firstTime = $startTime;
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
            self::$_schedule[$this->ID] = $a;
        }
        return self::$_schedule[$this->ID];
    }


    /**
     * Are there any orders scheduled for the future
     * @return bool
     */
    public function HasFutureOrders()
    {
        return $this->getHasFutureOrders();
    }

    /**
     * Are there any orders scheduled for the future
     * @return bool
     */
    public function getHasFutureOrders()
    {
        if ($this->NextOrderDate()) {
            return true;
        }

        return false;
    }

    /**
     * Are there any orders scheduled for today
     * @return bool
     */
    public function HasAnOrderToday()
    {
        if ($this->TodaysOrderDate()) {
            return true;
        }
        return false;
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
        if (in_array($this->Status, ['Pending', 'Active'])) {
            return $this->canView($member);
        } else {
            return false;
        }
    }

    public function canDelete($member = null)
    {
        if (in_array($this->Status, ['Pending'])) {

            return $this->canView($member);
        } else {

            return false;
        }
    }
}
