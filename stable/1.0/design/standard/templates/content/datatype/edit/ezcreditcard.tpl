{* DO NOT EDIT THIS FILE! Use an override template instead. *}
{def $gateway_array=$attribute.contentclass_attribute.content.gateway
     $card_array=$attribute.contentclass_attribute.content.card_array}

{literal}
<script type="text/javascript">
    function updateCCField{/literal}{$attribute.id}{literal}( val, id )
    {
        if ( val == 5 )
        {
            document.getElementById('ro-creditcardtype-' + id).style.display = 'none';
            document.getElementById('ro-ectype-' + id).style.display = 'block';
        }
        else
        {
            document.getElementById('ro-creditcardtype-' + id).style.display = 'block';
            document.getElementById('ro-ectype-' + id).style.display = 'none';
        }
    }
</script>
{/literal}

{default attribute_base='ContentObjectAttribute'
         html_class='full'}
<div class="block">
{if and(is_set($attribute.content.has_stored_card),eq($attribute.content.has_stored_card,1))}

    <p>
        <label>{"Current stored card"|i18n('design/standard/content/datatype')}</label>
       {$attribute.content.type_name|wash}
    </p>
    {if $attribute.content.type|eq(5)}
        <p>
            <strong>{$attribute.content.ecname|wash}</strong>
        </p>
        <table cellpadding="5" cellspacing="0" border="0">
        <tr>
            <td>{"Account number"|i18n('design/standard/content/datatype')}:</td>
            <td>&nbsp;{"X"|repeat(sub($attribute.content.accountnumber|count,3))}{$attribute.content.accountnumber|extract_right( 3 )|wash( xhtml )}</td>
        </tr>
        <tr>
            <td>{"Bank code"|i18n('design/standard/content/datatype')}:</td>
            <td>&nbsp;{$attribute.content.bankcode|wash}</td>
        </tr>
        </table>
        <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_delete_creditcard]" value="{'Remove debit card'|i18n( 'design/standard/content/datatype' )}" />
    {else}
        <p>
           <strong>{$attribute.content.name|wash}</strong><br />
           XXXX-XXXX-XXXX-{$attribute.content.number|extract_right( 4 )|wash( xhtml )}<br />
           {"expires"|i18n('design/standard/content/datatype')} {$attribute.content.month}/{$attribute.content.year}
        </p>
        </div>
        <input class="button" type="submit" name="CustomActionButton[{$attribute.id}_delete_creditcard]" value="{'Remove credit card'|i18n( 'design/standard/content/datatype' )}" />

    {/if}
{else}
    {def $type=first_set( $attribute.content.type, '' )
         $name=first_set( $attribute.content.name, '' )
         $number=first_set( $attribute.content.number, '' )
         $cmonth=first_set( $attribute.content.month, '' )
         $cyear=first_set( $attribute.content.year, '' )
         $ecname=first_set( $attribute.content.ecname, '' )
         $accountnumber=first_set( $attribute.content.accountnumber, '' )
         $bankcode=first_set( $attribute.content.bankcode, '' )
         $securitycode=first_set( $attribute.content.securitycode, '' )
    }

    <label>{'Card type'|i18n( 'design/standard/content/datatype' )}</label>
    <select name="{$attribute_base}_ezcreditcard_type_{$attribute.id}" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" onchange="updateCCField{$attribute.id}(this.value, {$attribute.id} );" >
    {foreach $gateway_array as $key => $gateway}
        <option value="{$key}"{if $type|eq($key)} selected="selected"{/if}>{$card_array[$key]|wash}</option>
    {/foreach}
    </select>
    <div id="ro-creditcardtype-{$attribute.id}"{if $type|eq(5)} style="display: none;"{/if}>
        <label>{'Name on card'|i18n( 'design/standard/content/datatype' )}:</label>
        <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" size="70" name="{$attribute_base}_ezcreditcard_name_{$attribute.id}" value="{$name|wash( xhtml )}" />
        <label>{'Credit card number'|i18n( 'design/standard/content/datatype' )}</label>
        <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" size="70" name="{$attribute_base}_ezcreditcard_number_{$attribute.id}" value="{$number|wash( xhtml )}" />
        <label>{'Security Code'|i18n( 'design/standard/content/datatype' )}</label>
        <input maxlength="4" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" size="70" name="{$attribute_base}_ezcreditcard_securitycode_{$attribute.id}" value="{$securitycode|wash( xhtml )}" /> <a id="show_cvv_tip">{'Help'|i18n( 'design/standard/content/datatype' )}</a>

{literal}
<style>
.yui-overlay { position:absolute;background:#fff;border:1px dotted black;padding:5px;margin:10px; }
#overlay1 { 
    background-color: white;
    border: 1px solid grey; 
}
        </style>
<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.4.1/build/container/assets/container.css"> 
<script type="text/javascript" src={"javascript/yui/yahoo-dom-event/yahoo-dom-event.js"|ezdesign}></script> 
<script type="text/javascript" src={"javascript/yui/animation/animation-min.js"|ezdesign}></script> 
<script type="text/javascript" src={"javascript/yui/container/container-min.js"|ezdesign}></script> 
        <script>
		YAHOO.namespace("example.container");

		function init() {
			// Build overlay1 based on markup, initially hidden, fixed to the center of the viewport, and 300px wide
			YAHOO.example.container.overlay1 = new YAHOO.widget.Overlay("overlay1", { fixedcenter:false,
																					  visible:false,
																					  width:"400px" } );
			YAHOO.example.container.overlay1.render();

			YAHOO.util.Event.addListener("show_auto_tip", "mouseover", YAHOO.example.container.overlay1.show, YAHOO.example.container.overlay1, true);
			YAHOO.util.Event.addListener("show_auto_tip", "mouseout", YAHOO.example.container.overlay1.hide, YAHOO.example.container.overlay1, true);
		}

		YAHOO.util.Event.addListener(window, "load", init);
</script>
{/literal}
<div id="overlay1" style="visibility:hidden;">
<h3>What is a Security Code?</h3>

<img src={'CVC2SampleVisaNew.png'|ezimage} alt="Sample credit card">

<p>The Card Security Code is located on the back of MasterCard, Visa and Discover credit or debit cards and is typically a separate group of 3 digits to the right of the signature strip.</p>

<img src={'CVC2SampleVisaNew.png'|ezimage} alt="Sample credit card">

<p>On American Express cards, the Card Security Code is a printed (NOT embossed) group of four digits on the front towards the right.</p>

<img src={'CIDSampleAmex.png'|ezimage} alt="Sample credit card">

</div>
        <div class="block">
            <div class="element">
            <label>{'Month'|i18n( 'design/standard/content/datatype' )}</label>
            <select name="{$attribute_base}_ezcreditcard_month_{$attribute.id}" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" >
            {def $months = array('01','02','03','04','05','06','07','08','09','10','11','12')}
            {foreach $months as $month}
            <option value="{$month}"{if $month|eq($cmonth)} selected="selected"{/if}>{$month}</option>
            {/foreach}
            </select>
            </div>

            <div class="element">
            <label>{'Year'|i18n( 'design/standard/content/datatype' )}</label>
            <select name="{$attribute_base}_ezcreditcard_year_{$attribute.id}" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" >
            {def $year=currentdate()|datetime(custom,'%Y')}
            {for $year to sum($year,10) as $i}
            <option value="{$i}"{if $i|eq($cyear)}selected="selected"{/if}>{$i}</option>
            {/for}
            </select>
            </div>
            <div class="break"></div>
        </div>
    </div>
    <div id="ro-ectype-{$attribute.id}"{if $type|ne(5)} style="display: none;"{/if}>
         <label>{'Name of account'|i18n( 'design/standard/content/datatype' )}:</label>
        <input maxlength="27" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" size="70" name="{$attribute_base}_ezcreditcard_ecname_{$attribute.id}" value="{$ecname|wash( xhtml )}" />
        <label>{'Account number'|i18n( 'design/standard/content/datatype' )}</label>
        <input maxlength="10" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" size="70" name="{$attribute_base}_ezcreditcard_accountnumber_{$attribute.id}" value="{$accountnumber|wash( xhtml )}" />
        <label>{'Bank code'|i18n( 'design/standard/content/datatype' )}</label>
        <input maxlength="8" id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}" class="{eq( $html_class, 'half' )|choose( 'box', 'halfbox' )} ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" type="text" size="70" name="{$attribute_base}_ezcreditcard_bankcode_{$attribute.id}" value="{$bankcode|wash( xhtml )}" />

    </div>
{/if}
</div>
{/default}

