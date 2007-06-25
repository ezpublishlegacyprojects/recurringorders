<?php

class XROWRecurringOrderHistory extends eZPersistentObject
{
    function XROWRecurringOrderHistory( $row )
    {
        parent::eZPersistentObject( $row );
    }
    function definition()
    {
        return array( "fields" => array( 
                                         "id" => array( 'name' => "id",
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         "collection_id" => array( 'name' => "collection_id",
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true ),
                                         "order_id" => array( 'name' => "order_id",
                                                             'datatype' => 'integer',
                                                             'default' => null,
                                                             'required' => true ),
                                         "type" => array( 'name' => "type",
                                                             'datatype' => 'integer',
                                                             'default' => null,
                                                             'required' => true ),
                                         "date" => array( 'name' => "date",
                                                                   'datatype' => 'integer',
                                                                   'default' => time(),
                                                                   'required' => true ),
                                         "data_text" => array( 'name' => "data_text",
                                                                   'datatype' => 'integer',
                                                                   'default' => null,
                                                                   'required' => true )
                                                                   ),
                      "keys" => array( "id" ),
                      "increment_key" => "id",
                      "function_attributes" => array(),
                      "class_name" => "XROWRecurringOrderHistory",
                      "sort" => array( "date" => "asc" ),
                      "name" => "xrow_recurring_order_history" );
    } 
}
?>