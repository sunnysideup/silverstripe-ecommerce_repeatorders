<?php


class RepeatOrderForm extends Form
{
    public function __construct($controller, $name, $repeatOrderID = 0, $originatingOrder = 0)
    {
        $order = null;
        //create vs edit
        if ($repeatOrderID) {
            $repeatOrder = DataObject::get_one('RepeatOrder', "ID = ".$repeatOrderID);
            $items = $repeatOrder->OrderItems();
        } else {
            $repeatOrder = null;
            if ($originatingOrder) {
                $order = Order::get_by_id_if_can_view($originatingOrder);
            }
            if (!$order) {
                $order = ShoppingCart::current_order();
            }
            if ($order) {
                $items = $order->Items();
            } else {
                $items = null;
            }
        }

        //build fields
        $fields = FieldList::create();

        //products!
        if ($items) {
            $fields->push(HeaderField::create('ProductsHeader', 'Products'));
            $products = Product::get()->filter('Product', "\"AllowPurchase\" = 1");
            $productsMap = $products->map('ID', 'Title');
            $this->array_unshift_assoc($productsMap, 0, "--- Please select ---");
            foreach ($productsMap as $id => $title) {
                if ($product = DataObject::get_one('Product', ["ID" => $id])) {
                    if (!$product->canPurchase()) {
                        unset($productsMap[$id]);
                    }
                }
            }
            $j = 0;
            foreach ($items as $key => $item) {
                $j++;
                $alternativeItemsMap = $productsMap;
                $defaultProductID =  $item->ProductID ? $item->ProductID : $item->BuyableID;
                $itemID = $defaultProductID;
                unset($alternativeItemsMap[$defaultProductID]);
                $fields->push(DropdownField::create('Product[ID]['.$itemID.']', "Preferred Product #$j", $productsMap, $defaultProductID));
                $fields->push(NumericField::create('Product[Quantity]['.$itemID.']', " ... quantity", $item->Quantity));
                for ($i = 1; $i < 6; $i++) {
                    $alternativeField = "Alternative".$i."ID";
                    $fields->push(DropdownField::create('Product['.$alternativeField.']['.$itemID.']', " ... alternative $i", $alternativeItemsMap, (isset($item->$alternativeField) ? $item->$alternativeField : 0)));
                }
            }
        } else {
            $fields->push(HeaderField::create('items', 'There are no products in this repeating order'));
        }

        //other details
        $fields->push(HeaderField::create('DetailsHeader', 'Repeat Order Details'));
        $fields->push(
            ListboxField::create(
                'PaymentMethod',
                'Payment Method',
                Config::inst()->get('RepeatOrder', 'payment_methods'),
                null,
                Config::inst()->get('RepeatOrder', 'payment_methods')
            )
        );
        $startField = DateField::create('Start', 'Start Date');
        $startField->setConfig('showcalendar', true);
        $fields->push($startField);
        $endField = DateField::create('End', 'End Date');
        $endField->setConfig('showcalendar', true);
        $fields->push($endField);
        $fields->push(
            ListboxField::create(
                'Period',
                'Period',
                Config::inst()->get('RepeatOrder', 'period_fields'),
                null,
                count(Config::inst()->get('RepeatOrder', 'period_fields'))
            )
        );
        $fields->push(TextareaField::create('Notes', 'Notes'));

        //hidden field
        if (isset($order->ID)) {
            $fields->push(HiddenField::create('OrderID', 'OrderID', $order->ID));
        }
        if ($repeatOrder) {
            $fields->push(HiddenField::create('RepeatOrderID', 'RepeatOrderID', $repeatOrder->ID));
        }

        //actions
        $actions = FieldList::create();
        if ($repeatOrder) {
            $actions->push(FormAction::create('doSave', 'Save'));
        } else {
            $actions->push(FormAction::create('doCreate', 'Create'));
        }

        //required fields
        $requiredArray = array('Start', 'End', 'Period', 'DeliveryDay');
        $requiredFields = RequiredFields::create($requiredArray);

        //make form
        parent::__construct($controller, $name, $fields, $actions, $requiredFields);

        //load data
        if ($repeatOrder) {
            $this->loadDataFrom(array(
                'Start' => $repeatOrder->Start,
                'End' => $repeatOrder->End,
                'Period' => $repeatOrder->Period,
                'Notes' => $repeatOrder->Notes,
                'DeliveryDay' => $repeatOrder->DeliveryDay,
                'PaymentMethod' => $repeatOrder->PaymentMethod,
            ));
        }
    }

    public function doCreate($data, $form, $request)
    {
        return $this->doSave($data, $form, $request);
    }

    /**
     * Save the changes
     */
    public function doSave($data, $form, $request)
    {
        $data = Convert::raw2sql($data);
        $member = Member::currentUser();
        if (!$member) {
            $form->sessionMessage('Could not find customer details.', 'bad');
            Director::redirectBack();
            return false;
        }
        if ($member->IsShopAdmin()) {
            $form->sessionMessage('Repeat orders can not be created by Shop Administrators.  Only customers can create repeat orders.', 'bad');
            Director::redirectBack();
            return false;
        }
        if (isset($data['OrderID'])) {
            $order = DataObject::get_one('Order', 'Order.ID = \''.$data['OrderID'].'\' AND MemberID = \''.$member->ID.'\'');
            if ($order) {
                $repeatOrder = RepeatOrder::create_repeat_order_from_order($order);
            } else {
                $form->sessionMessage('Could not find originating order.', 'bad');
                Director::redirectBack();
                return false;
            }
        } else {
            $repeatOrderID = intval($data['RepeatOrderID']);
            $repeatOrder = DataObject::get_one('RepeatOrder', 'RepeatOrder.ID = \''.$repeatOrderID.'\' AND MemberID = \''.$member->ID.'\'');
        }
        if ($repeatOrder) {
            if ($repeatOrderItems = $repeatOrder->OrderItems()) {
                foreach ($repeatOrderItems as $repeatOrderItem) {
                    $repeatOrderItem->ProductID = $data["Product"]["ID"][$repeatOrderItem->ProductID];
                    $repeatOrderItem->Quantity = $data["Product"]["Quantity"][$repeatOrderItem->ProductID];
                    for ($i = 1; $i < 6; $i++) {
                        $alternativeField = "Alternative".$i."ID";
                        $repeatOrderItem->$alternativeField = $data["Product"][$alternativeField][$repeatOrderItem->ProductID];
                    }
                    $repeatOrderItem->write();
                }
            }
            $params = [];
            if (isset($data['Start']) && strtotime($data['Start']) > strtotime(Date("Y-m-d"))) {
                $params['Start'] = $data['Start'];
            } else {
                $params["Start"] = Date("Y-m-d");
            }
            if (isset($data['End'])  && strtotime($data['End']) > strtotime($params["Start"])) {
                $params['End'] = $data['End'];
            } else {
                $params["End"] = Date("Y-m-d", strtotime("+1 year"));
            }
            if (isset($data['Period'])) {
                $params['Period'] = $data['Period'];
            } else {
                $data['Period'] = RepeatOrder::default_period_key();
            }
            if (isset($data['DeliveryDay'])) {
                $params['DeliveryDay'] = $data['DeliveryDay'];
            } else {
                $data['DeliveryDay'] = RepeatOrder::default_delivery_day_key();
            }
            if (isset($data['PaymentMethod'])) {
                $params['PaymentMethod'] = $data['PaymentMethod'];
            } else {
                $data['PaymentMethod'] = RepeatOrder::default_payment_method_key();
            }
            if (isset($data['Notes'])) {
                $params['Notes'] = $data['Notes'];
            }
            $repeatOrder->update($params);
            $repeatOrder->Status = 'Pending';
            $repeatOrder->write();
        } else {
            $form->sessionMessage('Could not find repeat order.', 'bad');
            Director::redirectBack();
            return false;
        }
        Director::redirect(RepeatOrdersPage::get_repeat_order_link('view', $repeatOrder->ID));
        return true;
    }

    private function array_unshift_assoc(&$arr, $key, $val)
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        $arr = array_reverse($arr, true);
        return $arr;
    }
}
