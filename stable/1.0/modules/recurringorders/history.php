<?php

// include classes
include_once( 'extension/recurringorders/classes/recurringordercollection.php');

$Module =& $Params['Module'];
include_once( 'kernel/common/template.php' );
$tpl =& templateInit();


$limit = 10;

if ( isset( $Params['UserParameters'] ) )
{
    $viewParameters = $Params['UserParameters'];                  
}
else
{
    $viewParameters = array();
}
$offset = (int)$viewParameters['offset'];

$http =& eZHTTPTool::instance();



$tpl->setVariable( "history_list", XROWRecurringOrderHistory::historyList( $offset, $limit ) );
$tpl->setVariable( "history_list_count", XROWRecurringOrderHistory::historyCount() );
$tpl->setVariable( "limit", $limit );

$tpl->setVariable( "module", $Module );
$tpl->setVariable( 'view_parameters', $viewParameters );

$Result = array();
$Result['left_menu'] = "design:parts/xrowrecurringorders/menu.tpl";
$Result['content'] = $tpl->fetch( "design:recurringorders/history.tpl" );
$Result['path'] = array( array( 'url' => false,
                        'text' => 'Recurring orders' ) );
?>
