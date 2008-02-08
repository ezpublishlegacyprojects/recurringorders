<?php
/**
 * This will link to the phpDocumentor.pkg tutorial if it is unique, regardless
 * of its location
 * @tutorial bla.txt
 */
include_once( 'lib/ezxml/classes/ezxml.php' );

class XROWRecurringordersCommonFunctions
{
    
    function addLeadingZero( $value )
    {
        return sprintf("%02d", $value);
    }
    /*!
     \static
     This can be called like XROWRecurringordersCommonFunctions::createDOMTreefromArray( $name, $array )
    */
    function createDOMTreefromArray( $name, $array, $root = false )
    {
        $doc = new eZDOMDocument( $name );
        if ( !$root )
            $root = $doc->createElementNode( $name );

        $keys = array_keys( $array );
        foreach ( $keys as $key )
        {
            if ( is_array( $array[$key] ) )
            {
                $node = XROWRecurringordersCommonFunctions::createDOMTreefromArray( $key, $array[$key] );
                $root->appendChild( $node );
            }
            else
            {
                $node = $doc->createElementNode( (string)$key );
                $node->appendChild( $doc->createTextNode( $array[$key] ) );
                $root->appendChild( $node );
            }
            unset( $node );
        }
        return $root;
    }

    /*!
     \static
     This can be called like XROWRecurringordersCommonFunctions::createArrayfromXML( $xmlDoc )
    */
    function createArrayfromXML( $xmlDoc )
    {
        $result = array();
        $xml = new eZXML();
        $dom = $xml->domTree( $xmlDoc );
        if ( is_object( $dom ) )
        {
            $node = $dom->get_root();
            $children = $node->children();
            foreach ( $children as $child )
            {
                $contentnode = $child->firstChild();
                if ( $contentnode->type === EZ_XML_NODE_TEXT )
                {
                    $result[$child->name()] = $contentnode->textContent();
                }
                else
                {
                    $result[$child->name()] = XROWRecurringordersCommonFunctions::createArrayfromDOMNODE( $child );
                }
            }
        }
        return $result;
    }
    /*!
     \static
     This can be called like XROWRecurringordersCommonFunctions::createArrayfromDOMNODE( $node )
    */
    function createArrayfromDOMNODE( $node )
    {
        $result = array();
        if ( is_object( $node ) )
        {
            $children = $node->children();
            foreach ( $children as $child )
            {
                $contentnode = $child->firstChild();
                if ( $contentnode->type === EZ_XML_NODE_TEXT )
                {
                    $result[$child->name()] = $contentnode->textContent();
                }
                else
                {
                    $result[$child->name()] = XROWRecurringordersCommonFunctions::createArrayfromDOMNODE( $child );
                }
            }
        }
        return $result;
    }
}
?>