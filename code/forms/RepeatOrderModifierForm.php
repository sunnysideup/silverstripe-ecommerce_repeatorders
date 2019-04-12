<?php

class RepeatOrderModifierForm extends OrderModifierForm
{
    /**
     *
     */
    public function __construct($optionalController = null, $name, FieldList $fields, FieldList $actions, $optionalValidator = null)
    {
        parent::__construct($optionalController, $name, $fields, $actions, $optionalValidator);
        Requirements::javascript('ecommerce_repeatorders/javascript/RepeatOrdersPage_admin.js');
    }

    public function submit($data, $form)
    {
    }
}
