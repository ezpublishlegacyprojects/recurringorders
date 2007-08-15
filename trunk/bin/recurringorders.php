<?php
include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "eZ publish recurring orders\n" .
                                                         "php extension/recurringorders/bin/recurringorders.php" ),
                                      'use-session' => false,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[clear-tag:][clear-id:][clear-all][list-tags][list-ids]",
                                "",
                                array(  ) );
$sys =& eZSys::instance();

$script->initialize();
if ( !$isQuiet )
{
    
    $cli->output( 'Using Siteaccess '.$GLOBALS['eZCurrentAccess']['name'] );
    
}

// login as admin
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
$user = eZUser::fetchByName( 'admin' );

if ( is_object( $user ) )
{
	if ( $user->loginCurrent() )
	   $cli->output( "Logged in as 'admin'" );
}
else
{
	$cli->error( 'No admin.' );
    $script->shutdown( 1 );
}

include_once( 'extension/recurringorders/classes/recurringordercollection.php');
$cli->output( "Today is " . strftime( "%d.%m.%Y", XROWRecurringOrderCollection::now() ) );

$list = XROWRecurringOrderCollection::fetchAll();
foreach ( $list as $collection )
{
    $cli->output( "Processing Collection #" . $collection->id );
    $collection->markRun();
    $user = $collection->attribute( 'user' );
    if ( $collection->attribute( 'status' ) === XROWRECURRINGORDER_STATUS_DEACTIVATED )
    {
        $cli->output( "Collection #" . $collection->id . ' deactivated' );
        continue;
    }
    if ( !$collection->canTry() )
    {
        $cli->output( "Collection #" . $collection->id . ' has to wait for the next try');
        continue;
    }
    
    $cccheck = $collection->checkCreditCard();
    if ( $cccheck !== true )
    {
        XROWRecurringOrderHistory::add( XROWRECURRINGORDER_STATUSTYPE_CREDITCARD_EXPIRES, $collection->id, 'Creditcard expires' );
        
        // creditcard error
        $cli->output( "Collection #" . $collection->id . ' creditcard error' );
        continue;
    }
    if ( !$collection->isDue() )    
    {
        $cli->output( "Collection #" . $collection->id . " has no items that are due." );
        continue;
    }
    include_once( 'kernel/classes/ezshopaccounthandler.php' );

    $accountHandler =& eZShopAccountHandler::instance();
    // Do we have all the information we need to start the checkout
    if ( !$accountHandler->verifyAccountInformation() )
    {
        continue;
    }
    $items = $collection->fetchDueList();
    $order = $collection->createOrder( $items );

    $userArray = $accountHandler->fillAccountArray( $user );

    $node = XROWRecurringOrderCollection::createDOMTreefromArray( "shop_account", $userArray );
    $doc = new eZDOMDocument( 'account_information' );
    $doc->setRoot( $node );
    $docstring = $doc->toString();
    $shopaccountini = eZINI::instance( "shopaccount.ini" );
    $account_identifier = $shopaccountini->variable( 'AccountSettings', 'Handler' );
    $order->setAttribute( 'data_text_1', $doc->toString() );
    $order->setAttribute( 'account_identifier', $account_identifier );
    $order->setAttribute( 'email', $accountHandler->email( $order ) );
    $order->setAttribute( 'ignore_vat', 0 );
    $order->store();

    $operationResult = eZOperationHandler::execute( 'recurringorders', 'checkout', array( 'order_id' => $order->attribute( 'id' ) ) );
    switch( $operationResult['status'] )
    {
        case EZ_MODULE_OPERATION_HALTED:
        {
            if (  isset( $operationResult['redirect_url'] ) )
            {
                XROWRecurringOrderHistory::add( XROWRECURRINGORDER_STATUSTYPE_FAILURE, $collection->id, $order->id, "Order has been processed with a strange result.", $order->id);
                continue;
            }
            else if ( isset( $operationResult['result'] ) )
            {
                XROWRecurringOrderHistory::add( XROWRECURRINGORDER_STATUSTYPE_FAILURE, $collection->id, $order->id, "Order has been processed with a strange result.", $order->id);
                continue;
            }
        }break;
        case EZ_MODULE_OPERATION_CANCELED:
        {
            XROWRecurringOrderHistory::add( XROWRECURRINGORDER_STATUSTYPE_FAILURE, $collection->id, "Order has been CANCELED.", $order->id);
            continue;
        }
    }

    $order = eZOrder::fetch( $order->ID );
    $cli->output( "Order #" . $order->OrderNr . " created." );
    foreach ( $items as $item )
    {
        $item->setAttribute( 'last_success', $item->attribute( 'next_date' ) );
        $item->store();
        $cli->output( "  Item #" . $item->item_id . " next order is on " . strftime( "%d.%m.%Y", $item->attribute( 'next_date' ) ) );
    }
    XROWRecurringOrderHistory::add( XROWRECURRINGORDER_STATUSTYPE_SUCCESS, $collection->id, "Order has been completed.", $order->id );
}
$cli->output( "Recurring Orders processed" );

$script->shutdown();
?>