<?php
$Module = array( "name" => "Recurring orders" );

$ViewList = array();
$ViewList["add"] = array( 
    "functions" => array( 'buy' ),
    "script" => "add.php"
);
$ViewList["list"] = array( 
            "functions" => array( 'buy' ),
            'single_post_actions' => array( 'Remove' => 'Remove',
                                            'Cancel' => 'Cancel',
                                            'Update' => 'Update' ),
            'post_action_parameters' => array( 
                                            'Remove' => array( 'RemoveArray' => 'RemoveArray' ),
                                            'Cancel' => array( ),
                                            'Update' => array( 
                                                                'ItemArray' => 'ItemArray',
                                                                'SendDay' => 'SendDay',
                                                                'Pause' => 'Pause'
                                                             )
                                        ),
            'params' => array( 'CollectionID' ),
            'script' => 'list.php' );
$ViewList['history'] = array(
    "default_navigation_part" => 'ezshopnavigationpart',
    'functions' => array( 'administrate' ),
    'params' => array( 'Offset' ),
    'script' => 'history.php' );
$ViewList['forecast'] = array(
    "default_navigation_part" => 'ezshopnavigationpart',
    'functions' => array( 'administrate' ),
    'script' => 'forecast.php' );
$FunctionList['administrate'] = array( );
$FunctionList['use'] = array( );
?>