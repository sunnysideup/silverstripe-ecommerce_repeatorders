<?php

/**
 * An collection of functions shared by RepeatOrdersPage and AcccountPage extension.
 *
 */
trait RepeatOrdersTrait
{
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
            ->filter(['MemberID' => $memberID])
            ->sort('Created',  'DESC');
    }
}
