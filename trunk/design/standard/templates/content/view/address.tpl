

<table class="list">
<tr>
    <th SCOPE=col colspan=2>
    {if eq($object.data_map.shipping.content,1)}
    <b>{"Address"|i18n("design/standard/shop")}</b>
    {else}
    <b>{"Billing Address"|i18n("design/standard/shop")}</b>
    {/if}
    </th>
</tr>
<tr><th>{'Name'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.first_name} {attribute_view_gui attribute=$object.data_map.mi} {attribute_view_gui attribute=$object.data_map.last_name}</td></tr>
<tr><th>{'Email'|i18n('design/standard/shop')}:</th><td>{$object.data_map.user_account.content.email}</td></tr>
<tr><th>{'Address'|i18n('design/standard/shop')}:</th><td>
{attribute_view_gui attribute=$object.data_map.address1}</th></tr>
{if $object.data_map.address2.has_content}
<tr><th></th><td>{attribute_view_gui attribute=$object.data_map.address2}</td></tr>
{/if}
<tr><th>{'City'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.city}</td></tr>
<tr><th>{'State'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.state}</td></tr>
<tr><th>{'Zip code'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.zip_code}</td></tr>
<tr><th>{'Country'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.country}</td></tr>
<tr><th>{'Phone'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.phone}</td></tr>
<tr><th>{'Shipping'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.shippingtype}</td></tr>
</table>

{if eq($object.data_map.shippingaddress.content,0)}

<table class="list">
<tr>
    <th SCOPE=col colspan=2>
    <b>{"Shipping Address"|i18n("design/standard/shop")}</b>
    </th>
</tr>
<tr><th>Name:</th><td>{attribute_view_gui attribute=$object.data_map.s_first_name} {attribute_view_gui attribute=$object.data_map.s_mi} {attribute_view_gui attribute=$object.data_map.s_last_name}</td></tr>
<tr><th>{'Address'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.s_address1}</td></tr>
{if $object.data_map.s_address2.has_content}
<tr><th></th><td>{attribute_view_gui attribute=$object.data_map.s_address2}</td></tr>
{/if}
<tr><th>{'City'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.s_city}</td></tr>
<tr><th>{'State'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.s_state}</td></tr>
<tr><th>{'Zip code'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.s_zip_code}</td></tr>
<tr><th>{'Country'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.s_country}</td></tr>
<tr><th>{'Phone'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.s_phone}</td></tr>
<tr><th>{'Email'|i18n('design/standard/shop')}:</th><td>{attribute_view_gui attribute=$object.data_map.s_email}</td></tr>
</table>

{/if}
