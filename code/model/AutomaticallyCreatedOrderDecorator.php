<?php

/**
 * @author nicolaas @ sunnysideup . co . nz
 */

class AutomaticallyCreatedOrderDecorator extends DataObjectDecorator
{

    public function extraStatics()
    {
        return array(
            'db' => array(
                "OrderDate" => "Date", //date at which the order should be placed
                "OrderDateInteger" => "Int" //date at which the order should be placed AS integer
            ),
            'has_one' => array(
                'RepeatOrder' => 'RepeatOrder'
            ),
            'indexes' => array(
                'OrderDateInteger' => true
            ),
            'searchable_fields' => array(
                'RepeatOrderID' => array(
                    'field' => 'NumericField',
                    'title' => 'Repeat Order Number'
                )
            )
        );
    }

    public function updateCMSFields(&$fields)
    {
        $fields->removeByName("OrderDate");
        $fields->removeByName("OrderDateInteger");
        $fields->removeByName("RepeatOrderID");
        if ($this->owner->RepeatOrderID) {
            $fields->addFieldToTab("Root.RepeatOrder", new ReadonlyField("OrderDate", "Planned Order Date - based on repeating order schedule"));
            $fields->addFieldToTab("Root.RepeatOrder", new ReadonlyField("RepeatOrderID", "Created as part of Repeat Order #"));
        }
    }

    public function FuturePast()
    {
        $currentTime = strtotime(Date("Y-m-d"));
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
