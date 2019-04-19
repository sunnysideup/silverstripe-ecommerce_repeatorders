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

    public static $show_post_submit_actions = false;

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
        $showCreateRepeatOrderForm = false;
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
            if ($repeatOrder->canModify()) {
                $repeatOrderFormFields = RepeatOrderForm::repeatOrderFormFields($repeatOrder->ID, $orderID, true);
                foreach ($repeatOrderFormFields as $repeatOrderFormField) {
                    $fields->push(
                        $repeatOrderFormField
                    );
                }

                $cancelRepeatOrderLink = RepeatOrdersPage::get_repeat_order_link('ajaxcheckoutcancel', $repeatOrder->ID);
                $fields->push(
                    HiddenField::create('AjaxCancelLink', 'AjaxCancelLink', $cancelRepeatOrderLink)
                );

                $actions = RepeatOrderForm::repeatOrderFormActions('Update', $repeatOrder);
                $actions->push(
                    FormAction::create('doCancel', 'Cancel Subscription')
                );
            }
            else {
                $showCreateRepeatOrderForm = true;
            }
            Requirements::customScript("jQuery(document).ready(function(){jQuery(\"input[name='action_processOrder']\").hide();});", "hide_action_processOrder");
        } elseif ($currentMember || $allowNonMembers) {
            //this shouldn't actually happen as the repeat order should have been found and used in the previous if statement
            if ($order->RepeatOrderID) {
                $fields->push(
                    LiteralField::create(
                        "whatAreRepeatOrders",
                        '<div id="WhatAreRepeatOrders">This order is based on a Repeat Order.</div>'
                    )
                );
            } else {
                $showCreateRepeatOrderForm = true;
            }
        } else {
            $page = DataObject::get_one("RepeatOrdersPage");
            if ($page) {
                $fields->push(
                    LiteralField::create(
                        "whatAreRepeatOrders",
                        '<div id="WhatAreRepeatOrders">' . $page->OnceLoggedInYouCanCreateRepeatOrder . '</div>'
                    )
                );
            }
        }

        if($showCreateRepeatOrderForm){
            $repeatOrderFormFields = RepeatOrderForm::repeatOrderFormFields(0, $orderID);
            foreach ($repeatOrderFormFields as $repeatOrderFormField) {
                $fields->push(
                    $repeatOrderFormField
                );
            }

            $page = DataObject::get_one("RepeatOrdersPage");
            if ($page) {
                $fields->push(
                    LiteralField::create("whatAreRepeatOrders",
                    '<div id="WhatAreRepeatOrders">' . $page->WhatAreRepeatOrders . '</div>'
                    )
                );
            }

            $actions = RepeatOrderForm::repeatOrderFormActions('Confirm and Pay');

            //required fields
            $requiredArray = array('Start', 'Period');
            $optionalValidator = RequiredFields::create($requiredArray);
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
        if($this->config()->get('$show_post_submit_actions')){
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
}
