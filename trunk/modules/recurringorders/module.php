<?php
$Module = array( "name" => "Recurring orders" );

$ViewList = array();
$ViewList["add"] = array( "script" => "add.php" );
$ViewList["list"] = array( 
            'functions' => array( 'use' ), 
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
$ViewList['failures'] = array(
    'functions' => array( 'administrate' ),
    'script' => 'failures.php' );

$FunctionList['administrate'] = array( );
$FunctionList['use'] = array( );
?>