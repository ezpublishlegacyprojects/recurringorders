<?php
define( 'XROWRECURRINGORDER_CYCLE_ONETIME', 0 );
define( 'XROWRECURRINGORDER_CYCLE_DAY', 1 );
define( 'XROWRECURRINGORDER_CYCLE_WEEK', 2 );
define( 'XROWRECURRINGORDER_CYCLE_MONTH', 3 );
define( 'XROWRECURRINGORDER_CYCLE_QUARTER', 4 );
define( 'XROWRECURRINGORDER_CYCLE_YEAR', 5 );

define( 'XROWRECURRINGORDER_STATUS_DEACTIVATED', 0 );
define( 'XROWRECURRINGORDER_STATUS_ACTIVE', 1 );

define( 'XROWRECURRINGORDER_STATUSTYPE_SUCCESS', 1 );
define( 'XROWRECURRINGORDER_STATUSTYPE_CREDITCARD_EXPIRES', 2 );
define( 'XROWRECURRINGORDER_STATUSTYPE_FAILURE', 2 );

class XROWRecurringOrderCollection extends eZPersistentObject
{
    function XROWRecurringOrderCollection( $row )
    {
        parent::eZPersistentObject( $row );
    }

    static function definition()
    {
        return array( "fields" => array(
                                         "user_id" => array( 'name' => "user_id",
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         "id" => array( 'name' => "id",
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         "status" => array( 'name' => "status",
                                                             'datatype' => 'integer',
                                                             'default' => XROWRECURRINGORDER_STATUS_ACTIVE,
                                                             'required' => true ),
                                         "created" => array( 'name' => "created",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "last_run" => array('name' => "last_run",
                                                             'datatype' => 'integer',
                                                             'default' => null,
                                                             'required' => false ),
                                         "next_try" => array( 'name' => "next_try",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ) ),
                      "keys" => array( "id" ),
                      "increment_key" => "id",
                      "function_attributes" => array(
                                                        "list" => "fetchList",
                                                        'now' => 'now',
                                                        "user" => "user",
                                                        "internal_next_date" => "internalNextDate"
                                                     ),
                      "class_name" => "XROWRecurringOrderCollection",
                      "sort" => array( "id" => "asc" ),
                      "name" => "xrow_recurring_order_collection" );
    }
    /**
     * Time function needed for testing mod_fcgid doesn`t restart with a future date.
     *
     * @return int
     */
    static function now()
    {
        #$time = 1189002190;
        #$time = gmmktime( 0,0,0,9,5,2008 );
        $time = gmmktime( 0,0,0 );
        return $time;
    }
    function markRun()
    {
    	$this->setAttribute( 'last_run', XROWRecurringOrderCollection::now() );
    	$this->store();
    }

    function getAllCycleTypes()
    {
    	return array( XROWRECURRINGORDER_CYCLE_ONETIME, XROWRECURRINGORDER_CYCLE_DAY, XROWRECURRINGORDER_CYCLE_WEEK, XROWRECURRINGORDER_CYCLE_MONTH,XROWRECURRINGORDER_CYCLE_QUARTER,XROWRECURRINGORDER_CYCLE_YEAR);
    }

    function checkCreditCard( $monthsToExpiryDate = 3)
    {
        $user = $this->attribute( 'user' );
        if ( !is_object( $user ) )
            return false;
        $list = $this->fetchList();
        if ( count( $list ) == 0 )
            return false;
    
        $userco = $user->attribute( 'contentobject' );
        $dm = $userco->attribute( 'data_map' );
        $data = $dm['creditcard']->attribute( 'content' );
        if ( $data['month'] and $data['year'] )
        {
            $now = new eZDateTime( mktime() );
            $now->setMonth( $now->month() + $monthsToExpiryDate );
            $date = eZDateTime::create( -1, -1, -1, $data['month'], -1, $data['year'] );
            if ( !$date->isGreaterThan( $now ) )
            {
                return XROWRECURRINGORDER_STATUSTYPE_CREDITCARD_EXPIRES;
            }
            else
            {
                return true;
            }
        }
        elseif ( $data[XROWCREDITCARD_KEY_TYPE] == XROWCREDITCARD_TYPE_EUROCARD )
        {
            return true;
        }
        else
        {
            return XROWRECURRINGORDER_ERROR_CREDITCARD_MISSING;
        }
    }

    function creditCardExpiryDate()
    {
    	$user = $this->attribute( 'user' );
        if ( !is_object( $user ) )
            return false;
        $userco = $user->attribute( 'contentobject' );
        $dm = $userco->attribute( 'data_map' );
        $data = $dm['creditcard']->attribute( 'content' );
        if ( $data['month'] and $data['year'] )
        {
            $date = eZDateTime::create( -1, -1, -1, $data['month'], -1, $data['year'] );
            return $date;
        }
        else
        {
            return null;
        }
    }

    function hadErrorSince( $days )
    {
        $db = eZDB::instance();
        $date = new eZDateTime( mktime() );
        $date->setDay( $date->day() - $days );

        $result = $db->arrayQuery( "SELECT count( id ) as counter FROM xrow_recurring_order_history x WHERE x.date > " . $date->timeStamp() . " and x.collection_id = " . $this->id );
        return ( $result[0]['counter'] > 0 );
    }

    function failuresSinceLastSuccess()
    {
        $db = eZDB::instance();
        $date = $this->last_success;
        if ( !$date )
            $date = '0';
        $result = $db->arrayQuery( "SELECT count( id ) as counter FROM xrow_recurring_order_history x WHERE x.date > " . $date . " and x.collection_id = " . $this->id );
        return $result[0]['counter'];
    }

    function canTry()
    {
        if ( (int)$this->next_try <= XROWRecurringOrderCollection::now() )
            return true;
        else
            return false;
    }

    function &user()
    {
        return eZUser::fetch( $this->user_id );
    }

    function createOrder( $recurringitemlist )
    {
        if ( count( $recurringitemlist ) == 0 )
            return false;
        include_once( "kernel/classes/ezbasket.php" );
        include_once( "kernel/classes/ezvattype.php" );
        include_once( "kernel/classes/ezorder.php" );
        include_once( "kernel/classes/ezproductcollection.php" );
        include_once( "kernel/classes/ezproductcollectionitem.php" );
        include_once( "kernel/classes/ezproductcollectionitemoption.php" );
        // Make order
        $productCollection = eZProductCollection::create();
        $productCollection->store();
        $productCollectionID = $productCollection->attribute( 'id' );

        foreach ( $recurringitemlist as $recurringitem )
        {
            $handler = $recurringitem->attribute( 'handler' );
            $object = $recurringitem->attribute( 'object' );
            
            if ( !$handler )
            {
                $attributes = $object->contentObjectAttributes();
                $priceFound = false;
                foreach ( $attributes as $attribute )
                {
                    $dataType = $attribute->dataType();
                    if ( eZShopFunctions::isProductDatatype( $dataType->isA() ) )
                    {
                        $priceObj =& $attribute->content();
                        $price = $priceObj->attribute( 'price' );
                        $priceFound = true;
                    }
                }
            }
            else
            {
                $price = $handler->getPrice();
            }
            $item = eZProductCollectionItem::create( $productCollectionID );
            $item->setAttribute( 'name', $object->attribute( 'name' ) );
            $item->setAttribute( "contentobject_id", $object->attribute( 'id' ) );
            $item->setAttribute( "item_count", $recurringitem->attribute( 'amount' ) );
            $item->setAttribute( "price", $price );
            
            
            $item->store();
            if ( !$handler )
            {
                $optionList = $recurringitem->options();
                foreach ( $optionList as $optionData )
                {
                        if ( $optionData )
                        {
                            $optionData['additional_price'] = eZShopFunctions::convertAdditionalPrice( $currency, $optionData['additional_price'] );
                            $optionItem = eZProductCollectionItemOption::create( $item->attribute( 'id' ), $optionData['id'], $optionData['name'],
                                                                             $optionData['value'], $optionData['additional_price'], $attributeID );
                            $optionItem->store();
                            $price += $optionData['additional_price'];
                        }
                }
                $item->setAttribute( "price", $price );
                $item->store();
            }
        }

        $user = $this->attribute( 'user' );
        $userID = $user->attribute( 'contentobject_id' );

        include_once( 'kernel/classes/ezorderstatus.php' );
        $time = XROWRecurringOrderCollection::now();
        $order = new eZOrder( array( 'productcollection_id' => $productCollectionID,
                                     'user_id' => $userID,
                                     'is_temporary' => 1,
                                     'created' => $time,
                                     'status_id' => EZ_ORDER_STATUS_PENDING,
                                     'status_modified' => $time,
                                     'status_modifier_id' => $userID
                                     ) );

        $db =& eZDB::instance();
        $db->begin();
        $order->store();

        $orderID = $order->attribute( 'id' );
        $this->setAttribute( 'order_id', $orderID );
        $this->store();
        $db->commit();

        return $order;
    }

    static function fetchByUser( $user_id = null )
    {
        if ( $user_id === null )
               $user_id = eZUser::currentUserID();
        return eZPersistentObject::fetchObjectList( XROWRecurringOrderCollection::definition(),
                null, array( 'user_id' => $user_id ), true );

    }
    /**
     *
     * @return array array of XROWRecurringOrderCollection
     */
    static function fetchAll()
    {
        return eZPersistentObject::fetchObjectList( XROWRecurringOrderCollection::definition(),
                null, null, true );

    }
    /**
     *
     * @return XROWRecurringOrderCollection
     */
    function fetch( $collection_id )
    {
    	return eZPersistentObject::fetchObject( XROWRecurringOrderCollection::definition(),
                null, array( "id" => $collection_id ), true );
    }
    /**
     *
     * @return array array of XROWRecurringOrderCollection
     */
    function fetchDueList()
    {
        $list = eZPersistentObject::fetchObjectList( XROWRecurringOrderItem::definition(),
                null, array( 'collection_id' => $this->id ), true );
        $result = array();
        foreach ( $list as $item )
        {
            if ( $item->isDue() )
                $result[] = $item;
        }
        return $result;
    }

    function isDue()
    {
    	if ( count( $this->fetchDueList() ) > 0 )
    	   return true;
    	else
    	   return false;
    }
    /**
     *
     * @return array array of XROWRecurringOrderCollection
     */
    function fetchList()
    {
        return eZPersistentObject::fetchObjectList( XROWRecurringOrderItem::definition(),
                null, array( 'collection_id' => $this->id ), true );
    }
    /**
     *
     * @return XROWRecurringOrderCollection
     */
    function createNew( $user_id = null )
    {
        if ( $user_id === null )
            $user_id = eZUser::currentUserID();
        $collection = new XROWRecurringOrderCollection( array( 'user_id' => $user_id, 'status' => XROWRECURRINGORDER_STATUS_ACTIVE, 'created' => XROWRecurringOrderCollection::now() ) );
        $collection->store();
        return $collection;
    }

    function add( $object_id,
                  $variations = null,
                  $amount = 0,
                  $cycle_unit = null,
                  $cycle = 1,
                  $isSubscription = false,
                  $start = 0,
                  $end = 0,
                  $canceled = 0,
                  $data = array(),
                  $subscriptionIdentifier = '',
                  $status = XROW_SUBSCRIPTION_STATUS_UNDEFINED
                   )
    {
        return XROWRecurringOrderItem::add( $this->id,
                                            $object_id,
                                            $variations,
                                            $amount,
                                            $cycle,
                                            $cycle_unit,
                                            $isSubscription,
                                            $start,
                                            $end,
                                            $canceled,
                                            $data,
                                            $subscriptionIdentifier,
                                            $status );
    }
    /**
     *
     * @return void
     */
    function addHistory( $type = XROWRECURRINGORDER_STATUSTYPE_SUCCESS, $text = null, $orderid = null )
    {
        $db =& eZDB::instance();
        $db->begin();
        $ini = eZINI::instance( 'recurringorders.ini' );
        $row = array( 'date' => gmmktime(), 'type' => $type, 'data_text' => $text, 'order_id' => $orderid, 'collection_id' => $this->id );
        $item = new XROWRecurringOrderHistory( $row );
        $item->store();
        if ( $type != XROWRECURRINGORDER_STATUSTYPE_SUCCESS )
        {
            $date = new eZDateTime( XROWRecurringOrderCollection::now() );
            $date->setDay( $date->day() + $ini->variable( 'GeneralSettings', 'DaysAfterRetryOnError' ) );
            $this->setAttribute( 'next_try', $date->timeStamp() );
            if ( ( $ini->variable( 'RecurringOrderSettings', 'FailuresTillPause' ) > 1 ) and $this->failuresSinceLastSuccess() >= $ini->variable( 'RecurringOrderSettings', 'FailuresTillPause' ) )
            {
                $this->setAttribute( 'status', XROWRECURRINGORDER_STATUS_DEACTIVATED );
                $this->sendMail( 'design:recurringorders/email/manyfailures.tpl' );
            }
            $this->store();
        }
        $db->commit();
    }

    function hasSubscription( $object_id )
    {
        $return = XROWRecurringOrderItem::fetchObject( XROWRecurringOrderItem::definition(), null, array( 'contentobject_id' => $object_id, 'is_subscription' => '1' ) );
        if( isset( $return[0] ) )
            return true;
        else
            return false;
    }

    function sendMail( $template, $params = array() )
    {
        $user = $this->attribute( 'user' );
        $userobject = $user->attribute( 'contentobject' );
        $ini = eZINI::instance();
        include_once( "lib/ezutils/classes/ezmail.php" );
        include_once( "lib/ezutils/classes/ezmailtransport.php" );
    	$mail = new eZMail();

        $mail->setSender( $ini->variable( 'MailSettings', 'AdminEmail' ) );
        $mail->setReceiver( $user->attribute( 'email' ), $userobject->attribute( 'name' ) );

        include_once( 'kernel/common/template.php' );
        // fetch text from mail template
        $mailtpl =& templateInit();
        foreach ( $params as $key => $value )
        {
            $mailtpl->setVariable( $key, $value );
        }
        $mailtext =& $mailtpl->fetch( $template );
        $subject = $mailtpl->variable( 'subject' );
        $mail->setSubject( $subject );
        $mail->setBody( $mailtext );

        // mail was sent ok
        if ( eZMailTransport::send( $mail ) )
        {
            return true;
        }
        else
        {
            eZDebug::writeError( "Failed to send mail.", 'Recurring orders' );
            return false;
        }
    }

    /*!
     \static
     fetch text array of available billing cycles
     This can be called like XROWRecurringOrderCollection::getBillingCycleTextArray()
    */
    function getBillingCycleTextArray()
    {
        if ( !isset( $GLOBALS['xrowBillingCycleTextArray'] ) )
        {
            $GLOBALS['xrowBillingCycleTextArray'] = array (
                XROWRECURRINGORDER_CYCLE_ONETIME   => ezi18n( 'kernel/classes/recurringordercollection', "one time" ),
                XROWRECURRINGORDER_CYCLE_DAY       => ezi18n( 'kernel/classes/recurringordercollection', "day(s)" ),
                XROWRECURRINGORDER_CYCLE_WEEK      => ezi18n( 'kernel/classes/recurringordercollection', "weeks(s)" ),
                XROWRECURRINGORDER_CYCLE_MONTH     => ezi18n( 'kernel/classes/recurringordercollection', "month(s)" ),
                XROWRECURRINGORDER_CYCLE_QUARTER   => ezi18n( 'kernel/classes/recurringordercollection', "quarter(s)" ),
                XROWRECURRINGORDER_CYCLE_YEAR      => ezi18n( 'kernel/classes/recurringordercollection', "year(s)" )
                );
            $ini = eZINI::instance( 'recurringorders.ini' );
            foreach ( $ini->variable( 'RecurringOrderSettings','DisabledCycles' ) as $disabled )
            {
                unset( $GLOBALS['xrowBillingCycleTextArray'][$disabled] );
            }
        }
        return $GLOBALS['xrowBillingCycleTextArray'];
    }

    /*!
     \static
     fetch text array of available billing cycles
     This can be called like XROWRecurringOrderCollection::getBillingCycleTextAdjectiveArray()
    */
    function getBillingCycleTextAdjectiveArray()
    {

        if ( !isset( $GLOBALS['xrowBillingCycleTextAdjectiveArray'] ) )
        {
            $GLOBALS['xrowBillingCycleTextAdjectiveArray'] = array (
                    XROWRECURRINGORDER_CYCLE_ONETIME   => ezi18n( 'kernel/classes/recurringordercollection', "one time" ),
                    XROWRECURRINGORDER_CYCLE_DAY       => ezi18n( 'kernel/classes/recurringordercollection', "daily" ),
                    XROWRECURRINGORDER_CYCLE_WEEK      => ezi18n( 'kernel/classes/recurringordercollection', "weekly" ),
                    XROWRECURRINGORDER_CYCLE_MONTH     => ezi18n( 'kernel/classes/recurringordercollection', "monthly" ),
                    XROWRECURRINGORDER_CYCLE_QUARTER   => ezi18n( 'kernel/classes/recurringordercollection', "quarterly" ),
                    XROWRECURRINGORDER_CYCLE_YEAR      => ezi18n( 'kernel/classes/recurringordercollection', "yearly" )
                );
            $ini = eZINI::instance( 'recurringorders.ini' );
            foreach ( $ini->variable( 'RecurringOrderSettings','DisabledCycles' ) as $disabled )
            {
                unset( $GLOBALS['xrowBillingCycleTextAdjectiveArray'][$disabled] );
            }
        }
        return $GLOBALS['xrowBillingCycleTextAdjectiveArray'];
    }

    /*!
     \static
     fetch description text for the given period
     This can be called like XROWRecurringOrderCollection::getBillingCycleText( $period, $quantity )
    */
    function getBillingCycleText ( $period, $quantity = 0 )
    {

        if ( !isset( $GLOBALS['xrowBillingCycleText'] ) )
        {
            $GLOBALS['xrowBillingCycleText'] = array (
                XROWRECURRINGORDER_CYCLE_ONETIME   => array ( 0 => ezi18n( 'kernel/classes/recurringordercollection', "one time" ),
                                                               1 => ezi18n( 'kernel/classes/recurringordercollection', "one time" ) ),
                XROWRECURRINGORDER_CYCLE_DAY       => array ( 0 => ezi18n( 'kernel/classes/recurringordercollection', "days" ),
                                                               1 => ezi18n( 'kernel/classes/recurringordercollection', "day" ) ),
                XROWRECURRINGORDER_CYCLE_WEEK      => array ( 0 => ezi18n( 'kernel/classes/recurringordercollection', "weeks" ),
                                                               1 => ezi18n( 'kernel/classes/recurringordercollection', "week" ) ),
                XROWRECURRINGORDER_CYCLE_MONTH     => array ( 0 => ezi18n( 'kernel/classes/recurringordercollection', "months" ),
                                                               1 => ezi18n( 'kernel/classes/recurringordercollection', "month" ) ),
                XROWRECURRINGORDER_CYCLE_QUARTER   => array ( 0 => ezi18n( 'kernel/classes/recurringordercollection', "quarters" ),
                                                               1 => ezi18n( 'kernel/classes/recurringordercollection', "quarter" ) ),
                XROWRECURRINGORDER_CYCLE_YEAR      => array ( 0 => ezi18n( 'kernel/classes/recurringordercollection', "years" ),
                                                               1 => ezi18n( 'kernel/classes/recurringordercollection', "year" ) )

            );
            $ini = eZINI::instance( 'recurringorders.ini' );
            foreach ( $ini->variable( 'RecurringOrderSettings','DisabledCycles' ) as $disabled )
            {
                unset( $GLOBALS['xrowBillingCycleText'][$disabled] );
            }
        }

        if ( $quantity == 1 )
        {
            if ( isset( $GLOBALS['xrowBillingCycleText'][$period][1] ) )
                return $GLOBALS['xrowBillingCycleText'][$period][1];
        }
        else
        {
            if ( isset( $GLOBALS['xrowBillingCycleText'][$period][0] ) )
                return $GLOBALS['xrowBillingCycleText'][$period][0];
        }

        return '';

    }
}
?>
