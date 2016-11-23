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
    public static $db = array(
        "OrderDays" => "Varchar(255)", //days of the week that can be ordered.
        "WhatAreRepeatOrders" => "HTMLText", // explanation of repeat orders in general
        "OnceLoggedInYouCanCreateRepeatOrder" => "HTMLText" //explaining the benefits of logging in for Repeat Orders
    );

    /**
     * Standard SS method
     */
    protected static $week_days = array(
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
        return !DataObject::get_one("RepeatOrdersPage");
    }


    /**
     * standard SS Method
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Content.Settings", new TextField($name = "OrderDays", $title = "Order Weekdays - separated by comma, e.g. Monday, Tuesday, Wednesday"));
        $fields->addFieldToTab("Root.Content.ExplainingRepeatOrders", new HTMLEditorField($name = "WhatAreRepeatOrders", $title = "What Are Repeat Orders - Explanation Used throughout the site.", $rows = 3, $cols = 3));
        $fields->addFieldToTab("Root.Content.ExplainingRepeatOrders", new HTMLEditorField($name = "OnceLoggedInYouCanCreateRepeatOrder", $title = "Explanation for people who are not logged-in yet explaining that they can turn an order into a Repeat order...", $rows = 3, $cols = 3));
        return $fields;
    }

    /**
     * Returns all {@link Order} records for this
     * member that are completed.
     *
     * @return DataObjectSet
     */
    public function RepeatOrders()
    {
        $memberID = Member::currentUserID();
        return DataObject::get(
            'RepeatOrder',
            "\"MemberID\" = '$memberID' AND \"Status\" NOT IN ('MemberCancelled', 'AdminCancelled')",
            "\"Created\" DESC"
        );
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
        $days = explode(",", $this->OrderDays);
        $cleanDays = array();
        if (count($days)) {
            foreach ($days as $day) {
                $day = trim($day);
                if (count(self::$week_days)) {
                    foreach (self::$week_days as $perfectDay) {
                        if (strtolower($day)== strtolower($perfectDay)) {
                            $cleanDays[$perfectDay] = $perfectDay;
                        }
                    }
                }
            }
        }
        if (count($cleanDays)) {
            $this->OrderDays = implode(",", $cleanDays);
        } else {
            $this->OrderDays = implode(",", self::$week_days);
        }
    }
}

class RepeatOrdersPage_Controller extends AccountPage_Controller
{
    public function init()
    {
        parent::init();
    }

    public function createorder($request)
    {
        $orderID = intval($request->param("ID"));
        $order = null;
        if ($orderID) {
            $order = Order::get_by_id_if_can_view($orderID);
        }
        if (!$order) {
            $order = ShoppingCart::current_order();
        }
        //TODO: move items to order
        $params = array(
            'Order' => $order,
        );
        return $this->renderWith(array('RepeatOrdersPage_edit', 'Page'), $params);
    }

    public function cancel($request)
    {
        if ($repeatOrderID = intval($request->param("ID"))) {
            $repeatOrder = DataObject::get_one('RepeatOrder', "RepeatOrder.ID = '$repeatOrderID'");
            if ($repeatOrder && $repeatOrder->canEdit()) {
                $repeatOrder->Status = 'MemberCancelled';
                $repeatOrder->write();
                Director::redirectBack();
            }
        }
        die("Could not cancel repeat order.");
    }

    public function view($request)
    {
        $params = array(
            'RepeatOrder' => false,
            'Message' => 'Repeating order could not be found.'
        );
        if ($repeatOrderID = intval($request->param("ID"))) {
            $repeatOrder = DataObject::get_one('RepeatOrder', "RepeatOrder.ID = '$repeatOrderID'");
            if ($repeatOrder && $repeatOrder->canView()) {
                $params = array(
                    'RepeatOrder' => $repeatOrder,
                    'Message' => "Please review order below."
                );
            } else {
                $params = array(
                    'RepeatOrder' => null,
                    'Message' => "You can not view this Order."
                );
            }
        }
        return $this->renderWith(array('RepeatOrdersPage_view', 'Page'), $params);
    }

    public function modify($request)
    {
        $params = array(
            'RepeatOrder' => false,
            'Message' => 'There is no order by that ID.'
        );
        if ($repeatOrderID = intval($request->param("ID"))) {
            $repeatOrder = DataObject::get_by_id('RepeatOrder', $repeatOrderID);
            if ($repeatOrder->canEdit()) {
                $params = array(
                    'RepeatOrder' => false,
                    'Message' => 'Please edit your details below.'
                );
            }
        }
        return $this->renderWith(array('RepeatOrdersPage_edit', 'Page'), $params);
    }

    /**
     *
     * @return RepeatOrderForm
     */
    public function RepeatOrderForm()
    {
        $action = $this->request->param('Action');
        $repeatOrderID = intval($this->request->param('ID'));
        $orderID = 0;
        if ($action == 'createorder' || isset($_REQUEST['action_doCreate'])) {
            if (isset($_REQUEST['action_doCreate']) && isset($_REQUEST['repeatOrderID'])) {
                $repeatOrderID = $_REQUEST['repeatOrderID'];
            }
            if ($action == 'createorder') {
                $orderID = $repeatOrderID;
                $repeatOrderID = 0;
            }
            return new RepeatOrderForm($this, 'RepeatOrderForm', $repeatOrderID, $orderID);
        } elseif ($action == 'update' || isset($_REQUEST['action_doSave'])) {
            if (isset($_REQUEST['action_doSave']) && isset($_REQUEST['RepeatOrderID'])) {
                $repeatOrderID = $_REQUEST['RepeatOrderID'];
            }
            return new RepeatOrderForm($this, 'RepeatOrderForm', $repeatOrderID, $orderID);
        } elseif ($repeatOrderID) {
            return new RepeatOrderForm($this, 'RepeatOrderForm', $repeatOrderID, $orderID);
        } else {
            user_error("Could not find order");
        }
    }


    /**
     * Show a list of all repeating orders.
     * @return HTML
     */
    public function admin()
    {
        $shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
        if (!Permission::check("ADMIN") && !Permission::check($shopAdminCode)) {
            return Security::permissionFailure($this, _t('OrderReport.PERMISSIONFAILURE', 'Sorry you do not have permission for this function. Please login as an Adminstrator'));
        }
        RepeatOrder::create_automatically_created_orders();
        $params = array(
            "AllRepeatOrders" => DataObject::get("RepeatOrder", "\"Status\" = 'Active'")
        );
        Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
        //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
        Requirements::javascript("ecommerce_repeatorders/javascript/RepeatOrdersPage_admin.js");
        Requirements::themedCSS("RepeatOrdersPage_admin");
        return $this->renderWith(array('RepeatOrdersPage_admin', 'Page'), $params);
    }
}
