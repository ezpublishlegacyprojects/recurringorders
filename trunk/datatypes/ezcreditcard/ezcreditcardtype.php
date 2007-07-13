<?php

include_once( 'kernel/classes/ezdatatype.php' );
include_once( "lib/ezlocale/classes/ezdatetime.php" );
include_once( 'lib/ezutils/classes/ezintegervalidator.php' );
include_once( 'kernel/common/i18n.php' );
include_once( 'lib/ezxml/classes/ezxml.php' );

define( 'EZ_DATATYPESTRING_CREDITCARD', 'ezcreditcard' );
define( 'EZ_DATATYPE_CREDITCARD_MAX_LEN_FIELD', 'data_int1' );
define( 'EZ_DATATYPE_CREDITCARD_MAX_LEN_VARIABLE', '_ezstring_max_string_length_' );
define( "EZ_DATATYPE_CREDITCARD_DEFAULT_STRING_FIELD", "data_text1" );
define( "EZ_DATATYPE_CREDITCARD_DEFAULT_STRING_VARIABLE", "_ezstring_default_value_" );

define( 'XROWCREDITCARD_TYPE_MASTERCARD', 1 );
define( 'XROWCREDITCARD_TYPE_VISA', 2 );
define( 'XROWCREDITCARD_TYPE_DISCOVER', 3 );
define( 'XROWCREDITCARD_TYPE_AMERICANEXPRESS', 4 );

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
        $this->MaxLenValidator = new eZIntegerValidator();
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
        else
        {
            $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();
            $default = $contentClassAttribute->attribute( "data_text1" );
            if ( $default !== "" )
            {
                $contentObjectAttribute->setAttribute( "data_text", $default );
            }
        }
    }

    /*
     Private method, only for using inside this class.
    */
    function validateCreditcard( $data, &$contentObjectAttribute, &$classAttribute )
    {
        $failure = false;
        if ( !$data['name'] )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Name on creditcard not given' ) );
            $failure = true;
        }
        if ( !preg_match( "/^[0-9]+$/", $data['number'] )  )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Creditcard number is not a number' ) );
            $failure = true;
        }
        if ( !preg_match( "/^[0-9]+$/", $data['securitycode'] )  )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Security code is not a number' ) );
            $failure = true;
        }
        if ( $data['securitycode'] > 9999 or $data['securitycode'] < 100  )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'A security code has 3 to 4 digests.' ) );
            $failure = true;
        }
        $time = eZDateTime::create( -1, -1, -1, $data['month'], -1, $data['year'] );
        $now = new eZDateTime( false );
        if ( $now->isGreaterThan( $time ) )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Your card is expired.' ) );
            $failure = true;
        }
        if ( !in_array( $data['type'], array(
            XROWCREDITCARD_TYPE_AMERICANEXPRESS,
            XROWCREDITCARD_TYPE_DISCOVER,
            XROWCREDITCARD_TYPE_MASTERCARD,
            XROWCREDITCARD_TYPE_VISA
            ) ) )
        {
            $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes',
                                                                 'Your card has a unknowntype' ) );
            $failure = true;
        }
        if ( file_exists( eZExtension::baseDirectory() . '/ezauthorize' ) and !$failure )
        {
            $ini = eZINI::instance( 'ezauthorize.ini' );
            include_once ( 'extension/ezauthorize/classes/ezauthorizeaim.php' );
            // assign variables to Authorize.Net class from post
            $aim = new eZAuthorizeAIM();
            // assign transaction type
            $aim->addField( 'x_type', 'AUTH_ONLY' );
            // assign card name
            $aim->addField( 'x_card_name', $data['name'] );

            // assign card expiration date
            $aim->addField( 'x_exp_date', $time->month() . $time->year() );

            // assign card number
            $aim->addField( 'x_card_num', $data['number']  );

            // check cvv2 code
            if ( $ini->variable( 'eZAuthorizeSettings', 'CustomerCVV2Check' ) == 'true' )
            {
                // assign card security number, cvv2 code
                $aim->addField( 'x_card_code', $data['securitycode'] );
            }
            $aim->addField( 'x_description', 'Authorization Check' );
            $aim->addField( 'x_cust_id', eZUser::currentUserID() );
            // get currency code
            $currency_code =  $ini->variable( 'eZAuthorizeSettings', 'CurrencyCode' );

            // assign currency code
            if ( $currency_code != '' )
            {
                $aim->addField( 'x_currency_code', $currency_code );
            }
            // assign total variables from order $ 1 transaction
            $aim->addField( 'x_amount', '1.00' );
            $aim->addField( 'x_tax', '0.00' );
            // assign merchant account information
            $aim->addField( 'x_login', $ini->variable( 'eZAuthorizeSettings', 'MerchantLogin' ) );
            $aim->addField( 'x_tran_key', $ini->variable( 'eZAuthorizeSettings', 'TransactionKey' ) );

            // set authorize.net mode
            $aim->setTestMode( $ini->variable( 'eZAuthorizeSettings', 'TestMode' ) == 'true' );
            // send payment information to authorize.net
            $aim->sendPayment();
            $response = $aim->getResponse();

            // Enable MD5Hash Verification
            if ( $ini->variable( 'eZAuthorizeSettings', 'MD5HashVerification' ) == 'true' )
            {
                $md5_hash_secret = $ini->variable( 'eZAuthorizeSettings', 'MD5HashSecretWord' );
                $aim->setMD5String ( $md5_hash_secret, $ini->variable( 'eZAuthorizeSettings', 'MerchantLogin' ), $response['Transaction ID'], $order_total_amount );

                // Enable Optional Debug Output | MD5Hash Compare
                if ( $ini->variable( 'eZAuthorizeSettings', 'Debug' ) == 'true' )
                {
                    ezDebug::writeDebug( 'Server md5 hash is ' . $response["MD5 Hash"] . ' and client hash is ' . strtoupper( md5( $aim->getMD5String ) ) . ' from string' . $aim->getMD5String );
                }
                $md5pass = $aim->verifyMD5Hash();
            }
            else
            {
                $md5pass = true;
            }

            eZDebug::writeDebug( $response, 'eZAuthorizeGateway response'  );
            if ( $aim->hasError() or !$md5pass )
            {
                if ( !$md5pass )
                {
                    $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes', 'This transaction has failed to
                    verify that the use of a secure transaction (MD5 Hash Failed).
                    Please contact the site administrator and inform them of
                    this error. Please do not try to resubmit payment.' ) );
                }
                $contentObjectAttribute->setValidationError( ezi18n( 'kernel/classes/datatypes', $response['Response Reason Text'] ) );
                $failure = true;
            }
        }

        if ( $failure )
            return EZ_INPUT_VALIDATOR_STATE_INVALID;
        else
            return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }


    /*!
     \reimp
    */
    function validateObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
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
            $data['type'] = $http->postVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['name'] = $http->postVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['number'] = $http->postVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['securitycode'] = $http->postVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['month'] = $http->postVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['year'] = $http->postVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) );


            $classAttribute =& $contentObjectAttribute->contentClassAttribute();

            if ( $data['name']  == "" or $data['number']  == "" or $data['securitycode']  == "" )
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
                return $this->validateCreditcard( $data, $contentObjectAttribute, $classAttribute );
            }
        }
        return EZ_INPUT_VALIDATOR_STATE_ACCEPTED;
    }

    /*!
     Fetches the http post var string input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( &$http, $base, &$contentObjectAttribute )
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
            $data['type'] = $http->postVariable( $base . '_ezcreditcard_type_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['name'] = $http->postVariable( $base . '_ezcreditcard_name_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['number'] = $http->postVariable( $base . '_ezcreditcard_number_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['securitycode'] = $http->postVariable( $base . '_ezcreditcard_securitycode_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['month'] = $http->postVariable( $base . '_ezcreditcard_month_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['year'] = $http->postVariable( $base . '_ezcreditcard_year_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['number'] = ezcreditcardType::gpgEncode( $data['number'] );
            $data['name'] = ezcreditcardType::gpgEncode( $data['name'] );
            $data['securitycode'] = ezcreditcardType::gpgEncode( $data['securitycode'] );
            $doc = new eZDOMDocument( 'creditcard' );
            $root = ezcreditcardType::createDOMTreefromArray( 'creditcard', $data );
            $doc->setRoot( $root );
            $contentObjectAttribute->setAttribute( 'data_text', $doc->toString() );
            return true;
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
        if ( $data['number'] )
            $data['has_stored_card'] = 1;
        $doc = new eZDOMDocument( 'creditcard' );
        $root = ezcreditcardType::createDOMTreefromArray( 'creditcard', $data );
        $doc->setRoot( $root );
        $attribute->setAttribute( 'data_text', $doc->toString() );
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
        $maxLenName = $base . EZ_DATATYPE_CREDITCARD_MAX_LEN_VARIABLE . $classAttribute->attribute( 'id' );
        $defaultValueName = $base . EZ_DATATYPE_CREDITCARD_DEFAULT_STRING_VARIABLE . $classAttribute->attribute( 'id' );
        if ( $http->hasPostVariable( $maxLenName ) )
        {
            $maxLenValue = $http->postVariable( $maxLenName );
            $classAttribute->setAttribute( EZ_DATATYPE_CREDITCARD_MAX_LEN_FIELD, $maxLenValue );
        }
        if ( $http->hasPostVariable( $defaultValueName ) )
        {
            $defaultValueValue = $http->postVariable( $defaultValueName );

            $classAttribute->setAttribute( EZ_DATATYPE_CREDITCARD_DEFAULT_STRING_FIELD, $defaultValueValue );
        }
        return true;
    }

    /*!
     Returns the content.
    */
    function &objectAttributeContent( &$contentObjectAttribute )
    {
         $content = ezcreditcardType::createArrayfromXML( $contentObjectAttribute->attribute( 'data_text' ) );
         $content['number'] = ezcreditcardType::gpgDecode( $content['number'] );
         $content['name'] = ezcreditcardType::gpgDecode( $content['name'] );
         $content['securitycode'] = ezcreditcardType::gpgDecode( $content['securitycode'] );
         return $content;
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( &$contentObjectAttribute )
    {
        return false;
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
    function gpgEncode( $value )
    {
        if ( include_once( 'extension/ezgpg/autoloads/ezgpg_operators.php' ) )
        {
            $b_ini = eZINI::instance( 'ezgpg.ini' );
            $key = trim( $b_ini->variable( 'eZGPGSettings', 'KeyID' ) );
            $return = eZGPGOperators::gpgEncode( $value, $key, true );
            if ( $return !== false )
                $value = $return;
        }
        return $value;
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
    function gpgDecode( $value )
    {
        $return = $value;
        if ( include_once( 'extension/ezgpg/autoloads/ezgpg_operators.php' ) )
        {
            $b_ini = eZINI::instance( 'ezgpg.ini' );
            $key = trim( $b_ini->variable( 'eZGPGSettings', 'KeyID' ) );
            $return = eZGPGOperators::gpgDecode( $value, $key, true );
            if ( $return !== false )
                $value = $return;
        }
        return $value;
    }
    /// \privatesection
    /// The max len validator
    var $MaxLenValidator;
}

eZDataType::register( EZ_DATATYPESTRING_CREDITCARD, 'ezcreditcardtype' );

?>
