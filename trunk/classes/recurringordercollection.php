<?php
define( 'XROWRECURRINGORDER_PERIOD_ONETIME', 0 );
define( 'XROWRECURRINGORDER_PERIOD_DAY', 1 );
define( 'XROWRECURRINGORDER_PERIOD_WEEK', 2 );
define( 'XROWRECURRINGORDER_PERIOD_MONTH', 3 );
define( 'XROWRECURRINGORDER_PERIOD_QUARTER', 4 );
define( 'XROWRECURRINGORDER_PERIOD_YEAR', 5 );

define( 'XROWRECURRINGORDER_STATUS_DEACTIVATED', 0 );
define( 'XROWRECURRINGORDER_STATUS_ACTIVE', 1 );

define( 'XROWRECURRINGORDER_STATUSTYPE_SUCCESS', 1 );
define( 'XROWRECURRINGORDER_STATUSTYPE_CREDITCARD_EXPIRES', 2 );
include_once( "kernel/classes/ezpersistentobject.php" );

include_once('lib/ezlocale/classes/ezdatetime.php');
include_once( 'extension/recurringorders/classes/recurringorderhistory.php');
include_once( 'extension/recurringorders/classes/recurringorderitem.php');
include_once( 'extension/recurringorders/classes/recurringorderitemoption.php');
include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
include_once( 'kernel/shop/classes/ezshopfunctions.php' );

class XROWRecurringOrderCollection extends eZPersistentObject
{
    function XROWRecurringOrderCollection( $row )
    {
        parent::eZPersistentObject( $row );
    }
    function definition()
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
                                                             'default' => XROWRECURRINGORDER_STATUS_DEACTIVATED,
                                                             'required' => true ),
                                         "created" => array( 'name' => "created",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "last_success" => array( 'name' => "last_success",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "next_date" => array( 'name' => "next_date",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "next_try" => array( 'name' => "next_try",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "send_day" => array( 'name' => "send_day",
                                                                   'datatype' => 'integer',
                                                                   'default' => XROWRecurringOrderCollection::now(),
                                                                   'required' => true ),
                                         'period' => array( 'name' => "period",
                                                                 'datatype' => 'integer',
                                                                 'default' => XROWRECURRINGORDER_PERIOD_MONTHLY,
                                                                 'required' => true ) ),
                      "keys" => array( "id" ),
                      "increment_key" => "id",
                      "function_attributes" => array(
                                                        "list" => "fetchList",
                                                        "user" => "user",
                                                        "internal_next_date" => "internalNextDate"
                                                     ),
                      "class_name" => "XROWRecurringOrderCollection",
                      "sort" => array( "id" => "asc" ),
                      "name" => "xrow_recurring_order_collection" );
    }
    function checkCreditCard()
    {
        $user = $this->attribute( 'user' );
        $userco = $user->attribute( 'contentobject' );
        $dm = $userco->attribute( 'data_map' );
        $data = $dm['creditcard']->attribute( 'content' );
        if ( $data['month'] and $data['year'] )
        {
            $now = new eZDateTime( time() );
            $now->setMonth( $now->month() + 3 );
            $date = eZDateTime::create( -1, -1, -1, $data['month'], -1, $data['year'] );
            if ( !$date->isGreaterThan( $now ) )
            {
                return XROWRECURRINGORDER_STATUSTYPE_CREDITCARD_EXPIRES;
            }
            else
                return true;
        }
        else
        {
            return XROWRECURRINGORDER_ERROR_CREDITCARD_MISSING;
        }
    }
    function hadErrorSince( $days )
    {
        $db = eZDB::instance();
        $date = new eZDateTime( time() );
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
        if ( (int)$this->next_try < time() )
            return true;
        else
            return false;
    }
    function history( $type = XROWRECURRINGORDER_STATUSTYPE_SUCCESS, $orderid = null, $text = null )
    {
        $row = array( 'date' => time(), 'type' => $type, 'data_text' => $text, 'order_id' => $orderid, 'collection_id' => $this->id );
        $item = new XROWRecurringOrderHistory( $row );
        $item->store();

        $date = new eZDateTime( time() );
        $date->setDay( $date->day() + 2 );
        $this->setAttribute( 'next_try', $date->timeStamp() );
        if ( $this->failuresSinceLastSuccess() >= 3 )
        {
            $this->setAttribute( 'status', XROWRECURRINGORDER_STATUS_DEACTIVATED );
            // TODO send mail to user
        }
        $this->store();
    }
    function &user()
    {
        return eZUser::fetch( $this->user_id );
    }
    function createDOMTreefromArray( $name, $array )
    {

        $doc = new eZDOMDocument( $name );
        $root = $doc->createElementNode( $name );
        $keys = array_keys( $array );
        foreach ( $keys as $key )
        {

            if ( is_array( $array[$key] ) )
            {
                //TODO recursive shoudl work too
                // createDOMTreefromArray( $key, $array[$key] )
            }
            else
            {
                $node = $doc->createElementNode( $key );
                $node->appendChild( $doc->createTextNode( $array[$key] ) );
            }

            $root->appendChild( $node );
            unset( $node );
        }
        return $root;
    }
    function now()
    {
        return time();
    }
    function nextDate( )
    {
        if ( $this->last_success )
        {
            $time = $this->last_success;
        }
        else
        {
            $time = $this->created;
        }
        $result = $this->nextDateHelper( $time );
        if ( $result < XROWRecurringOrderCollection::now() )
            $result = $this->nextDateHelper( XROWRecurringOrderCollection::now() );
        return $result;
    }
    function internalNextDate( )
    {
        if ( $this->last_success )
        {
            $time = $this->last_success;
        }
        else
        {
            $time = $this->created;
        }
        $result = $this->nextDateHelper( $time );
        return $result;
    }
    function nextDateHelper( $time )
    {
        $datetime = new eZDateTime( $time );
        if ( $this->period == XROWRECURRINGORDER_PERIOD_MONTHLY )
        {
            $datetime->setMonth( $datetime->month() + 1 );
        }
        $datetime->setDay( $this->send_day );
        $datetime->setSecond( 0 );
        $datetime->setMinute( 0 );
        $datetime->setHour( 0 );
        return $datetime->timeStamp();
    }
    function isDue()
    {
        if ( $this->attribute( 'next_date' ) < XROWRecurringOrderCollection::now() )
            return true;
        else
            return false;
    }

    function createOrder()
    {
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
        $recurringitemlist = $this->fetchList();
        foreach ( $recurringitemlist as $recurringitem )
        {
            $object = $recurringitem->attribute( 'object' );
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
            $item = eZProductCollectionItem::create( $productCollectionID );
            $item->setAttribute( 'name', $object->attribute( 'name' ) );
            $item->setAttribute( "contentobject_id", $object->attribute( 'id' ) );
            $item->setAttribute( "item_count", $recurringitem->attribute( 'amount' ) );
            $item->setAttribute( "price", $price );
            $item->store();
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
    function fetchByUser( $user_id = null )
    {
        if ( $user_id === null )
               $user_id = eZUser::currentUserID();
        return eZPersistentObject::fetchObject( XROWRecurringOrderCollection::definition(),
                null, array( 'user_id' => $user_id ), true );

    }
    function fetchAll()
    {
        return eZPersistentObject::fetchObjectList( XROWRecurringOrderCollection::definition(),
                null, null, true );

    }
    function fetch( $collection_id )
    {
    	return eZPersistentObject::fetchObject( XROWRecurringOrderCollection::definition(),
                null, array( "id" => $collection_id ), true );
    }
    function fetchList()
    {
        return eZPersistentObject::fetchObjectList( XROWRecurringOrderItem::definition(),
                null, array( 'collection_id' => $this->id ), true );
    }
    function createNew( $user_id = null )
    {
        if ( $user_id === null )
            $user_id = eZUser::currentUserID();
        $collection = new XROWRecurringOrderCollection( array( 'user_id' => $user_id, 'period' => XROWRECURRINGORDER_PERIOD_MONTHLY, 'send_day' => 1, 'status' => XROWRECURRINGORDER_STATUS_DEACTIVATED, 'created' => XROWRecurringOrderCollection::now() ) );
        $collection->setAttribute( 'next_date', $collection->nextDate() );
        $collection->store();
        return $collection;
    }
    function add( $object_id, $variations = null, $amount )
    {
        return XROWRecurringOrderItem::add( $this->id, $object_id, $variations, $amount );
    }

    /*!
     \static
     fetch text array of available billing cycles
     This can be called like XROWRecurringOrderCollection::getBillingCycleTextArray()
    */
    function getBillingCycleTextArray()
    {
        $result = array (
            XROWRECURRINGORDER_PERIOD_ONETIME   => ezi18n( 'kernel/classes/recurringordercollection', "one time fee" ),
            XROWRECURRINGORDER_PERIOD_DAY       => ezi18n( 'kernel/classes/recurringordercollection', "day(s)" ),
            XROWRECURRINGORDER_PERIOD_WEEK      => ezi18n( 'kernel/classes/recurringordercollection', "weeks(s)" ),
            XROWRECURRINGORDER_PERIOD_MONTH     => ezi18n( 'kernel/classes/recurringordercollection', "month(s)" ),
            XROWRECURRINGORDER_PERIOD_QUARTER   => ezi18n( 'kernel/classes/recurringordercollection', "quarter(s)" ),
            XROWRECURRINGORDER_PERIOD_YEAR      => ezi18n( 'kernel/classes/recurringordercollection', "year(s)" )
        );
        return $result;
    }

    /*!
     \static
     fetch text array of available billing cycles
     This can be called like XROWRecurringOrderCollection::getBillingCycleTextAdjectiveArray()
    */
    function getBillingCycleTextAdjectiveArray()
    {
        $result = array (
            XROWRECURRINGORDER_PERIOD_ONETIME   => ezi18n( 'kernel/classes/recurringordercollection', "one time fee" ),
            XROWRECURRINGORDER_PERIOD_DAY       => ezi18n( 'kernel/classes/recurringordercollection', "daily" ),
            XROWRECURRINGORDER_PERIOD_WEEK      => ezi18n( 'kernel/classes/recurringordercollection', "weekly" ),
            XROWRECURRINGORDER_PERIOD_MONTH     => ezi18n( 'kernel/classes/recurringordercollection', "monthly" ),
            XROWRECURRINGORDER_PERIOD_QUARTER   => ezi18n( 'kernel/classes/recurringordercollection', "quarterly" ),
            XROWRECURRINGORDER_PERIOD_YEAR      => ezi18n( 'kernel/classes/recurringordercollection', "yearly" )
        );
        return $result;
    }

    /*!
     \static
     fetch description text for the given period
     This can be called like XROWRecurringOrderCollection::getBillingCycleText( $period, $quantity )
    */
    function getBillingCycleText ( $period, $quantity = 0 )
    {
        switch ( $period )
        {
            case XROWRECURRINGORDER_PERIOD_ONETIME:
            {
                return ezi18n( 'kernel/classes/recurringordercollection', "one time fee" );
            }break;
            case XROWRECURRINGORDER_PERIOD_DAY:
            {
                if ( $quantity == 1 )
                    return ezi18n( 'kernel/classes/recurringordercollection', "day" );
                else
                    return ezi18n( 'kernel/classes/recurringordercollection', "days" );
            }break;
            case XROWRECURRINGORDER_PERIOD_WEEK:
            {
                if ( $quantity == 1 )
                    return ezi18n( 'kernel/classes/recurringordercollection', "week" );
                else
                    return ezi18n( 'kernel/classes/recurringordercollection', "weeks" );
            }break;
            case XROWRECURRINGORDER_PERIOD_MONTH:
            {
                if ( $quantity == 1 )
                    return ezi18n( 'kernel/classes/recurringordercollection', "month" );
                else
                    return ezi18n( 'kernel/classes/recurringordercollection', "months" );
            }break;
            case XROWRECURRINGORDER_PERIOD_QUARTER:
            {
                if ( $quantity == 1 )
                    return ezi18n( 'kernel/classes/recurringordercollection', "quarter" );
                else
                    return ezi18n( 'kernel/classes/recurringordercollection', "quarters" );
            }break;
            case XROWRECURRINGORDER_PERIOD_YEAR:
            {
                if ( $quantity == 1 )
                    return ezi18n( 'kernel/classes/recurringordercollection', "year" );
                else
                    return ezi18n( 'kernel/classes/recurringordercollection', "years" );
            }break;
            default:
            {
                return "";
            }
        }
    }

}
?>