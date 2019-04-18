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
        $fields->addFieldsToTab(
            "Root.ExplainingRepeatOrders",
            [
                HtmlEditorField::create(
                    $name = "WhatAreRepeatOrders",
                    $title = "What Are Repeat Orders."
                )->setDescription('Explanation Used throughout the site'),
                HtmlEditorField::create(
                    $name = "OnceLoggedInYouCanCreateRepeatOrder",
                    $title = "Not Logged In"
                )->setDescription('Explanation for people who are not logged-in yet explaining that they can turn an order into a Repeat order...')
            ]
        );

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
            $page = RepeatOrdersPage::create();
            $page->Title = 'Repeat Orders';
            $page->Content = '<p>This is the Repeat orders account page. It is used for shop users to login and create or change their Repeat orders.</p>';
            $page->URLSegment = 'repeat-orders';
            $page->WhatAreRepeatOrders = '<p>Repeat Orders allow you to regularly repeat an order.</p>';
            $page->OnceLoggedInYouCanCreateRepeatOrder = '<p>Once logged in you can setup a repeating order.</p>';
            $page->ShowInMenus = 0;
            $page->ShowInSearch = 0;
            $page->writeToStage('Stage');
            $page->publish('Stage', 'Live');
            DB::alteration_message('Repeat Order page \'Repeat Orders\' created', 'created');
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


class RepeatOrdersPage_Controller extends AccountPage_Controller
{

    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = array(
        'createorder' => true,
        'cancel' => true,
        'view' => true,
        'modify' => true,
        'admin' => true,
        'ajaxcreateorder' => true,
        'RepeatOrderForm' => true
    );

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
        return $this->renderWith(
            ['RepeatOrdersPage_edit', 'Page'],
            $params
        );
    }

    public function cancel($request)
    {
        if ($repeatOrderID = intval($request->param("ID"))) {
            $repeatOrder = DataObject::get_one('RepeatOrder', ["ID" => $repeatOrderID]);
            if ($repeatOrder && $repeatOrder->canEdit()) {
                $repeatOrder->Status = 'MemberCancelled';
                $repeatOrder->write();

                return $this->redirectBack();
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
                    'RepeatOrder' => '',
                    'Message' => "You do not have permission to view this Order, please log in."
                );
            }
        }

        return $this->renderWith(
            ['RepeatOrdersPage_view', 'Page'],
            $params
        );
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
        return $this->renderWith(
            ['RepeatOrdersPage_edit', 'Page'],
            $params
        );
    }

    public function ajaxcreateorder($request)
    {
        $orderID = intval($request->postVar('OrderID'));
        if ($request->isAjax()) {
            $orderForm = RepeatOrderForm::create(
                $this,
                'RepeatOrderForm',
                0,
                $orderID
            );
            $orderForm->doCreate($this->request->postVars(), $orderForm, $request);
        }
        else {
            user_error('This function can only be called via Ajax and also requires an OrderID to be posted.');
        }
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
                $repeatOrderID = intval($_REQUEST['repeatOrderID']);
            }
            if ($action == 'createorder') {
                $orderID = $repeatOrderID;
                $repeatOrderID = 0;
            }
            return RepeatOrderForm::create(
                $this,
                'RepeatOrderForm',
                $repeatOrderID,
                $orderID
            );
        } elseif ($action == 'update' || isset($_REQUEST['action_doSave'])) {
            if (isset($_REQUEST['action_doSave']) && isset($_REQUEST['RepeatOrderID'])) {
                $repeatOrderID = intval($_REQUEST['RepeatOrderID']);
            }
            return RepeatOrderForm::create(
                $this,
                'RepeatOrderForm',
                $repeatOrderID,
                $orderID
            );
        } elseif ($repeatOrderID) {
            return RepeatOrderForm::create(
                $this,
                'RepeatOrderForm',
                $repeatOrderID,
                $orderID
            );
        } else {
            return $this->redirect('404-could-not-find-order');
        }
    }
    /**
     * Show a list of all repeating orders.
     * @return HTML
     */
    public function admin()
    {
        $shopAdminCode = EcommerceConfig::get("EcommerceRole", "admin_permission_code");
        if (Permission::check("ADMIN") || Permission::check($shopAdminCode)) {
            RepeatOrder::create_automatically_created_orders();
            $params = array(
                "AllRepeatOrders" => RepeatOrder::get()->filter(["Status" => 'Active'])
            );
            Requirements::javascript(THIRDPARTY_DIR."/jquery/jquery.js");
            //Requirements::block(THIRDPARTY_DIR."/jquery/jquery.js");
            //Requirements::javascript(Director::protocol()."ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
            Requirements::javascript("ecommerce_repeatorders/javascript/RepeatOrdersPage_admin.js");
            Requirements::themedCSS("RepeatOrdersPage_admin");

            return $this->renderWith(
                ['RepeatOrdersPage_admin', 'Page'],
                $params
            );
        } else {
            return Security::permissionFailure($this, _t('OrderReport.PERMISSIONFAILURE', 'Sorry you do not have permission for this function. Please login as an Adminstrator'));
        }
    }
}
