<?php


class RepeatOrders_OrderFormAddressExtension extends Extension
{

    public function updateOrderFormAddress(&$form)
    {
        $nextButton = new FormAction('saveAddressAndSkipSubscriptionStep', 'Confirm and Pay');
        $nextButton->addExtraClass('next');
        $subsButton = new FormAction('saveAddressAndCreateSubscription', 'Create Subscription');
        $subsButton->addExtraClass('next');
        $actions = FieldList::create($subsButton, $nextButton);
        $form->setActions($actions);
    }


    public function saveAddressAndSkipSubscriptionStep(array $data, Form $form, SS_HTTPRequest $request)
    {
        $this->owner->saveAddressDetails($data, $form, $request);

        $nextStepLink = CheckoutPage::find_next_step_link('orderformsubscription');
        $this->owner->controller->redirect($nextStepLink);

        return true;
    }

    public function saveAddressAndCreateSubscription(array $data, Form $form, SS_HTTPRequest $request)
    {
        $this->owner->saveAddressDetails($data, $form, $request);

        $nextStepLink = CheckoutPage::find_next_step_link('orderformaddress');
        $this->owner->controller->redirect($nextStepLink);

        return true;
    }
}
