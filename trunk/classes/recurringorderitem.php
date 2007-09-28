<?php
include_once( 'extension/recurringorders/classes/recurringordercollection.php');

class XROWRecurringOrderItem extends eZPersistentObject
{
    function XROWRecurringOrderItem( $row = array() )
    {
        parent::eZPersistentObject( $row );
    }
    function definition()
    {
        return array( "fields" => array(
                                         "item_id" => array( 'name' => "item_id",
                                                                      'datatype' => 'integer',
                                                                      'default' => null,
                                                                      'required' => true ),
                                         "collection_id" => array( 'name' => "collection_id",
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         "contentobject_id" => array( 'name' => "contentobject_id",
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         "cycle" => array( 'name' => "cycle",
                                                                   'datatype' => 'integer',
                                                                   'default' => 1,
                                                                   'required' => true ),
                                         "cycle_unit" => array( 'name' => "cycle_unit",
                                                                   'datatype' => 'integer',
                                                                   'default' => XROWRECURRINGORDER_CYCLE_MONTH,
                                                                   'required' => true ),
                                         "is_subscription" => array( 'name' => "is_subscription",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "subscription_handler" => array( 'name' => "subscription_handler",
                                                             'datatype' => 'string',
                                                             'default' => null,
                                                             'required' => true ),
                                         "last_success" => array( 'name' => "last_success",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "next_date" => array( 'name' => "next_date",
                                                             'datatype' => 'integer',
                                                             'default' => 0,
                                                             'required' => true ),
                                         "created" => array( 'name' => "created",
                                                                   'datatype' => 'integer',
                                                                   'default' => time(),
                                                                   'required' => true ),
                                         'amount' => array( 'name' => "amount",
                                                                 'datatype' => 'integer',
                                                                 'default' => '0',
                                                                 'required' => true ),
                                         "start" => array( 'name' => "start",
                                                          'datatype' => 'integer',
                                                          'default' => 0,
                                                          'required' => false ),
                                         "canceled" => array( 'name' => "canceled",
                                                          'datatype' => 'integer',
                                                          'default' => 0,
                                                          'required' => false ),
                                         "end" => array( 'name' => "end",
                                                          'datatype' => 'integer',
                                                          'default' => 0,
                                                          'required' => false ),
                                         "status" => array( 'name' => "status",
                                                          'datatype' => 'integer',
                                                          'default' => 0,
                                                          'required' => true ),
                                         "data_text" => array( 'name' => "data_text",
                                                          'datatype' => 'text',
                                                          'default' => '',
                                                          'required' => false ),
                                        ),
                      "keys" => array( "item_id" ),
                      "increment_key" => "item_id",
                      "function_attributes" => array(
                                                        "collection" => "collection",
                                                        "object" => "object",
                                                        'days_in_cycle' => 'daysInCycle',
                                                        "price_per_item" => "pricePerItem",
                                                        "price" => "price",
                                                        "options" => "options"
                                                     ),
                      "class_name" => "XROWRecurringOrderItem",
                      "sort" => array( "created" => "asc" ),
                      "name" => "xrow_recurring_order_item" );
    }
    
    function collection()
    {
    	return XROWRecurringOrderCollection::fetch( $this->collection_id );
    }

    function &attribute( $name )
    {
        switch ( $name )
        {
            case 'last_run':
            {
                $c = $this->attribute( 'collection' );
                $return = $c->attribute( 'last_run' );
            }break;

            case 'content':
            {
                $content = array();
                if ( $this->attribute( 'is_subscription' ) == 1 )
                {
                    $subscriptionHandler = new xrowSubscription( $this->attribute( 'subscription_handler' ) );

                    $handler = $subscriptionHandler->getHandler();

                    if ( is_object( $handler ) )
                        $content = $handler->createArrayfromXML( $this->attribute( 'data_text' )  );
                }
                return $content;
            }break;

            default:
            {
                $return = parent::attribute( $name );
            }break;
        }
        return $return;
    }

    function setAttribute( $name, $value, $updatenextdate = false )
    {
        switch ( $name )
        {
            case 'cycle_unit':
            case 'cycle':
            case 'order_date':
                if ( $name == 'cycle' and $value < 1 )
                    $value = 1;
                $return = parent::setAttribute( $name, $value );
                if ( $updatenextdate == true )
                    $this->setAttribute( 'next_date', $this->nextDate() );
            break;
            case 'last_success':
                $return = parent::setAttribute( $name, $value );
                $this->setAttribute( 'next_date', $this->nextDate() );
            break;
            case 'content':
            {
                $dataText = '';
                $handlerIdentifier = $this->attribute( 'subscription_handler' );
                if ( strlen( $handlerIdentifier ) > 0 and
                     $this->attribute( 'is_subscription' ) == 1 )
                {
                    $subscriptionHandler = new xrowSubscription( $handlerIdentifier );
                    $handler = $subscriptionHandler->getHandler();
                    if ( is_object( $handler ) )
                    {
                        $handler = $subscriptionHandler->getHandler();

                        $domnode = $handler->createDOMTreefromArray( 'item-info', $value );
                        $doc = new eZDOMDocument();
                        $doc->setName( "ItemInfo" );
                        $doc->setRoot( $domnode );
                        $dataText = $doc->toString();
                    }
                }
                $return = parent::setAttribute( 'data_text', $dataText );
            }break;
            default:
                $return = parent::setAttribute( $name, $value );
            break;
        }
        return $return;
    }
    /**
     * Enter description here...
     *
     * @access public
     * @return boolean
     */
    function isValid()
    {
        $object = $this->attribute( 'object' );
        if ( !is_object( $object ) )
            return false;
        else 
            return true;
    }
    function forwardNextDate( $toTime )
    {
        if ( $this->attribute( 'last_success' ) )
            $nextdate = $this->attribute( 'last_success' );
        else
            $nextdate = $this->attribute( 'created' );
        if ( $this->cycle_unit != XROWRECURRINGORDER_CYCLE_ONETIME )
        {
            if ( $this->is_subscription )
            {
                $nextdate = $this->nextDateHelper( $nextdate );
            }
            else
            {
                while( $toTime >= $nextdate )
                {
                    $nextdate = $this->nextDateHelper( $nextdate );
                }
            }
        }

        return $nextdate;
    }

    function nextDate()
    {
        return $this->forwardNextDate( XROWRecurringOrderCollection::now() );
    }

    function daysInCycle()
    {
    	if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_DAY )
        {
            $amount = $this->cycle;
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_WEEK )
        {
            $amount = $this->cycle * 7;
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_MONTH )
        {
            $amount = $this->cycle * 30;
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_QUARTER )
        {
            $amount = $this->cycle * 30 * 3;
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_YEAR )
        {
            $amount = $this->cycle * 30 * 3 * 4;
        }
        for ( $i = 1; $i <= $amount; $i++)
        {
            $days[$i-1] = $i;
        }
        return $days;
    }

    function nextDateHelper( $time )
    {
        $datetime = new eZDateTime( $time );
        if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_MONTH )
        {
                $datetime->setMonth( $datetime->month() + $this->cycle );
                $datetime->setDay( $datetime->day() );
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_DAY )
        {
                $datetime->setMonth( $datetime->month() );
                $datetime->setDay( $datetime->day() + $this->cycle );
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_WEEK )
        {

                $datetime->setMonth( $datetime->month() );
                $datetime->setDay( $datetime->day() + ( $this->cycle * 7 ) );
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_QUARTER )
        {
                $datetime->setMonth( $datetime->month() + ( $this->cycle * 3 ) );
                $datetime->setDay( $datetime->day() );
        }
        else if ( $this->cycle_unit == XROWRECURRINGORDER_CYCLE_YEAR )
        {
                $datetime->setYear( $datetime->year() +  $this->cycle );
                $datetime->setMonth( $datetime->month() );
                $datetime->setDay( $datetime->day() );
        }
        return $datetime->timeStamp();
    }

    function isDue()
    {
        if ( $this->next_date < XROWRecurringOrderCollection::now() )
            return true;
        else
            return false;
    }

    function object()
    {
        $object = eZContentObject::fetch( $this->contentobject_id );
        return $object;
    }

    function fetch( $item_id )
    {
        return eZPersistentObject::fetchObject( XROWRecurringOrderItem::definition(),
                null, array( "item_id" => $item_id ) );
    }

    function price()
    {
        return $this->pricePerItem() * $this->amount;
    }

    function pricePerItem()
    {
        $currency = eZShopFunctions::preferredCurrencyCode();
        $object = $this->attribute( 'object' );
        $attributes = $object->contentObjectAttributes();

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
        $optionList = $this->options();
        foreach ( $optionList as $optionData )
        {
            if ( $optionData )
            {
                $optionData['additional_price'] = eZShopFunctions::convertAdditionalPrice( $currency, $optionData['additional_price'] );
                $price += $optionData['additional_price'];
            }
        }
        return $price;
    }

    function options()
    {
        $optionData = array();
    	$options = eZPersistentObject::fetchObjectList( XROWRecurringOrderItemOption::definition(), null, array( "item_id" => $this->item_id ) );
    	foreach ( $options as $option )
    	{
            $object = $this->attribute( 'object' );
            $attribute = eZContentObjectAttribute::fetch( $option->variation_id, $object->attribute( 'current_version' ) );
            $dataType = $attribute->dataType();
            $productItem = null;
            $optionData[] = $dataType->productOptionInformation( $attribute, $option->option_id, $productItem );
    	}
    	return $optionData;
    }

    function itemOptions()
    {
    	return eZPersistentObject::fetchObjectList( XROWRecurringOrderItemOption::definition(), null, array( "item_id" => $this->item_id ) );
    }

    function remove()
    {
        foreach( $this->itemOptions() as $option )
        {
            $option->remove();
        }
        parent::remove();
    }

    function add( $collection_id,
                  $object_id,
                  $variations = null,
                  $amount = 1,
                  $cycle = 1,
                  $cycle_unit = null,
                  $isSubscription = false,
                  $start = 0,
                  $end = 0,
                  $canceled = 0,
                  $data = array(),
                  $subscriptionIdentifier = '',
                  $status = XROW_SUBSCRIPTION_STATUS_UNDEFINED )
    {
        $item = new XROWRecurringOrderItem();

        if ( $cycle_unit === null )
        {
            $ini = eZINI::instance( 'recurringorders.ini' );
            $cycle_unit = (int)$ini->variable( 'RecurringOrderSettings', 'DefaultCycle' );
        }
        if ( !$amount ) // else we need a subscription handler
            return false;

        if ( !is_numeric( $object_id ) or $object_id <= 0 )
            return false;

        $dataText = '';
        if ( $isSubscription )
        {
            $item->setAttribute( 'is_subscription', 1 );
            $item->setAttribute( 'subscription_handler', $subscriptionIdentifier );
            $item->setAttribute( 'content', $data );
        }
        else
            $item->setAttribute( 'is_subscription', 0 );

        if ( $start == 0 )
            $start = XROWRecurringOrderCollection::now();

        $row = array( 'cycle' => $cycle,
                      'cycle_unit' => $cycle_unit,
                      'created' => XROWRecurringOrderCollection::now(),
                      'collection_id' => $collection_id,
                      'contentobject_id' => $object_id,
                      'amount' => $amount,
                      'end' => $end,
                      'start' => $start,
                      'canceled' => $canceled,
                      'status' => $status );

        foreach ( $row as $key => $value )
        {
            $item->setAttribute( $key, $value );
        }

        $item->setAttribute( 'next_date', $item->nextDate() );
        $item->store();

        if ( is_array( $variations ) )
        {
            foreach ( $variations as $variation_id => $option_id )
            {
                $option = new XROWRecurringOrderItemOption( array( 'item_id' => $item->item_id,
                                                                   'variation_id' => $variation_id,
                                                                   'option_id' => $option_id ) );
                $option->store();
            }
        }
    }

    function fetchByUser( $user_id = null )
    {
        if ( $user_id === null )
               $user_id = eZUser::currentUserID();
        return eZPersistentObject::fetchObjectList( XROWRecurringOrderItem::definition(),
                null, array( 'user_id' => $user_id ), true );

    }

    // returns true, if an order item exists for a given user
    /**
     *  @access public
     */
    function hasRecurringOrderItem( $contentobjectID, $userID = false )
    {
        $db =& eZDB::instance();

        $contentobjectID = $db->escapeString( $contentobjectID );

        if ( $userID == false )
        {
            $sql = "SELECT
                        COUNT(*) counter
                    FROM
                        xrow_recurring_order_item a
                    WHERE
                        a.contentobject_id = '$contentobjectID'";
        }
        else
        {
            $userID = $db->escapeString( $userID );

            $sql = "SELECT
                        COUNT(*) counter
                    FROM
                        xrow_recurring_order_collection a,
                        xrow_recurring_order_item b
                    WHERE
                        a.user_id = '$userID' and
                        a.id = b.collection_id and
                        b.contentobject_id = '$contentobjectID'";

        }
        $result = $db->arrayQuery( $sql );

        if ( $result[0]['counter'] > 0 )
            return true;
        else
            return false;
    }
}
?>