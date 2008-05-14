<?php

include_once( 'kernel/classes/ezdatatype.php' );
include_once( 'lib/ezlocale/classes/ezdatetime.php' );
include_once( 'lib/ezutils/classes/ezintegervalidator.php' );
include_once( 'kernel/common/i18n.php' );
include_once( 'lib/ezxml/classes/ezxml.php' );
include_once( eZExtension::baseDirectory() . '/recurringorders/classes/xrowpaymentinfo.php' );
include_once( eZExtension::baseDirectory() . '/recurringorders/classes/recurringordercollection.php' );

define( 'EZ_DATATYPESTRING_CREDITCARD', 'ezcreditcard' );
define( 'EZ_DATATYPE_CREDITCARD_GATEWAY_FIELD', 'data_text5' );

define( 'XROWCREDITCARD_TYPE_MASTERCARD', 1 );
define( 'XROWCREDITCARD_TYPE_VISA', 2 );
define( 'XROWCREDITCARD_TYPE_DISCOVER', 3 );
define( 'XROWCREDITCARD_TYPE_AMERICANEXPRESS', 4 );
define( 'XROWCREDITCARD_TYPE_EUROCARD', 5 );

define( 'XROWCREDITCARD_KEY_ECNAME', 'ecname' );
define( 'XROWCREDITCARD_KEY_ACCOUNTNUMBER', 'accountnumber' );
define( 'XROWCREDITCARD_KEY_BANKCODE', 'bankcode' );
define( 'XROWCREDITCARD_KEY_TYPE', 'type' );
define( 'XROWCREDITCARD_KEY_NUMBER', 'number' );
define( 'XROWCREDITCARD_KEY_SECURITYCODE', 'securitycode' );
define( 'XROWCREDITCARD_KEY_MONTH', 'month' );
define( 'XROWCREDITCARD_KEY_MONTH', 'year' );
define( 'XROWCREDITCARD_MAINKEY_CREDITCARD', 'creditcard' );
define( 'XROW_ERROR_NO_STORED_CARD', 101 );

class ezcreditcardType extends eZDataType
{
    /*!
     Initializes with a string id and a description.
    */
    function ezcreditcardType()
    {
        $this->eZDataType( EZ_DATATYPESTRING_CREDITCARD, ezi18n( 'kernel/classes/datatypes', 'Credit card', 'Datatype name' ),
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
                if ( $payObj->validateCardData( $contentObjectAttribute, $classAttribute, $data ) )
                    return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                else
                    return EZ_INPUT_VALIDATOR_STATE_INVALID;
            }
            else
            {
                eZDebug::writeError( 'PaymentInfo Object not found: ' . $gateway . 'Info', 'eZCreditcardType::validateCard' );
                return EZ_INPUT_VALIDATOR_STATE_INVALID;
            }
        }
        else
        {
            eZDebug::writeError( 'Gateway not found: ' . $data['type'], 'eZCreditcardType::validateCard' );
            return EZ_INPUT_VALIDATOR_STATE_INVALID;
        }
    }


    /*!
     \reimp
    */
    function validateObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        include_once( eZExtension::baseDirectory() . '/recurringorders/classes/recurringordercollection.php');
        $classAttribute =& $contentObjectAttribute->contentClassAttribute();

        if ( $http->hasPostVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $type = (int) $http->postVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) );

            if ( $type == XROWCREDITCARD_TYPE_EUROCARD )
            {
                if (  $http->hasPostVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) )
                   )
                {
                    $data = array();
                    $data['type']           = $type;
                    $data['ecname']         = trim( $http->postVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) ) );
                    $data['accountnumber']  = trim( $http->postVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) ) );
                    $data['bankcode']       = trim( $http->postVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) ) );

                    if ( $data['ecname']  == ""
                         and $data['accountnumber']  == ""
                         and $data['bankcode']  == "" )
                    {
                        if ( $this->hasOrderCollection( $contentObjectAttribute ) )
                        {
                            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                                 'Input is required, if you have active subscriptions or recurring orders.' ) );
                            return EZ_INPUT_VALIDATOR_STATE_INVALID;
                        }

                        if ( !$classAttribute->attribute( 'is_information_collector' ) and
                             $contentObjectAttribute->validateIsRequired() )
                        {
                            // users with a test order are allowed to bypass
                            // the card check
                            if ( !$http->hasSessionVariable( 'xrowTestAccountOrder' ) )
                            {
                                $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                                 'Input required.' ) );
                                return EZ_INPUT_VALIDATOR_STATE_INVALID;
                            }
                            else
                                return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                        }
                        else
                            return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                    }
                    else
                    {
                        return $this->validateCard( $data, $contentObjectAttribute, $classAttribute );
                    }
                }
                else
                {
                    if ( !$classAttribute->attribute( 'is_information_collector' ) and
                             $contentObjectAttribute->validateIsRequired() )
                    {
                        // users with a test order are allowed to bypass
                        // the card check
                        if ( !$http->hasSessionVariable( 'xrowTestAccountOrder' ) )
                        {
                            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                             'Input required.' ) );
                            return EZ_INPUT_VALIDATOR_STATE_INVALID;
                        }
                        else
                            return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                    }
                    else
                        return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                }
            }
            else if ( in_array( $type, array( XROWCREDITCARD_TYPE_AMERICANEXPRESS,
                                               XROWCREDITCARD_TYPE_DISCOVER,
                                               XROWCREDITCARD_TYPE_MASTERCARD,
                                               XROWCREDITCARD_TYPE_VISA ) ) )
            {
                if (  $http->hasPostVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) )
                   )
                {
                    $data                   = array();
                    $data['type']           = $type;
                    $data['name']           = trim( $http->postVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) ) );
                    $data['number']         = trim( $http->postVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) ) );
                    $data['securitycode']   = trim( $http->postVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) ) );
                    $data['month']          = $http->postVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['year']           = $http->postVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) );

                    if ( $data['name']  == ""
                         and $data['number']  == ""
                         and $data['securitycode']  == "" )
                    {
                        if ( $this->hasOrderCollection( $contentObjectAttribute ) )
                        {
                            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                                 'Input is required, if you have active subscriptions or recurring orders.' ) );

                            return EZ_INPUT_VALIDATOR_STATE_INVALID;
                        }

                        if ( !$classAttribute->attribute( 'is_information_collector' ) and
                             $contentObjectAttribute->validateIsRequired() )
                        {
                            // users with a test order are allowed to bypass
                            // the card check
                            if ( !$http->hasSessionVariable( 'xrowTestAccountOrder' ) )
                            {
                                $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                                 'Input required.' ) );
                                return EZ_INPUT_VALIDATOR_STATE_INVALID;
                            }
                            else
                                return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                        }
                        else
                            return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                    }
                    else
                    {
                        return $this->validateCard( $data, $contentObjectAttribute, $classAttribute );
                    }
                }
                else
                {
                    if ( !$classAttribute->attribute( 'is_information_collector' ) and
                          $contentObjectAttribute->validateIsRequired() )
                    {
                        // users with a test order are allowed to bypass
                        // the card check
                        if ( !$http->hasSessionVariable( 'xrowTestAccountOrder' ) )
                        {
                            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                             'Input required.' ) );
                            return EZ_INPUT_VALIDATOR_STATE_INVALID;
                        }
                        else
                            return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                    }
                    else
                        return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
                }
            }
        }

        return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }

    function hasOrderCollection()
    {
        if ( isset( $this->hasOrderCollection ) )
            return $this->hasOrderCollection;
        else
            $this->hasOrderCollection = false;

        $collections = XROWRecurringOrderCollection::fetchByUser();

        foreach ( $collections as $collection )
        {
           $list = $collection->fetchList();
           if( count( $list ) > 0 )
           {
                $this->hasOrderCollection = true;
                return $this->hasOrderCollection;
           }
        }
        return $this->hasOrderCollection;
    }

    /*!
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $type = (int) $http->postVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) );

            if ( $type == XROWCREDITCARD_TYPE_EUROCARD )
            {
                if (
                      $http->hasPostVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) )
                      and $http->hasPostVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) )
                   )
                {
                    $data = array();

                    $data['type']           = $type;
                    $data['ecname']         = $http->postVariable( $base . '_ezcreditcard_ecname_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['accountnumber']  = $http->postVariable( $base . '_ezcreditcard_accountnumber_' . $contentObjectAttribute->attribute( 'id' ) );
                    $data['bankcode']       = $http->postVariable( $base . '_ezcreditcard_bankcode_' . $contentObjectAttribute->attribute( 'id' ) );

                    $data = ezcreditcardType::encodeData( $data );

                    $doc = new eZDOMDocument( 'creditcard' );
                    $root = ezcreditcardType::createDOMTreefromArray( 'creditcard', $data );
                    $doc->setRoot( $root );
                    $text = $doc->toString();
                    $contentObjectAttribute->setAttribute( 'data_text', $text );

                    return true;
                }
            }
            else if ( in_array( $type, array( XROWCREDITCARD_TYPE_AMERICANEXPRESS,
                                              XROWCREDITCARD_TYPE_DISCOVER,
                                              XROWCREDITCARD_TYPE_MASTERCARD,
                                              XROWCREDITCARD_TYPE_VISA ) ) )
            {
                if (
                      $http->hasPostVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) ) or
                      $http->hasPostVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) ) or
                      $http->hasPostVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) ) or
                      $http->hasPostVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) ) or
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

                    $data = ezcreditcardType::encodeData( $data );

                    // it's not allowed to store the cvv2 code for security reasons
                    // $data['securitycode']   = ezcreditcardType::gpgEncode( $data['securitycode'] );

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
    
    function encodeData( $data )
    {
        if ( !isset( $data['type'] ) )
            return $data;
            
        if ( $data['type'] == XROWCREDITCARD_TYPE_EUROCARD )
        {
            if ( isset( $data['accountnumber'] ) and $data['accountnumber'] > 0 )
                $data['accountnumber'] = ezcreditcardType::gpgEncode( $data['accountnumber'] );

            if ( isset( $data['ecname'] ) and strlen( $data['ecname'] ) > 0 )
                $data['ecname'] = ezcreditcardType::gpgEncode( $data['ecname'] );

            if ( isset( $data['bankcode'] ) and $data['bankcode'] > 0 )
                $data['bankcode'] = ezcreditcardType::gpgEncode( $data['bankcode'] );
        }
        else 
        {
            if ( isset( $data['number'] ) and $data['number'] > 0 )
                $data['number'] = ezcreditcardType::gpgEncode( $data['number'] );

            if ( isset( $data['name'] ) and strlen( $data['name'] ) > 0 )
                $data['name'] = ezcreditcardType::gpgEncode( $data['name'] );
        }
        return $data;
    }
    
    function decodeData( $data )
    {
        if ( !isset( $data['type'] ) )
            return $data;
        
        if ( $data['type'] == XROWCREDITCARD_TYPE_EUROCARD )
        {
            if ( isset( $data['accountnumber'] ) and strlen( $data['accountnumber'] ) > 0 )
                $data['accountnumber'] = ezcreditcardType::gpgDecode( $data['accountnumber'] );

            if ( isset( $data['ecname'] ) and strlen( $data['ecname'] ) > 0 )
                $data['ecname'] = ezcreditcardType::gpgDecode( $data['ecname'] );

            if ( isset( $data['bankcode'] ) and strlen( $data['bankcode'] ) > 0 )
                $data['bankcode'] = ezcreditcardType::gpgDecode( $data['bankcode'] );
        }
        else 
        {
            if ( isset( $data['number'] ) and strlen( $data['number'] ) > 0 )
                $data['number'] = ezcreditcardType::gpgDecode( $data['number'] );

            if ( isset( $data['name'] ) and strlen( $data['name'] ) > 0 )
                $data['name'] = ezcreditcardType::gpgDecode( $data['name'] );
        }
        return $data;
    }
    
    function onPublish( &$contentObjectAttribute, &$contentObject, &$publishedNodes )
    {
        $hasContent = $contentObjectAttribute->hasContent();
        if ( $hasContent )
        {
            $data = $contentObjectAttribute->content();
            $data = ezcreditcardType::decodeData( $data );
            if ( $data['type'] == XROWCREDITCARD_TYPE_EUROCARD )
            {
                if ( strlen( $data['ecname'] ) > 0
                     and $data['accountnumber'] > 0
                     and $data['bankcode'] > 0 )
                    $data['has_stored_card'] = 1;
                else
                    $data['has_stored_card'] = 0;
            }
            else 
            {
                if ( $data['number'] > 0 and strlen( $data['name'] ) > 0 )
                    $data['has_stored_card'] = 1;
                else
                    $data['has_stored_card'] = 0;
            }
            
            $data = ezcreditcardType::encodeData( $data );
            
            $doc = new eZDOMDocument( 'creditcard' );
            $root = ezcreditcardType::createDOMTreefromArray( 'creditcard', $data );
            $doc->setRoot( $root );
            $contentObjectAttribute->setAttribute( 'data_text', $doc->toString() );
            $contentObjectAttribute->store();
        }
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

            $content = ezcreditcardType::decodeData( $content );    
            
            if ( isset( $content['type'] ) )
                $content['type_name'] = ezcreditcardType::getCardTypeName( $content['type'] );

            if ( isset( $content['has_stored_card'] ) and $content['has_stored_card'] == 1 )
                $content['has_stored_card'] = 1;
            else
                $content['has_stored_card'] = 0;

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
                XROWCREDITCARD_TYPE_EUROCARD => ezi18n( 'kernel/classes/datatypes', 'Debit card' )
            );

        if ( $type == -1 )
            return $GLOBALS['xrowCreditCardArray'];
        else if ( isset( $GLOBALS['xrowCreditCardArray'][$type] ) )
            return $GLOBALS['xrowCreditCardArray'][$type];
        else
            eZDebug::writeError( 'Card type not found.', 'ezcreditcardtype' );

    }

    // returns true, if an user has a stored card
    /**
     *  @access public
     */
    function hasStoredCard( $user = false )
    {
        if ( $user == false )
            $user =& eZUser::currentUser();

        if ( !$user->isLoggedIn() )
            return false;

        $userObj =& $user->contentObject();
        if ( !is_object( $userObj ) )
            return false;

        $contentObjectAttributes = $userObj->contentObjectAttributes();

        foreach( $contentObjectAttributes as $key => $attribute )
        {
            if ( $attribute->DataTypeString == EZ_DATATYPESTRING_CREDITCARD )
            {
                $content = $attribute->content();
                if ( isset( $content['has_stored_card'] ) and
                     $content['has_stored_card'] == 1 )
                     return true;
                else
                    return false;
            }
        }

        return false;
    }

    /// \privatesection
    var $hasOrderCollection;
}

eZDataType::register( EZ_DATATYPESTRING_CREDITCARD, 'ezcreditcardtype' );

?>
