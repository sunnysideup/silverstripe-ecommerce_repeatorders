<?php

class RepeatOrderModifier extends OrderModifier
{
    public static $singular_name = "Repeat Order Modifier";
    public function i18n_singular_name()
    {
        return _t("RepeatOrderModifier.REPEATORDERMODIFIER", "Repeat Order Modifier");
    }

    public static $plural_name = "Repeat Order Modifiers";
    public function i18n_plural_name()
    {
        return _t("RepeatOrderModifier.REPEATORDERMODIFIERS", "Repeat Order Modifiers");
    }

    /**
     * standard OrderModifier Method
     * Should we show a form in the checkout page for this modifier?
     */
    public function ShowForm()
    {
        return $this->Order()->Items();
    }

    public function getModifierForm(Controller $optionalController = NULL, Validator $optionalValidator = NULL)
    {
        $fields = FieldList::create();
        $actions = null;
        $fields->push($this->headingField());
        $fields->push($this->descriptionField());
        $order = ShoppingCart::current_order();
        $currentMember = Member::currentUser();

        $repeatOrder = null;
        if($order && $order->exists()) {
            $orderID = $order->ID;
            $repeatOrder = DataObject::get_one('RepeatOrder', ['OriginatingOrderID' => $order->ID]);
        } else {
            $orderID = 0;
        }
        $createLink = RepeatOrdersPage::get_repeat_order_link('createorder', $orderID);
        $allowNonMembers = Config::inst()->get('RepeatOrder', 'allow_non_members');

        if (($repeatOrder && $currentMember) || ($repeatOrder && $allowNonMembers)) {

            $updateLink = RepeatOrdersPage::get_repeat_order_link('modify', $repeatOrder->ID);
            $cancelLink = RepeatOrdersPage::get_repeat_order_link('cancel', $repeatOrder->ID);

            if ($repeatOrder->canModify()) {
                $fields->push(
                    LiteralField::create(
                        'modifyRepeatOrder',
<<<HTML
                        <div class="Actions">
                            <input id="ModifyRepeatOrderUpdate" class="action" type="button" value="Edit your associated Repeat Order" onclick="window.location='{$updateLink}';" />
                        </div>
HTML
                    )
                );
            } else {
                $fields->push(
                    LiteralField::create(
                        'createRepeatOrder',
<<<HTML
                        <div class="Actions">
                            <input id="ModifyRepeatOrderCreate" class="action" type="button" value="Create a new Repeat Order" onclick="window.location='{$createLink}';" />
                        </div>
HTML
                    )
                );
            }
            Requirements::customScript("jQuery(document).ready(function(){jQuery(\"input[name='action_processOrder']\").hide();});", "hide_action_processOrder");
        } elseif ($currentMember || $allowNonMembers) {
            if ($order->RepeatOrderID) {
                $fields->push(
                    LiteralField::create(
                        "whatAreRepeatOrders",
<<<HTML
                        <div id="WhatAreRepeatOrders">This order is based on a Repeat Order.</div>
HTML
                    ));
            } else {
                $repeatOrderFormFields = RepeatOrderForm::repeatOrderFormFields(0, $orderID);
                foreach ($repeatOrderFormFields as $repeatOrderFormField) {
                    $fields->push(
                        $repeatOrderFormField
                    );
                }

                $repeatOrderFormLink = RepeatOrdersPage::get_repeat_order_link('ajaxcreateorder', $orderID);
                $fields->push(
                    HiddenField::create('AjaxSubmissionLink', 'AjaxSubmissionLink', $repeatOrderFormLink)
                );
                $page = DataObject::get_one("RepeatOrdersPage");
                if ($page) {
                    $fields->push(
                        LiteralField::create("whatAreRepeatOrders",
<<<HTML
                        <div id="WhatAreRepeatOrders">$page->WhatAreRepeatOrders</div>
HTML
                    ));
                }

                $actions = RepeatOrderForm::repeatOrderFormActions('Confirm and Pay');

                //required fields
                $requiredArray = array('Start', 'Period');
                $optionalValidator = RequiredFields::create($requiredArray);
            }
        } else {
            $page = DataObject::get_one("RepeatOrdersPage");
            if ($page) {
                $fields->push(
                    LiteralField::create(
                        "whatAreRepeatOrders",
<<<HTML
                        <div id="WhatAreRepeatOrders">$page->OnceLoggedInYouCanCreateRepeatOrder</div>
HTML
                ));
            }
        }

        if($actions === NULL){
            $actions = FieldList::create();
        }

        return RepeatOrderModifierForm::create(
            $optionalController,
            'RepeatOrderModifier',
            $fields,
            $actions,
            $optionalValidator
        );
    }

    public function LiveCalculatedTotal()
    {
        return 0;
    }

    public function CanBeRemoved()
    {
        return false;
    }

    public function ShowInTable()
    {
        return false;
    }

    public function LiveName()
    {
        return '';
    }

    /**
     * retursn and array like this: array(Title => "bla", Link => "/doit/now/");
     * This will be shown on the confirmation page....
     * @return Array
     */
    public function PostSubmitAction()
    {
        $order = $this->Order();
        if($order && $order->exists()) {
            if ($order->MemberID) {
                if($order->RepeatOrderID) {
                    return array(
                        "Title" => _t("RepeatOrder.MODIFYORDER", "Edit repeating order"),
                        "Link" => RepeatOrdersPage::get_repeat_order_link("view", $order->RepeatOrderID)
                    );
                }
                $existingRepeatOrder = RepeatOrder::get()->filter(['OriginatingOrderID' => $order->ID])->first();
                if($existingRepeatOrder && $existingRepeatOrder->exists()) {
                    return array(
                        "Title" => _t("RepeatOrder.MODIFYORDER", "Edit repeating order"),
                        "Link" => RepeatOrdersPage::get_repeat_order_link("modify", $existingRepeatOrder->ID)
                    );
                } else {
                    return array(
                        "Title" => _t("RepeatOrder.CREATEREPEATEORDER", "Turn this Order into a Repeat Order"),
                        "Link" => RepeatOrdersPage::get_repeat_order_link("createorder", $this->Order()->ID)
                    );
                }
            }
        }
    }
}
