<?php
/**
 * RepeatOrdersPage page shows order history and a form to allow
 * the member to edit his/her details.
 *
 * @package ecommerce
 * @subpackage ecommerce ecommerce_Repeatorders
 * @author nicolaas [at] sunnysideup.co.nz
 */
class RepeatOrdersPage extends AccountPage
{

    /**
     * Standard SS method
     */
    private static $db = array(
        "WhatAreRepeatOrders" => "HTMLText", // explanation of repeat orders in general
        "OnceLoggedInYouCanCreateRepeatOrder" => "HTMLText" //explaining the benefits of logging in for Repeat Orders
    );

    /**
     * Standard SS method
     */
    private static $week_days = array(
        "Monday" => "Monday",
        "Tuesday" => "Tuesday",
        "Wednesday" => "Wednesday",
        "Thursday" => "Thursday",
        "Friday" => "Friday",
        "Saturday" => "Saturday",
        "Sunday" => "Sunday"
    );

    /**
     * Return a link to view the order on the account page.
     * actions are: create, update, view
     * @param String $action
     * @param int|string $orderID ID of the order
     */
    public static function get_repeat_order_link($action = 'view', $repeatOrderID = 0)
    {
        $page = DataObject::get_one(__CLASS__);
        if (!$page) {
            user_error('No RepeatOrderPage was found. Please create one in the CMS!', E_USER_ERROR);
        }
        return $page->Link($action)."/".$repeatOrderID."/";
    }

    /**
     * standard SS Method
     */
    public function canCreate($member = null)
    {
        if(DataObject::get_one("RepeatOrdersPage")) {
            return false;
        } else {
            return parent::canCreate($member);
        }
    }


    /**
     * standard SS Method
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Content.ExplainingRepeatOrders", new HTMLEditorField($name = "WhatAreRepeatOrders", $title = "What Are Repeat Orders - Explanation Used throughout the site.", $rows = 3, $cols = 3));
        $fields->addFieldToTab("Root.Content.ExplainingRepeatOrders", new HTMLEditorField($name = "OnceLoggedInYouCanCreateRepeatOrder", $title = "Explanation for people who are not logged-in yet explaining that they can turn an order into a Repeat order...", $rows = 3, $cols = 3));
        return $fields;
    }

    /**
     * Returns all {@link Order} records for this
     * member that are completed.
     *
     * @return ArrayList
     */
    public function RepeatOrders()
    {
        $memberID = Member::currentUserID();
        return RepeatOrder::get()
            ->where("\"MemberID\" = '$memberID' AND \"Status\" NOT IN ('MemberCancelled', 'AdminCancelled')")
            ->sort("\"Created\" DESC");
    }

    /**
     * Automatically create an AccountPage if one is not found
     * on the site at the time the database is built (dev/build).
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (!DataObject::get_one('RepeatOrdersPage')) {
            $page = new RepeatOrdersPage();
            $page->Title = 'Repeat Orders';
            $page->Content = '<p>This is the Repeat orders account page. It is used for shop users to login and create or change their Repeat orders.</p>';
            $page->URLSegment = 'repeat-orders';
            $page->WhatAreRepeatOrders = '<p>Repeat Orders allow you to regularly repeat an order.</p>';
            $page->OnceLoggedInYouCanCreateRepeatOrder = '<p>Once logged in you can setup a repeating order.</p>';
            $page->ShowInMenus = 0;
            $page->ShowInSearch = 0;
            $page->writeToStage('Stage');
            $page->publish('Stage', 'Live');
            if (method_exists('DB', 'alteration_message')) {
                DB::alteration_message('Repeat Order page \'Repeat Orders\' created', 'created');
            }
        }
    }

    /**
     * Standard SS method
     * Sets the days available for repeating orders.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }
}
