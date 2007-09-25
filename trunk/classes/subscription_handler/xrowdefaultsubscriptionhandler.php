<?php
class xrowDefaultSubscriptionHandler
{
    /**
     * XROWRecurringOrderItem
     *
     * @var XROWRecurringOrderItem
     */
    var $item;
	function xrowDefaultSubscriptionHandler( $itemID )
	{
	    $this->item = XROWRecurringOrderItem::fetch( $itemID );
	}
	function getName()
	{
		$co = $this->item->attribute( 'contentobject' );
		return $co->attribute( 'name' );
	}
	function getPrice()
	{
	    $co = $this->item->attribute( 'contentobject' );
		$dm  = $co->attribute('data_map');
		/**
		 * @todo functions tries to fetch the price datatype from the content object
		 */
		$package = $dm['package']->content();
		$pdm = &$package->attribute('data_map');
		return (float)$pdm['price']->content();
	}
    function actionSignup()
    {
    	return false;
    }
    function actionCancle()
    {
    	return false;
    }
    function actionSuspend()
    {
    	return false;
    }
    function actionRemove()
    {
    	return false;
    }
}
?>