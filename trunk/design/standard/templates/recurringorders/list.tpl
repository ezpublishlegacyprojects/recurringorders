{def $currency = fetch( 'shop', 'currency' )
         $locale = false()
         $symbol = false()}
{if $currency}
        {set locale = $currency.locale
             symbol = $currency.symbol}
{/if}
{def $user=fetch( 'user', 'current_user' )}

<div id="main-wide">
<h1>Your recurring orders</h1>
{content_view_gui content_object=$user.contentobject view="address"}
<form name="recurringorders" method="post" action={concat( "content/edit/", $user.contentobject.id )|ezurl}>
<div class="block">
<input class="button" type="submit" name="Cancel" value="Edit profile" />
<div class="break"></div>
</div>
</form>
<form name="recurringorders" method="post" action={concat( "recurringorders/list/", $collection.id )|ezurl}>


<table class="list">
<tr>
    <th colspan="2">Settings</th>
    <th colspan="2">Description</th>
</tr>


<tr>
    <th>Pause recurring orders</th>
    <td><input name="Pause" type="checkbox" value="1" {if $collection.status|ne('1')}checked{/if} /></td>
    <td><p>This is your holiday mode. If you are away and you wish to not receive your orders please check this box. We will also pause your recurrent orders, if we notice problems with your order request.</p></td>
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
    <td>{$item.created|l10n( 'shortdate' )}</td>
    <td><input name="ItemArray[{$item.item_id}][amount]" type="input" value="{$item.amount}"/></td>
    <td>{$item.price_per_item|l10n( 'currency', $locale, $symbol )}</td>
    <td>{$item.price|l10n( 'currency', $locale, $symbol )}</td>
</tr>
<tr>
    <th>Cycle</th>
    <td>
    <input name="ItemArray[{$item.item_id}][cycle]" type="input" value="{$item.cycle}"/>
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
    <th>Order date</th>
    <td>
        <select name="ItemArray[{$item.item_id}][order_date][day]">
        {foreach $item.days_in_cycle as $day}
        <option value="{$day}" {if $day|eq($item.order_date_object.day)}selected{/if}>{$day}</option>
        {/foreach}
        </select> day of cycle
    </td>
    <td><p>This day will determine on which day the order is being processed. The shipped goods will arrive at your shipping location a short time thereafter.</p></td>
</tr>
<tr>
    <th>Next order</th>
    <td>{$item.real_next_order_date|l10n( 'shortdate' )}</td>
    <td></td>
</tr>
{if $item.last_success}
<tr>
    <th>Last order</th>
    <td>{$item.last_success|l10n( 'shortdate' )}</td>
    <td></td>
</tr>
{/if}
{/foreach}
</table>

<p>
All prices exclude Tax, Shipping and Handling. Those will be added to your order once a new order is created.  
</p>
<input class="button" type="submit" name="Remove" value="Remove" />
<input class="button" type="submit" name="Update" value="Update" />
<input class="button" type="submit" name="Cancel" value="Cancel" />
</form>
Last : {$collection.last_run|l10n( 'shortdate' )}<br/>
Now : {$collection.now|l10n( 'shortdate' )}<br/>
</div>