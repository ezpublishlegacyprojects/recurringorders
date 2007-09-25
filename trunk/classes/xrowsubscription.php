<?php

include_once( eZExtension::baseDirectory() . '/recurringorders/classes/recurringordercollection.php');

class xrowSubscription
{
 	function xrowSubscription( $handlerIdentifier = 'default' )
 	{
        $this->handleName = $handlerIdentifier;
        $this->handleArray = $this->getHandlerArray();
        $this->handle = $this->getHandler( $handlerIdentifier );
 	}

    function getHandlerArray()
    {
        $ini =& eZINI::instance( 'recurringorders.ini' );
        $subscriptionArray = $ini->variable( 'SubscriptionSettings', 'SubscriptionHandlerArray' );
        $repositoryArray = $ini->variable( 'SubscriptionSettings', 'SubscriptionHandlerRepository' );

        foreach ( $subscriptionArray as $subscription )
        {
            foreach ( $repositoryArray as $repository )
            {
                $fileName = eZExtension::baseDirectory() . "/$repository/classes/subscription_handler/" . strtolower( $subscription ) . 'subscriptionhandler.php';
                eZDebug::writeDebug( $fileName, 'file' );
                if ( file_exists( $fileName ) )
                {
                    include_once( $fileName );
                    $className = $subscription . 'SubscriptionHandler';
                    $this->handlerArray[$subscription] = new $className();
                    continue;
                }
            }
            if ( !isset( $this->handlerArray[$subscription] ) )
                eZDebug::writeError( $subscription . ': No file for inclusion found.', 'xrowSubcription::getHandlerArray' );
        }
    }

    function getHandler( $handlerIdentifier )
    {
        if ( isset( $this->handlerArray[$handlerIdentifier] ) )
            return $this->handlerArray[$handlerIdentifier];
        else
            return false;
    }

 	function remove( $handlerIdentifier = false )
 	{
 	}
 	function signup()
 	{
 	}

 	function suspend()
 	{
 	}

 	var $handle;
 	var $handlerIdentifier;
 	var $handlerArray;
}
?>