<?php
class RepeatOrder_OrderItem extends DataObject
{
    public static $db = array(
        'Quantity' => 'Int'
    );

    public static $has_one = array(
        'Product' => 'Product',
        'Order' => 'RepeatOrder',
        'Alternative1' => 'Product',
        'Alternative2' => 'Product',
        'Alternative3' => 'Product',
        'Alternative4' => 'Product',
        'Alternative5' => 'Product'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $fields;
    }

    public function Title()
    {
        if ($product = $this->Product()) {
            return $product->Title;
        }
        return "NOT FOUND";
    }

    public function BuyableTitle()
    {
        if ($product = $this->Product()) {
            return $product->Title;
        }
        return "NOT FOUND";
    }

    /**
     * returns a link to the product
     * @return String
     */
    public function Link()
    {
        if ($product = $this->Product()) {
            return $product->Link;
        }
        return "";
    }

    /**
     * returns the product ID
     * @return String
     */
    public function getProductID()
    {
        if ($product = $this->Product()) {
            return $product->ID;
        }
        return 0;
    }

    /**
     * Alias for AlternativesPerProduct
     */
    public function TableAlternatives()
    {
        return $this->AlternativesPerProduct();
    }

    /**
     * returns a list of alternatives per product (if any)
     * @return ArrayList|null
     */
    public function AlternativesPerProduct()
    {
        $dos = ArrayList::create();
        for ($i = 1; $i < 6; $i++) {
            $alternativeField = "Alternative".$i."ID";
            if ($this->$alternativeField) {
                $product = DataObject::get_one("Product", ['ID' => $this->$alternativeField]);
                if ($product) {
                    $dos->push($product);
                }
            }
        }
        if ($dos && $dos->count()) {
            return $dos;
        }
        return null;
    }
}
