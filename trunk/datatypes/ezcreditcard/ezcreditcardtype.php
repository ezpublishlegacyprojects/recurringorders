<?php

include_once( 'kernel/classes/ezdatatype.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );
include_once( 'lib/ezutils/classes/ezintegervalidator.php' );
include_once( 'kernel/common/i18n.php' );
include_once( 'lib/ezxml/classes/ezxml.php' );
include_once( eZExtension::baseDirectory() . '/recurringorders/classes/xrowpaymentinfo.php' );

define( 'EZ_DATATYPESTRING_CREDITCARD', 'ezcreditcard' );
define( 'EZ_DATATYPE_CREDITCARD_GATEWAY_FIELD', 'data_text5' );

define( 'XROWCREDITCARD_TYPE_MASTERCARD', 1 );
define( 'XROWCREDITCARD_TYPE_VISA', 2 );
define( 'XROWCREDITCARD_TYPE_DISCOVER', 3 );
define( 'XROWCREDITCARD_TYPE_AMERICANEXPRESS', 4 );
define( 'XROWCREDITCARD_TYPE_EUROCARD', 5 );

class ezcreditcardType extends eZDataType
{
    /*!
     Initializes with a string id and a description.
    */
    function ezcreditcardType()
    {
        $this->eZDataType( EZ_DATATYPESTRING_CREDITCARD, ezi18n( 'kernel/classes/datatypes', 'Creditcard', 'Datatype name' ),
                           array( 'serialize_supported' => true,
                                  'object_serialize_map' => array( 'data_text' => 'text' ) ) );
    }

    /*!
     Sets the default value.
    */
    function initializeObjectAttribute( &$contentObjectAttribute, $currentVersion, &$originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
//             $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
//             $currentObjectAttribute = eZContentObjectAttribute::fetch( $contentObjectAttributeID,
//                                                                         $currentVersion );
            $dataText = $originalContentObjectAttribute->attribute( "data_text" );
            $contentObjectAttribute->setAttribute( "data_text", $dataText );
        }
    }

    /*
     Private method, only for using inside this class.
    */
    function validateCard( $data, &$contentObjectAttribute, &$classAttribute )
    {
        $error = false;
        $classContent = $this->classAttributeContent( $classAttribute );
        $gatewayArray = $classContent['gateway'];

        if ( isset( $gatewayArray[$data['type']] ) )
        {
            $gateway = $gatewayArray[$data['type']];

            $payObj = xrowPaymentInfo::getInfoClassObj( $gateway );
            if ( is_object( $payObj ) )
            {
                if ( $payObj->validateCardData( $contentObjectAttribute, $classAttribute, $data ) == false )
                    $error = true;
            }
            else
                eZDebug::writeError( 'PaymentInfo Object not found: ' . $gateway . 'Info', 'eZCreditcardType::validateCard' );
        }
        else
            eZDebug::writeError( 'Gateway not found: ' . $data['type'], 'eZCreditcardType::validateCard' );

        if ( $error )
            return EZ_INPUT_VALIDATOR_STATE_INVALID;
        else
            return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }


    /*!
     \reimp
    */
    function validateObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $type = $http->postVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) );

            if ( $type == XROWCREDITCARD_TYPE_EUROCARD )
            {
                if (  $http->hasPostVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) )
                   )
                {
                    $data = array();
                    $data['type']           = $type;
                    $data['ecname']         = trim ( $http->postVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) ) );
                    $data['accountnumber']  = trim ( $http->postVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) ) );
                    $data['bankcode']       = trim ( $http->postVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) ) );

                    $classAttribute =& $contentObjectAttribute->contentClassAttribute();

                    if ( $data['ecname']  == "" and $data['accountnumber']  == "" and $data['bankcode']  == "" )
                    {
                        if ( !$classAttribute->attribute( 'is_information_collector' ) and
                             $contentObjectAttribute->validateIsRequired() )
                        {
                            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                                 'Input required.' ) );
                            return EZ_INPUT_VALIDATOR_STATE_INVALID;
                        }
                    }
                    else
                    {
                        return $this->validateCard( $data, $contentObjectAttribute, $classAttribute );
                    }

                }
            }
            else if ( in_array( $type, array( XROWCREDITCARD_TYPE_AMERICANEXPRESS,
                                               XROWCREDITCARD_TYPE_DISCOVER,
                                               XROWCREDITCARD_TYPE_MASTERCARD,
                                               XROWCREDITCARD_TYPE_VISA ) ) )
            {
                if (  $http->hasPostVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) )
                   )
                {
                    $data = array();
                    $data['type'] = $type;
                    $data['name'] = $http->postVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['number'] = $http->postVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['securitycode'] = $http->postVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['month'] = $http->postVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['year'] = $http->postVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) );


                    $classAttribute =& $contentObjectAttribute->contentClassAttribute();

                    if ( $data['name']  == "" and $data['number']  == "" and $data['securitycode']  == "" )
                    {
                        if ( !$classAttribute->attribute( 'is_information_collector' ) and
                             $contentObjectAttribute->validateIsRequired() )
                        {
                            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                                 'Input required.' ) );
                            return EZ_INPUT_VALIDATOR_STATE_INVALID;
                        }
                    }
                    else
                    {
                        return $this->validateCard( $data, $contentObjectAttribute, $classAttribute );
                    }
                }
            }
        }
        /* TODO 
        if there are recurring orders we need a credit card.
        $collections = XROWRecurringOrderCollection::fetchByUser();
        if ( $collections )
        {}
        $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                                 'Input required.' ) );
        */
        return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }

    /*!
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {

        if ( $http->hasPostVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $type = $http->postVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) );

            if ( $type == XROWCREDITCARD_TYPE_EUROCARD )
            {
                if (
                      $http->hasPostVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) )
                      or $http->hasPostVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) )
                      or $http->hasPostVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) )
                   )
                {
                    $data = array();

                    $data['type']           = $type;
                    $data['ecname']         = $http->postVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['accountnumber']  = $http->postVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['bankcode']       = $http->postVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) );

                    $data['accountnumber']  = ezcreditcardType::gpgEncode( $data['accountnumber'] );
                    $data['ecname']         = ezcreditcardType::gpgEncode( $data['ecname'] );
                    $data['bankcode']       = ezcreditcardType::gpgEncode( $data['bankcode'] );

                    $doc = new eZDOMDocument( 'creditcard' );
                    $root = ezcreditcardType::createDOMTreefromArray( 'creditcard', $data );
                    $doc->setRoot( $root );
                    $contentObjectAttribute->setAttribute( 'data_text', $doc->toString() );
                    return true;
                }
            }
            else if ( in_array( $type, array( XROWCREDITCARD_TYPE_AMERICANEXPRESS,
                                               XROWCREDITCARD_TYPE_DISCOVER,
                                               XROWCREDITCARD_TYPE_MASTERCARD,
                                               XROWCREDITCARD_TYPE_VISA ) ) )
            {
                if (
                      $http->hasPostVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) ) and
                      $http->hasPostVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) )
                   )
                {
                    $data = array();

                    $data['type']           = $type;
                    $data['name']           = $http->postVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['number']         = $http->postVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['securitycode']   = $http->postVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['month']          = $http->postVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['year']           = $http->postVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) );

                    $data['number']         = ezcreditcardType::gpgEncode( $data['number'] );
                    $data['name']           = ezcreditcardType::gpgEncode( $data['name'] );
                    $data['securitycode']   = ezcreditcardType::gpgEncode( $data['securitycode'] );

                    $doc = new eZDOMDocument( 'creditcard' );
                    $root = ezcreditcardType::createDOMTreefromArray( 'creditcard', $data );
                    $doc->setRoot( $root );
                    $contentObjectAttribute->setAttribute( 'data_text', $doc->toString() );
                    return true;
                }
            }
        }

        return false;
    }
    /*!
     \reimp
    */
    function customObjectAttributeHTTPAction( $http, $action, &$contentObjectAttribute )
    {
        if( $action == "delete_creditcard" )
        {
            $contentObjectAttribute->setAttribute( 'data_text', null );
            $contentObjectAttribute->store();
        }
    }
    /*!
     Does nothing since it uses the data_text field in the content object attribute.
     See fetchObjectAttributeHTTPInput for the actual storing.
    */
    function storeObjectAttribute( &$attribute )
    {
        $data = ezcreditcardType::createArrayfromXML( $attribute->attribute( 'data_text' ) );
        if ( $data['number'] or $data['accountnumber'] )
            $data['has_stored_card'] = 1;
        $doc = new eZDOMDocument( 'creditcard' );
        $root = ezcreditcardType::createDOMTreefromArray( 'creditcard', $data );
        $doc->setRoot( $root );
        $attribute->setAttribute( 'data_text', $doc->toString() );
        if ( isset( $GLOBALS['eZCreditcardCache'] ) )
            unset( $GLOBALS['eZCreditcardCache'] );
    }

    function storeClassAttribute( &$attribute, $version )
    {
    }

    function storeDefinedClassAttribute( &$attribute )
    {
    }

    /*!
     \reimp
    */
    function validateClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
         return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }

    /*!
     \reimp
    */
    function fetchClassAttributeHTTPInput( &$http, $base, &$classAttribute )
    {
        if ( $http->hasPostVariable( 'ContentClass_ezcreditcard_gateway_' . $classAttribute->attribute('id') ) )
        {
            $gatewayArray = $http->postVariable( 'ContentClass_ezcreditcard_gateway_' . $classAttribute->attribute('id') );

            $doc = new eZDOMDocument( 'gatewayinfo' );
            $root = ezcreditcardType::createDOMTreefromArray( 'gatewayinfo', $gatewayArray );
            $doc->setRoot( $root );
            $classAttribute->setAttribute( EZ_DATATYPE_CREDITCARD_GATEWAY_FIELD, $doc->toString() );
        }
        return true;
    }

    /*!
     Returns the content.
    */
    function &objectAttributeContent( &$contentObjectAttribute )
    {
        if ( isset( $GLOBALS['eZCreditcardCache'][$contentObjectAttribute->ID][$contentObjectAttribute->Version] ) )
        {
              return $GLOBALS['eZCreditcardCache'][$contentObjectAttribute->ID][$contentObjectAttribute->Version];
        }
        else
        {
            $content = ezcreditcardType::createArrayfromXML( $contentObjectAttribute->attribute( 'data_text' ) );

            if ( isset( $content['number'] ) )
                $content['number'] = ezcreditcardType::gpgDecode( $content['number'] );

            if ( isset( $content['name'] ) )
                $content['name'] = ezcreditcardType::gpgDecode( $content['name'] );

            if ( isset( $content['securitycode'] ) )
                $content['securitycode'] = ezcreditcardType::gpgDecode( $content['securitycode'] );

            if ( isset( $content['ecname'] ) )
                $content['ecname'] = ezcreditcardType::gpgDecode( $content['ecname'] );

            if ( isset( $content['accountnumber'] ) )
                $content['accountnumber'] = ezcreditcardType::gpgDecode( $content['accountnumber'] );

            if ( isset( $content['bankcode'] ) )
                $content['bankcode'] = ezcreditcardType::gpgDecode( $content['bankcode'] );

            $GLOBALS['eZCreditcardCache'][$contentObjectAttribute->ID][$contentObjectAttribute->Version] = $content;

            return $content;
        }
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( &$contentObjectAttribute )
    {
        return false;
    }

    /*!
     Returns class content.
    */
    function &classAttributeContent( &$classAttribute )
    {
        if ( isset( $GLOBALS['xrowCCClassInfo'][$classAttribute->ID][$classAttribute->Version] ) )
            return $GLOBALS['xrowCCClassInfo'][$classAttribute->ID][$classAttribute->Version];

        $content = array();
        $content['gateway_array'] = xrowPaymentInfo::getGateways();
        $content['card_array']    = ezcreditcardType::getCardTypeName( -1 );

        $cardGatewayArray = array();
        foreach ( $content['card_array'] as $cardKey => $card )
        {
            if ( count( $content['gateway_array'] ) > 0 )
            {
                foreach ( $content['gateway_array'] as $gatewayKey => $gateway )
                {
                    $payObj = xrowPaymentInfo::getInfoClassObj( $gateway['value'] );
                    if ( is_object( $payObj ) )
                    {
                        if ( $payObj->isCardAvailable( $cardKey ) == true )
                            $cardGatewayArray[$cardKey][$gateway['value']] = $gateway;
                    }
                    else
                        eZDebug::writeError( 'PaymentInfo Object not found: ' . $gatewayArray[$data['type']] . 'Info', 'eZCreditcardType::classAttributeContent' );

                }
            }
            else
                eZDebug::writeError( 'No gateways installed.', 'eZCreditcardType::classAttributeContent' );
        }
        //eZDebug::writeDebug ( $cardGatewayArray, 'cardGatewayArray' );
        $content['card_gateway_array'] = $cardGatewayArray;

        $content['gateway'] = ezcreditcardType::createArrayfromXML( $classAttribute->attribute( EZ_DATATYPE_CREDITCARD_GATEWAY_FIELD ) );

        $GLOBALS['xrowCCClassInfo'][$classAttribute->ID][$classAttribute->Version] = $content;

        return $content;
    }

    /*!
     Returns the content of the string for use as a title
    */
    function title( &$contentObjectAttribute )
    {
        return false;
    }

    function hasObjectAttributeContent( &$contentObjectAttribute )
    {
        return trim( $contentObjectAttribute->attribute( 'data_text' ) ) != '';
    }

    /*!
     \reimp
    */
    function isIndexable()
    {
        return false;
    }

    /*!
     \reimp
    */
    function isInformationCollector()
    {
        return false;
    }


    /*!
     \reimp
    */
    function sortKeyType()
    {
        return 'string';
    }

    /*!
      \reimp
    */
    function diff( $old, $new, $options = false )
    {
        include_once( 'lib/ezdiff/classes/ezdiff.php' );
        $diff = new eZDiff();
        $diff->setDiffEngineType( $diff->engineType( 'text' ) );
        $diff->initDiffEngine();
        $diffObject = $diff->diff( $old->content(), $new->content() );
        return $diffObject;
    }

    function createDOMTreefromArray( $name, $array )
    {

        $doc = new eZDOMDocument( $name );
        $root = $doc->createElementNode( $name );
        $keys = array_keys( $array );
        foreach ( $keys as $key )
        {
            if ( is_array( $array[$key] ) )
            {
                //TODO recursive should work too
                // createDOMTreefromArray( $key, $array[$key] )
            }
            else
            {
                $node = $doc->createElementNode( $key );
                $node->appendChild( $doc->createTextNode( $array[$key] ) );
            }

            $root->appendChild( $node );
            unset( $node );
        }
        return $root;
    }

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
                    // do something recurisve here, there is currently no need
                }
            }
        }
        return $result;
    }

    function gpgEncode( $value )
    {
        if ( include_once( eZExtension::baseDirectory() . '/ezgpg/autoloads/ezgpg_operators.php' ) )
        {
            $b_ini = eZINI::instance( 'ezgpg.ini' );
            $key = trim( $b_ini->variable( 'eZGPGSettings', 'KeyID' ) );
            $return = eZGPGOperators::gpgEncode( $value, $key, true );
            if ( $return !== false )
                $value = $return;
        }
        return $value;
    }

    function gpgDecode( $value )
    {
        if ( include_once( eZExtension::baseDirectory() . '/ezgpg/autoloads/ezgpg_operators.php' ) )
        {
            $b_ini = eZINI::instance( 'ezgpg.ini' );
            $key = trim( $b_ini->variable( 'eZGPGSettings', 'KeyID' ) );
            $return = eZGPGOperators::gpgDecode( $value, $key, true );
            if ( $return !== false )
                $value = $return;
        }
        return $value;
    }

    /*!
     \static
     returns the name of the creditcard if $type is found and positve
     if type is -1 the array of available types
     This can be called like ezcreditcardType::getCardTypeName( $type )
    */
    function getCardTypeName( $type )
    {
        if ( !isset( $GLOBALS['xrowCreditCardArray'] ) )
            $GLOBALS['xrowCreditCardArray'] = array (
                XROWCREDITCARD_TYPE_MASTERCARD => ezi18n( 'kernel/classes/datatypes', 'Mastercard' ),
                XROWCREDITCARD_TYPE_VISA => ezi18n( 'kernel/classes/datatypes', 'Visa' ),
                XROWCREDITCARD_TYPE_DISCOVER => ezi18n( 'kernel/classes/datatypes', 'Discover' ),
                XROWCREDITCARD_TYPE_AMERICANEXPRESS => ezi18n( 'kernel/classes/datatypes', 'American Express' ),
                XROWCREDITCARD_TYPE_EUROCARD => ezi18n( 'kernel/classes/datatypes', 'Eurocard' )
            );

        if ( $type == -1 )
            return $GLOBALS['xrowCreditCardArray'];
        else if ( isset( $GLOBALS['xrowCreditCardArray'][$type] ) )
            return $GLOBALS['xrowCreditCardArray'][$type];
        else
            eZDebug::writeError( 'Card type not found.', 'ezcreditcardtype' );

    }

    /// \privatesection

}

eZDataType::register( EZ_DATATYPESTRING_CREDITCARD, 'ezcreditcardtype' );

?>
