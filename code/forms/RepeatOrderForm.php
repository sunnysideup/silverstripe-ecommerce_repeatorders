<?php


class RepeatOrderForm extends Form
{

    private static $number_of_product_alternatives = 0;

    public function __construct($controller, $name, $repeatOrderID = 0, $originatingOrder = 0)
    {

        if ($repeatOrderID) {
            $repeatOrder = DataObject::get_one('RepeatOrder', "ID = ".$repeatOrderID);
            $items = $repeatOrder->OrderItems();
        } else {
            $repeatOrder = null;
        }

        $fields = RepeatOrderForm::repeatOrderFormFields($repeatOrderID, $originatingOrder);

        $actions = RepeatOrderForm::repeatOrderFormActions($repeatOrder);

        //required fields
        $requiredArray = array('Start', 'Period');
        $requiredFields = RequiredFields::create($requiredArray);

        //make form
        parent::__construct($controller, $name, $fields, $actions, $requiredFields);
        //load data
        if ($repeatOrder) {
            $this->loadDataFrom(
                [
                    'Start' => $repeatOrder->Start,
                    'End' => $repeatOrder->End,
                    'Period' => $repeatOrder->Period,
                    'Notes' => $repeatOrder->Notes,
                    'PaymentMethod' => $repeatOrder->PaymentMethod,
                ]
            );
        }
    }

    public static function repeatOrderFormFields($repeatOrderID = 0, $originatingOrder = 0, $isCheckout = false)
    {
        $order = null;
        $repeatOrder = null;
        //create vs edit
        if ($repeatOrderID) {
            $repeatOrder = DataObject::get_one('RepeatOrder', "ID = ".$repeatOrderID);
            $items = $repeatOrder->OrderItems();
        }

        if(is_null($repeatOrder) || $isCheckout){
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
            // $fields->push(HeaderField::create('ProductsHeader', 'Products'));
            $products = Product::get()->filter(["AllowPurchase" => 1]);
            $productsMap = $products->map('ID', 'Title')->toArray();
            $arr1 = [0 => "--- Please select ---"] + $productsMap;
            foreach ($productsMap as $id => $title) {
                if ($product = Product::get()->byID($id)) {
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
                $fields->push(
                    HiddenField::create(
                        'Product[ID]['.$itemID.']',
                        "Preferred Product #$j",
                        $defaultProductID
                    )
                );
                $fields->push(
                    HiddenField::create(
                        'Product[Quantity]['.$itemID.']',
                        " ... quantity",
                        $item->Quantity
                    )
                );
                $altCount = Config::inst()->get('RepeatOrderForm', 'number_of_product_alternatives');
                if($altCount > 9) {
                    user_error('You can only have up to nine alternatives buyables');
                } elseif($altCount > 0) {
                    for ($i = 1; $i <= $altCount; $i++) {
                        $alternativeField = "Alternative".$i."ID";
                        $fields->push(
                            DropdownField::create(
                                'Product['.$alternativeField.']['.$itemID.']',
                                " ... alternative $i",
                                $alternativeItemsMap,
                                (isset($item->$alternativeField) ? $item->$alternativeField : 0)
                            )
                        );
                    }
                }
            }
        } else {
            $fields->push(HeaderField::create('items', 'There are no products in this repeating order'));
        }

        //other details
        $fields->push(
            HeaderField::create(
                'DetailsHeader',
                'Repeat Order Details'
            )
        );
        $fields->push(
            DropdownField::create(
                'PaymentMethod',
                'Payment Method',
                Config::inst()->get('RepeatOrder', 'payment_methods'),
                $repeatOrder ? $repeatOrder->PaymentMethod : ''
            )
        );

        $startField = DateField::create(
            'Start',
            'Start Date',
            $repeatOrder ? $repeatOrder->Start : date('d-m-Y')
        );
        $startField->setAttribute('autocomplete', 'off');
        $startField->setConfig('showcalendar', true);
        $fields->push($startField);

        $endField = DateField::create(
            'End',
            'End Date',
            $repeatOrder ? $repeatOrder->End : ''
        );
        $endField->setAttribute('autocomplete', 'off');
        $endField->setConfig('showcalendar', true);
        $fields->push($endField);

        $fields->push(
            DropdownField::create(
                'Period',
                'Period',
                Config::inst()->get('RepeatOrder', 'period_fields'),
                $repeatOrder ? $repeatOrder->Period : ''
            )
        );

        $fields->push(
            TextareaField::create(
                'Notes',
                'Notes',
                $repeatOrder ? $repeatOrder->Notes : ''
            )
        );

        $repeatOrderFormLink = RepeatOrdersPage::get_repeat_order_link('ajaxcreateorder', $order ? $order->ID : 0);
        $fields->push(
            HiddenField::create('AjaxSubmissionLink', 'AjaxSubmissionLink', $repeatOrderFormLink)
        );

        //hidden field
        if (isset($order->ID)) {
            $fields->push(
                HiddenField::create('OrderID', 'OrderID', $order->ID)
            );
        }
        if ($repeatOrder) {
            $fields->push(
                HiddenField::create('RepeatOrderID', 'RepeatOrderID', $repeatOrder->ID)
            );
        }
        return $fields;
    }

    public static function repeatOrderFormActions($label = '', $repeatOrder = null)
    {
        //actions
        $actions = FieldList::create();
        if ($repeatOrder) {
            if(!$label){
                $label = 'Save';
            }
            $actions->push(FormAction::create('doSave', $label));
        } else {
            if(!$label){
                $label = 'Create';
            }
            $actions->push(FormAction::create('doCreate', $label));
        }
        return $actions;
    }

    /**
     * same as save!
     * @param  [type] $data    [description]
     * @param  [type] $form    [description]
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
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
        $allowNonMembers = Config::inst()->get('RepeatOrder', 'allow_non_members');
        if (!$member && !$allowNonMembers) {
            $form->sessionMessage('Could not find customer details.', 'bad');
            $this->controller->redirectBack();

            return false;
        }
        if ($member && $member->IsShopAdmin()) {
            $form->sessionMessage('Repeat orders can not be created by Shop Administrators.  Only customers can create repeat orders.', 'bad');
            $this->controller->redirectBack();

            return false;
        }
        if (isset($data['OrderID'])) {

            $orderID = intval($data['OrderID']);
            if($orderID) {

                if ($member) {
                    $order = DataObject::get_one('Order', 'Order.ID = \''.$orderID.'\' AND MemberID = \''.$member->ID.'\'');
                }
                else {
                    $order = DataObject::get_one('Order', 'Order.ID = \''.$orderID.'\'');
                }

                if ($order) {
                    $repeatOrder = RepeatOrder::create_repeat_order_from_order($order);
                    if($repeatOrder) {
                        $repeatOrder->OriginatingOrderID = $order->ID;
                        $repeatOrder->write();

                        $order->RepeatOrderID = $repeatOrder->ID;
                        $order->write();
                    } else {
                        $form->sessionMessage('Sorry, an error occured - we could not create your subscribtion order. Please try again.', 'bad');
                        $this->controller->redirectBack();

                        return false;
                    }
                } else {
                    $form->sessionMessage('Could not find originating order.', 'bad');
                    $this->controller->redirectBack();

                    return false;
                }
            }
        } else {
            $repeatOrderID = intval($data['RepeatOrderID']);
            if ($member) {
                $repeatOrder = DataObject::get_one('RepeatOrder', 'RepeatOrder.ID = \''.$repeatOrderID.'\' AND MemberID = \''.$member->ID.'\'');
            }
            else {
                $repeatOrder = DataObject::get_one('RepeatOrder', 'RepeatOrder.ID = \''.$repeatOrderID.'\'');
            }

        }
        if ($repeatOrder) {
            if ($repeatOrderItems = $repeatOrder->OrderItems()) {
                foreach ($repeatOrderItems as $repeatOrderItem) {
                    $repeatOrderItem->ProductID = $data["Product"]["ID"][$repeatOrderItem->ProductID];
                    $repeatOrderItem->Quantity = $data["Product"]["Quantity"][$repeatOrderItem->ProductID];
                    $altCount = Config::inst()->get('RepeatOrderForm', 'number_of_product_alternatives');
                    for ($i = 1; $i <= $altCount; $i++) {
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
            }
            if (isset($data['Period'])) {
                $params['Period'] = $data['Period'];
            } else {
                $data['Period'] = RepeatOrder::default_period_key();
            }
            if (isset($data['PaymentMethod'])) {
                $params['PaymentMethod'] = $data['PaymentMethod'];
            } else {
                $params['PaymentMethod'] = RepeatOrder::default_payment_method_key();
            }
            if (isset($data['Notes'])) {
                $params['Notes'] = $data['Notes'];
            }
            $repeatOrder->update($params);
            $repeatOrder->Status = 'Pending';
            $repeatOrder->write();
        } else {
            $form->sessionMessage('Could not find repeat order.', 'bad');
            $this->controller->redirectBack();

            return false;
        }

        if(!$request->isAjax()){
            $this->controller->redirect(
                RepeatOrdersPage::get_repeat_order_link('view', $repeatOrder->ID)
            );
        }
        return true;
    }
}
