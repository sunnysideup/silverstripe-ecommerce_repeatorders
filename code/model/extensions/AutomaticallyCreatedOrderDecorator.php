<?php

/**
 * @author nicolaas @ sunnysideup . co . nz
 */

class AutomaticallyCreatedOrderDecorator extends DataExtension
{
    private static $db= [
        "OrderDate" => "Date", //date at which the order should be placed
        "OrderDateInteger" => "Int" //date at which the order should be placed AS integer
    ];

    private static $has_one = [
        'RepeatOrder' => 'RepeatOrder'
    ];

    private static $indexes = [
        'OrderDateInteger' => true
    ];
    private static $searchable_fields = [
        'RepeatOrderID' => [
            'field' => 'NumericField',
            'title' => 'Repeat Order Number'
        ]
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName("OrderDate");
        $fields->removeByName("OrderDateInteger");
        $fields->removeByName("RepeatOrderID");
        if ($this->owner->RepeatOrderID) {
            $fields->addFieldToTab("Root.RepeatOrder", ReadonlyField::create("OrderDate", "Planned Next Order Date - based on repeating order schedule"));
            $fields->addFieldToTab("Root.RepeatOrder", ReadonlyField::create("RepeatOrderID", "Created as part of Repeat Order #"));
        }
    }

    public function FuturePast()
    {
        $currentTime = strtotime('Now');
        $orderTime = strtotime($this->owner->OrderDate);
        if ($currentTime > $orderTime) {
            return "past";
        } elseif ($currentTime == $orderTime) {
            return "current";
        } else {
            return "future";
        }
    }
}
