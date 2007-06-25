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
<input class="button" type="submit" name="Cancel" value="Edit profile" />
</form>
<form name="recurringorders" method="post" action={concat( "recurringorders/list/", $collection.id )|ezurl}>


<table class="list">
<tr>
    <th colspan="2">Settings</th>
    <th colspan="2">Description</th>
</tr>
<tr>
    <th>Period</th>
    <td><select name="Period"><option value="2">MONTHLY</option></select></td>
    <td><p></p></td>
</tr>
<tr>
    <th>Sendday</th>
    <td>
        <select name="SendDay">
        {foreach $days as $day}
        <option value="{$day}" {if $day|eq($collection.send_day)}selected{/if}>{$day}</option>
        {/foreach}
        </select>
    </td>
    <td><p>This day will determine on which day the order is being send. The shipped goods will arrive at your shipping location a short time thereafter.</p></td>
</tr>
<tr>
    <th>Pause recurring orders</th>
    <td><input name="Pause" type="checkbox" value="1" {if $collection.status|ne('1')}checked{/if} /></td>
    <td><p>This is your holiday mode. If you are away and you wish to not receive your orders please check this box. We will also pause your recurrent orders if we notice problems wiht your order request.</p></td>
</tr>
<tr>
    <th>Next order</th>
    <td>{$collection.next_date|l10n( 'shortdatetime' )}</td>
    <td></td>
</tr>
{if $collection.last_success}
<tr>
    <th>Last order</th>
    <td>{$collection.last_success|l10n( 'shortdatetime' )}</td>
    <td></td>
</tr>
{/if}
</table>

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
    <td>{$item.date|l10n( 'shortdatetime' )}</td>
    <td><input name="ItemArray[{$item.item_id}]" type="input" value="{$item.amount}"/></td>
    <td>{$item.price_per_item|l10n( 'currency', $locale, $symbol )}</td>
    <td>{$item.price|l10n( 'currency', $locale, $symbol )}</td>
</tr>
{/foreach}
</table>

<p>
All prices exclude Tax, Shipping and Handling. This will be added to your order once a new is created.  
</p>
<input class="button" type="submit" name="Remove" value="Remove" />
<input class="button" type="submit" name="Update" value="Update" />
<input class="button" type="submit" name="Cancel" value="Cancel" />
</form>
</div>