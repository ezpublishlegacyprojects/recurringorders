<?php

// include classes
include_once( 'extension/recurringorders/classes/recurringordercollection.php');

$Module =& $Params['Module'];
include_once( 'kernel/common/template.php' );
$tpl =& templateInit();

$http =& eZHTTPTool::instance();
if ( !$Params['CollectionID'] )
{
    $collection = XROWRecurringOrderCollection::fetchByUser();
    return $Module->redirectTo( "recurringorders/list/" . $collection->id );
}
$collection = XROWRecurringOrderCollection::fetch( $Params['CollectionID'] );
if ( eZUser::currentUserID() != $collection->user_id )
{
    return $Module->handleError( EZ_ERROR_KERNEL_ACCESS_DENIED, 'kernel' );
}
if ( $Module->isCurrentAction( 'Remove' ) and $Module->hasActionParameter( 'RemoveArray' ) )
{
    foreach ( $Module->actionParameter( 'RemoveArray' ) as $item_id )
    {
        $item = XROWRecurringOrderItem::fetch( $item_id );
        if ( is_object( $item ) )
            $item->remove();
    }
}
if ( $Module->isCurrentAction( 'Update' ) and $Module->hasActionParameter( 'ItemArray' ) )
{
    foreach ( $Module->actionParameter( 'ItemArray' ) as $item_id => $amount )
    {
        $item = XROWRecurringOrderItem::fetch( $item_id );
        $item->setAttribute( 'amount', $amount );
        $item->store();
    }
    if ( $Module->actionParameter( 'SendDay' ) )
    {
        $collection->setAttribute( 'send_day', $Module->actionParameter( 'SendDay' ) );
    }
    if ( $Module->actionParameter( 'Pause' ) )
    {
        $collection->setAttribute( 'status', XROWRECURRINGORDER_STATUS_DEACTIVATED );
    }
    else
    {
        $collection->setAttribute( 'status', XROWRECURRINGORDER_STATUS_ACTIVE );
    }
    $collection->store();
}
if ( $Module->isCurrentAction( 'Cancel' ) )
{
    return $Module->redirectTo( $http->sessionVariable( "RedirectURI" ));
}
for ( $i = 1; $i <= 31; $i++)
{
    $days[] = $i; 
}
$tpl->setVariable( 'collection', $collection );
$tpl->setVariable( 'days', $days );
$Result = array();

$Result['left_menu'] = "design:parts/ezadmin/menu.tpl";
$Result['content'] = $tpl->fetch( "design:recurringorders/list.tpl" );
$Result['path'] = array( array( 'url' => false,
                        'text' => 'Recurring orders' ) );
?>