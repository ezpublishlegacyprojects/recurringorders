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
    function hasSubscription( $object_id )
    {
        $result = array( 'result' => XROWRecurringOrderCollection::hasSubscription( $object_id ) );
        return $result;
    }
    function fetchTextAdjectiveArray( )
    {
        $result = array( 'result' => XROWRecurringOrderCollection::getBillingCycleTextArray() );
        return $result;
    }
    function fetchGMNow( )
    {
        $result = array( 'result' => XROWRecurringOrderCollection::now() );
        return $result;
    }
}

?>
