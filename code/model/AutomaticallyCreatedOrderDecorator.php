<?php

/**
 * @author nicolaas @ sunnysideup . co . nz
 */

class AutomaticallyCreatedOrderDecorator extends DataObjectDecorator {

	function extraStatics() {
		return array(
			'db' => array(
				"UIDHash" => "Varchar(32)", //used to be able to open the order without username / password
				"OrderDate" => "Date", //date at which the order was instigated based on Repeat Order Requirements
				"OrderDateInteger" => "Int" //date at which the order was instigated based on Repeat Order Requirements INTEGER FOR easy lookup
			),
			'has_one' => array(
				'RepeatOrder' => 'RepeatOrder'
			),
			'casting' => array(
				"Completed" => "Boolean", //has been paid
				"OrderItemsAdded" => "Boolean" //has been paid
			),
			'indexes' => array(
				'UIDHash' => 'unique (UIDHash)'
			)
		);
	}

	function Completed() {
		return DataObject::get_one("Payment", "OrderID =".$this->owner->ID, false);
	}

	function getCompleted(){
		return $this->owner->Completed();
	}

	function hasCompleted() {
		if($this->owner->Completed()) {return  true;} else {return false;}
	}

	function OrderItemsAdded() {
		return DataObject::get_one("OrderAttribute", "OrderID =".$this->owner->ID, false);
	}

	function getOrderItemsAdded(){
		return $this->owner->OrderItemsAdded();
	}

	function hasOrderItemsAdded() {
		if($this->owner->OrderItemsAdded()) {return  true;} else {return false;}
	}


	/**
	 * Publish the order
	 * @return null
	 */
	public function publishOrder() {}

	function ViewHTMLLink() {
		return '<a href="OrderReportWithLog_Popup/invoice/'.$this->owner->ID.'" class="makeIntoPopUp">View</a>';
	}

	function LoadHTMLLink() {
		return '<a href="'.$this->LoadLink().'" class="makeIntoPopUp">Load</a>';
	}

	function LoadLink() {
		return RepeatOrdersPage::get_Repeat_order_link("load", $this->owner->UIDHash);
	}

	function FuturePast() {
		$currentTime = strtotime(Date("Y-m-d"));
		$orderTime = strtotime($this->owner->OrderDate);
		if($currentTime > $orderTime) {
			return "past";
		}
		elseif($currentTime == $orderTime) {
			return "current";
		}
		else {
			return "future";
		}
	}

	function CompleteOrder() {
		ShoppingCart::clear();
		if($RepeatOrder = $this->owner->RepeatOrder()) {
			// Set the items from the cart into the order
			if($RepeatOrder->OrderItems()) {
				foreach($RepeatOrder->OrderItems() as $orderItem) {
					$product = DataObject::get_by_id('Product', $orderItem->ProductID);
					if($product) {
						if(class_exists("ProductStockCalculatedQuantity")) {
							$numberAvailable = ProductStockCalculatedQuantity::get_quantity_by_product_id($product->ID);
							if($numberAvailable < $orderItem->Quantity) {
								$dos = $RepeatOrder->AlternativesPerProduct($product->ID);
								$product = null;
								if($dos) {
									foreach($dos as $do) {
										$numberAvailable = ProductStockCalculatedQuantity::get_quantity_by_product_id($do->ID);
										if($numberAvailable > $orderItem->Quantity) {
											$product = $do;
										}
									}
								}
							}
						}
						if($product) {
							$newProductOrderItem = new Product_OrderItem();
							$newProductOrderItem->BuyableID = $orderItem->ProductID;
							ShoppingCart::add_new_item(
								$newProductOrderItem,
								$orderItem->Quantity
							);
						}
					}
					else {
						USER_ERROR("Product does not exist", E_USER_WARNING);
					}
				}
			}
			else {
				USER_ERROR("There are no order items", E_USER_WARNING);
			}
		}
		else {
			USER_ERROR("Order #".$this->owner->ID." does not have a Repeat order associated with it!", E_USER_WARNING);
		}
		Session::set("DraftOrderID", $this->owner->ID);
		return Session::get("DraftOrderID");
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if($id = intvaL(Session::get("DraftOrderID"))) {
			$oldOne = DataObject::get_by_id("Order", $id);
			if($oldOne && $this->owner->MemberID == $oldOne->MemberID) {
				$this->owner->OrderDate = $oldOne->OrderDate;
				$this->owner->OrderDateInteger = $oldOne->OrderDateInteger;
				$this->owner->UIDHash = $oldOne->UIDHash;
				$this->owner->RepeatOrderID = $oldOne->RepeatOrderID;
				//does thsi work????
				$oldOne->delete();
			}
			else {
				USER_ERROR("Originating Order not correct", E_USER_NOTICE);
			}
		}
		Session::set("DraftOrderID", null);
		if(!strlen($this->owner->UIDHash) == 32) {
			$this->owner->UIDHash = substr(base_convert(md5(uniqid(mt_rand(), true)), 16, 36),0, 32);
		}
		$this->owner->OrderDateInteger = strtotime($this->owner->OrderDate);
	}

	function onAfterWrite() {
		parent::onAfterWrite();
	}

}
