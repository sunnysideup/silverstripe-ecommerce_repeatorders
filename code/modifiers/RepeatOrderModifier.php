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
        return $this->Order()->Items() && $this->Order()->Items() && Member::currentUser();
    }

    public function getModifierForm($optionalController = null, $optionalValidator = null)
    {
        $fields = new FieldList();
        $fields->push($this->headingField());
        $fields->push($this->descriptionField());
        $orderID = Session::get('RepeatOrder');
        $createLink = RepeatOrdersPage::get_repeat_order_link('createorder');
        if ($orderID && Member::currentMember()) {
            $order = DataObject::get_by_id('RepeatOrder', $orderID);
            $updateLink = RepeatOrdersPage::get_repeat_order_link('update', $orderID);
            $cancelLink = RepeatOrdersPage::get_repeat_order_link('cancel', $orderID);
            if ($order->CanModify()) {
                $fields->push(new LiteralField('modifyRepeatOrder',
<<<HTML
                    <div class="Actions"><input id="ModifyRepeatOrderUpdate"  class="action" type="button" value="Save changes to your Repeat Order #$orderID" onclick="window.location='{$updateLink}';" /></div>
HTML
                    )
                );
            } else {
                $fields->push(new LiteralField('createRepeatOrder',
<<<HTML
                        <div class="Actions"><input id="ModifyRepeatOrderCreate" class="action" type="button" value="Create a new Repeat Order" onclick="window.location='{$createLink}';" /></div>
HTML
                    )
                );
            }
            Requirements::customScript("jQuery(document).ready(function(){jQuery(\"input[name='action_processOrder']\").hide();});", "hide_action_processOrder");
        } elseif (Member::currentMember()) {
            if (!Session::get("DraftOrderID")) {
                $fields->push(new LiteralField('createRepeatOrder',
<<<HTML
                    <div class="Actions"><input  id="ModifyRepeatOrderCreate" class="action" type="button" value="Turn this Order into a Repeat Order" onclick="window.location='{$createLink}';" /></div>
HTML
                    )
                );
                $page = DataObject::get_one("RepeatOrdersPage");
                if ($page) {
                    $fields->push(new LiteralField("whatAreRepeatOrders",
<<<HTML
                    <div id="WhatAreRepeatOrders">$page->WhatAreRepeatOrders</div>
HTML
                    ));
                }
            } else {
                $fields->push(new LiteralField("whatAreRepeatOrders",
<<<HTML
                    <div id="WhatAreRepeatOrders">This order is based on a Repeat Order.</div>
HTML
                    ));
            }
        } else {
            $page = DataObject::get_one("RepeatOrdersPage");
            if ($page) {
                $fields->push(new LiteralField("whatAreRepeatOrders",
<<<HTML
                    <div id="WhatAreRepeatOrders">$page->OnceLoggedInYouCanCreateRepeatOrder</div>
HTML
                ));
            }
        }
        return new RepeatOrderModifierForm(
            $optionalController,
            'RepeatOrderModifier',
            $fields,
            new FieldList(), 
            $optionalValidator);
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
        return "";
    }

    /**
     * retursn and array like this: array(Title => "bla", Link => "/doit/now/");
     * @return Array
     */
    public function PostSubmitAction()
    {
        if (Member::currentUser()) {
            return array(
                "Title" => _t("RepeatOrder.CREATEREPEATEORDER", "Turn this Order into a Repeat Order"),
                "Link" => RepeatOrdersPage::get_repeat_order_link("createorder", $this->Order()->ID)
            );
        }
    }
}
