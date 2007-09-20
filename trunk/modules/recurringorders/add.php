<?php

$http =& eZHTTPTool::instance();
$Module =& $Params['Module'];

include_once( "kernel/classes/ezcontentobject.php" );
include_once( "kernel/classes/ezbasket.php" );
include_once( "kernel/classes/ezvattype.php" );
include_once( "kernel/classes/ezorder.php" );
include_once( "kernel/classes/datatypes/ezuser/ezuser.php" );

include_once( "kernel/classes/ezproductcollection.php" );
include_once( "kernel/classes/ezproductcollectionitem.php" );
include_once( "kernel/classes/ezproductcollectionitemoption.php" );
include_once( "kernel/common/template.php" );
include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'extension/recurringorders/classes/recurringordercollection.php');
if ( $http->hasPostVariable( "ActionAddToRecurring" ) )
{
    if ( $http->hasPostVariable( "AddToBasketList" ) and is_array( $http->postVariable( "AddToBasketList" ) ) )
    {
        $result = array();
        foreach ( $http->postVariable( "AddToBasketList" ) as $position )
        {
            if ( $position['quantity'] > 0 and $position['object_id'] )
            {
                if ( !is_array( $position['variations'] ) )
                    $position['variations'] =array();
                $result[] = $position;
            }
        }
        if ( count( $result ) == 0 )
        {
             $module->redirectTo( $_SERVER['HTTP_REFERER'] );
             return;
        }
        $collection  = XROWRecurringOrderCollection::fetchByUser();
        if ( !is_array( $collection ) or count( $collection ) == 0 )
            $collection = XROWRecurringOrderCollection::createNew();
        elseif ( is_array( $collection ) )
        {
            $collection = $collection[0];
        }
        foreach ( $result as $item )
        {
            $collection->add( $item['object_id'], $item['variations'], $item['quantity']);
        }
        $http->setSessionVariable( "RedirectURI", $http->sessionVariable( 'LastAccessesURI' ) );
    }
}
return $Module->redirectTo( "recurringorders/list/" . $collection->id );
?>