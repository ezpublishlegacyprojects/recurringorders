<?php

include_once( 'kernel/error/errors.php' );
include_once( 'extension/recurringorders/classes/recurringordercollection.php');

class recurringordersFunctionCollection
{
    /*!
     Constructor
    */
    function recurringordersFunctionCollection()
    {
    }

    function fetchTextAdjectiveArray( )
    {
        $result = array( 'result' => XROWRecurringOrderCollection::getBillingCycleTextArray() );
        return $result;
    }
}

?>
