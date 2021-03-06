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
        'Alternative5' => 'Product',
        'Alternative6' => 'Product',
        'Alternative7' => 'Product',
        'Alternative8' => 'Product',
        'Alternative9' => 'Product'
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
     * @return string
     */
    public function Link()
    {
        if ($product = $this->Product()) {
            return $product->Link;
        }
        return '';
    }

    /**
     * returns the product ID
     * @return string
     */
    public function getProductID()
    {
        if ($product = $this->Product()) {
            return $product->ID;
        }
        return 0;
    }

    /**
     * @return Field (EcomQuantityField)
     **/
    public function IDField()
    {
        $field = HiddenField::create(
            'Product[ID]['.$this->Product()->ID.']',
            "",
            $this->Product()->ID
        );
        return $field;
    }

    /**
     * @return Field (EcomQuantityField)
     **/
    public function QuantityField()
    {
        $field = NumericField::create(
            'Product[Quantity]['.$this->Product()->ID.']',
            "",
            $this->Quantity
        )->addExtraClass('ajaxQuantityField');
        return $field;
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
        $altCount = Config::inst()->get('RepeatOrderForm', 'number_of_product_alternatives');
        for ($i = 1; $i <= $altCount; $i++) {
            $alternativeField = "Alternative".$i."ID";
            if ($this->$alternativeField) {
                $product = Product::get()->filter(['ID' => $this->$alternativeField])->first();
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
