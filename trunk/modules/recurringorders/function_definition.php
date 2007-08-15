<?php

$FunctionList = array();

$FunctionList['fetch_text'] = array( 'name' => 'fetch_text',
                                 'call_method' => array( 'include_file' => 'extension/recurringorders/modules/recurringorders/recurringordersfunctioncollection.php',
                                                         'class' => 'recurringordersFunctionCollection',
                                                         'method' => 'fetchTextAdjectiveArray' ),
                                 'parameter_type' => 'standard',
                                 'parameters' => array( ) );
?>
