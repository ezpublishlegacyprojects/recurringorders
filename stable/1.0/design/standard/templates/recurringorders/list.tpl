{*
 This template works with GMT date for any date you have to append |sub(currentdate()|datetime( 'custom', '%Z' )) to get the 
correct locale date
*}
<link rel="stylesheet" type="text/css" href="/extension/recurringorders/design/standard/javascript/yui/fonts/fonts-min.css" />
<link rel="stylesheet" type="text/css" href="/extension/recurringorders/design/standard/javascript/yui/calendar/assets/skins/sam/calendar.css" />
{literal}
<script type="text/javascript">
YAHOO.namespace("example.calendar");
function ShowHide(id)
{
    	    var ComponentName = id + '-container';
	        if ( YAHOO.util.Dom.hasClass( ComponentName, 'hide') )
	        {
	            YAHOO.util.Dom.removeClass(ComponentName, 'hide');
	            YAHOO.util.Dom.addClass(ComponentName, 'show');
	        }
	        else
	        {
	            YAHOO.util.Dom.removeClass(ComponentName, 'show');
	            YAHOO.util.Dom.addClass(ComponentName, 'hide');
	        }
}
function handleSelect(type,args,obj) {
			var dates = args[0]; 
			var date = dates[0];
			var year = date[0], month = date[1], day = date[2];

			var txtDate1 = document.getElementById( obj.id + "-date");
			txtDate1.value = month + "/" + day + "/" + year;
			ShowHide( obj.id );
}
</script>
{/literal}
{literal}
<style type="text/css">
	.container
	{
        margin-right:10px; 
        margin-bottom:10px; 	
        position:absolute;
	}
	.hide
	{
        display: none;
	}
	.show
	{
	   display: block;
	}
</style>
{/literal}

<div class=" yui-skin-sam">




{*
{literal}
<script type="text/javascript">
	YAHOO.util.Event.onDOMReady( function() {
		YAHOO.example.calendar.cal1222 = new YAHOO.widget.Calendar("cal1","cal1222-container", 
																	{ mindate:"1/1/2006",
																	  maxdate:"12/31/2008" });
		YAHOO.example.calendar.cal1222.selectEvent.subscribe(handleSelect, YAHOO.example.calendar.cal1222, true);
		YAHOO.example.calendar.cal1222.render();

	} );
	YAHOO.util.Event.on('cal1222', 'click', function() { ShowHide( this.id ) } ); 
</script>
{/literal}
<input type="text" name="cal1222-date" id="cal1222-date" readonly value="8/14/2007"/>
<button type="button" id="cal1222">Change Date</button>
<div id="cal1222-container" class="container show"></div>
*}



{foreach $messages as $message}
<div class="message-{$message.type}">
    <h2>{$message.text}</h2>
</div>
{/foreach}


{def $currency = fetch( 'shop', 'currency' )
         $locale = false()
         $symbol = false()}
{if $currency}
        {set locale = $currency.locale
             symbol = $currency.symbol}
{/if}
{def $user=fetch( 'user', 'current_user' )}

<div id="main-wide">
{*<h1>Automatic Delivery</h1>*}
{content_view_gui content_object=$user.contentobject view="address"}
<form name="recurringorders" method="post" action={concat( "content/edit/", $user.contentobject.id )|ezurl}>
<div class="block">
<input class="button" type="submit" name="Cancel" value="Edit profile" />
<div class="break"></div>
</div>
</form>
<form name="recurringorders" method="post" action={concat( "recurringorders/list/", $collection.id )|ezurl}>

<h3>Settings</h3>
<table class="list">
<tr>
    <td><p><input name="Pause" type="checkbox" value="1" {if $collection.status|ne('1')}checked{/if} /></p></td>
    <td><p><b>Pause Automatic Delivery</b></p><p>If you are away and you wish to not receive your orders please check this box. We will also pause your Automatic Delivery, if we notice problems with your order request.</p></td>
</tr>
</table>
<p>
<i>Important Note</i>
</p>
<p>Items with the same "Next order" date will be combined on the same order.</p>

<table class="list">
<tr>
    <th></th>
    <th>Product name</th>
    <th>Variation</th>
    <th>Date added</th>
    <th>Amount</th>
    <th>Price per item</th>
    <th>Price</th>
</tr>
{foreach $collection.list as $item}
<tr>
    <td><input name="RemoveArray[]" type="checkbox" value="{$item.item_id}"/></td>
    <td>{$item.object.name|wash(xhtml)}</td>
    <td>
        {foreach $item.options as $option}
        {if $option.name}{$option.name|wash(xhtml)}{/if}
        {if $option.comment}{$option.comment|wash(xhtml)}{/if}
        <br />
        {/foreach}
    </td>
    <td>{$item.created|sub(currentdate()|datetime( 'custom', '%Z' ))|l10n( 'shortdate' )}</td>
    <td><input name="ItemArray[{$item.item_id}][amount]" type="text" value="{$item.amount}"/></td>
    <td>{$item.price_per_item|l10n( 'currency', $locale, $symbol )}</td>
    <td>{$item.price|l10n( 'currency', $locale, $symbol )}</td>
</tr>
<tr>
    <th>Frequency</th>
    <td>
    <input name="ItemArray[{$item.item_id}][cycle]" type="text" value="{$item.cycle}"/>
    {def $list=fetch('recurringorders','fetch_text')}
    <select name="ItemArray[{$item.item_id}][cycle_unit]"> {* disabled for subscriptions *}
    {foreach $list as $key => $text}
    {if ezini( 'RecurringOrderSettings','DisabledCycles','recurringorders.ini')|contains($key)|not}
    <option value="{$key}" {if $item.cycle_unit|eq($key)} selected{/if}>{$text}</option>
    {/if}
    {/foreach}
    </select>
    
    </td>
</tr>
<tr>
    <th>Next order</th>
    <td>

    

<script type="text/javascript">
	YAHOO.util.Event.onDOMReady( function() {ldelim}
		YAHOO.example.calendar.cal{$item.item_id} = new YAHOO.widget.Calendar("cal{$item.item_id}","cal{$item.item_id}-container", 
																	{ldelim}
																	  pagedate:"{$item.next_date|sub(currentdate()|datetime( 'custom', '%Z' ))|datetime( 'custom', '%m/%Y' )}", 
																	  selected:"{$item.next_date|sub(currentdate()|datetime( 'custom', '%Z' ))|l10n( 'shortdate' )}", 
																	  mindate:"{fetch('recurringorders','now')|sum(86400)|l10n( 'shortdate' )}",
																	  maxdate:"{fetch('recurringorders','now')|sub(currentdate()|datetime( 'custom', '%Z' ))|sum(86400)|sum(7776000)|l10n( 'shortdate' )}" {rdelim} );
		YAHOO.example.calendar.cal{$item.item_id}.selectEvent.subscribe(handleSelect, YAHOO.example.calendar.cal{$item.item_id}, true);
		YAHOO.example.calendar.cal{$item.item_id}.render();

	{rdelim} );
	YAHOO.util.Event.on('cal{$item.item_id}', 'click', function() {ldelim} ShowHide( this.id ) {rdelim} ); 
</script>
<input type="text" name="ItemArray[{$item.item_id}][next_date]" id="cal{$item.item_id}-date" readonly value="{$item.next_date|sub(currentdate()|datetime( 'custom', '%Z' ))|l10n( 'shortdate' )}"/>
<button type="button" id="cal{$item.item_id}" name="cal{$item.item_id}">Change Date</button>
<div id="cal{$item.item_id}-container" class="container hide"></div>
    <p>This date will determine on which day the order is being processed. The shipped goods will arrive at your shipping location a short time thereafter.</p>
    
    </td>
    <td></td>
</tr>
{if $item.last_success}
<tr>
    <th>Last order</th>
    <td>{$item.last_success|sub(currentdate()|datetime( 'custom', '%Z' ))|l10n( 'shortdate' )}</td>
    <td></td>
</tr>
{/if}
{/foreach}
</table>

<p>
All prices exclude tax, shipping and handling. Those will be added to your order once a new order is created.  
</p>
<input class="button" type="submit" name="Remove" value="Remove" />
<input class="button" type="submit" name="Update" value="Update" />
<input class="button" type="submit" name="Cancel" value="Cancel" />
</form>
<!--
Last : {$collection.last_run|l10n( 'shortdate' )}<br/>
Now : {$collection.now|l10n( 'shortdate' )}<br/>
-->
</div>

</div> {* YUI SAM *}
