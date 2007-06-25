<?php
include_once( 'extension/recurringorders/classes/recurringordercollection.php');
class XROWRecurringOrderItem extends eZPersistentObject
{
    function XROWRecurringOrderItem( $row )
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
                                         "date" => array( 'name' => "date",
                                                                   'datatype' => 'integer',
                                                                   'default' => time(),
                                                                   'required' => true ),
                                         'amount' => array( 'name' => "amount",
                                                                 'datatype' => 'integer',
                                                                 'default' => '0',
                                                                 'required' => true ) ),
                      "keys" => array( "item_id" ),
                      "increment_key" => "item_id",
                      "function_attributes" => array( 
                                                        "object" => "object",
                                                        "price_per_item" => "pricePerItem",
                                                        "price" => "price",
                                                        "options" => "options"
                                                     ),
                      "class_name" => "XROWRecurringOrderItem",
                      "sort" => array( "date" => "asc" ),
                      "name" => "xrow_recurring_order_item" );
    }
    function &object()
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
    function add( $collection_id, $object_id, $variations = null, $amount )
    {
        if ( !$amount )
            return false;

        $item = new XROWRecurringOrderItem( array( 'collection_id' => $collection_id, 'user_id' => eZUser::currentUserID(), 'contentobject_id' => $object_id, 'amount' => $amount ) );
        $item->store();
        foreach ( $variations as $variation_id => $option_id )
        {
            $option = new XROWRecurringOrderItemOption( array( 'item_id' => $item->item_id, 'variation_id' => $variation_id, 'option_id' => $option_id ) );
            $option->store();
        }
    }
    function store()
    {
        if ( $this->attribute( 'date' ) === null )
            $this->setAttribute( 'date', time() );
        parent::store();
    }
    function fetchByUser( $user_id = null )
    {
        if ( $user_id === null )
               $user_id = eZUser::currentUserID();
        return eZPersistentObject::fetchObjectList( XROWRecurringOrderItem::definition(),
                null, array( 'user_id' => $user_id ), true );
        
    }
}
?>