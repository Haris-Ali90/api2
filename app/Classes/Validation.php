<?php

namespace App\Classes;

use Carbon\Carbon;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

class Validation extends Validator
{

    /**
     * Define your custom rules error messages
     * @var array
     */
    private const CUSTOM_MESSAGES = [
        'alpha_dash_spaces'            => 'The :attribute may only contain letters, spaces, and dashes.',
        'alpha_num_spaces'             => 'The :attribute may only contain letters, numbers, and spaces.',
        'alpha_num_dash_spaces'        => 'The :attribute may only contain letters, numbers, dashed and spaces.',
        'alpha_num_dash_period_spaces' => 'The :attribute may only contain letters, numbers, dashed, periods and spaces.',
        'us_phone_standards'           => 'The :attribute may only contain numbers, dashes, parenthesis and spaces.',
        'unique_phone'                 => 'The :attribute already exists.',
        'unique_encrypted'             => 'The :attribute already exists.',
        'decimal'                      => 'The :attribute is not a valid decimal.',
        'edu'                          => 'The :attribute value should end with .edu',
        'time_string'                  => 'The :attribute is in invalid format.',
        'empty_html'                   => 'The :attribute should not be left empty.',
        'password_custom'              => 'Password should have minimum 8 characters, one number, one capital letter and one symbol',
        'timestamp_after'              => 'The date time must be greater than current date time',
        'iso_date_after'               => 'The date time must be greater than current date time',
    ];

    /**
     * Constructor to register custom error messages in core
     * @param mixed  $translator
     * @param mixed  $data
     * @param array  $rules
     * @param array  $messages
     * @param array  $customAttributes
     */
    public function __construct( $translator, $data, $rules, $messages = array(), $customAttributes = array() ) {
        parent::__construct( $translator, $data, $rules, $messages, $customAttributes );

        $this->setCustomMessages( self::CUSTOM_MESSAGES);
    }

    /**
     * Allow only english alphabets and numbers
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateEnglishAlphaNum( $attribute, $value, $parameters, $validator ) : bool
    {
        return (bool) preg_match( '/^[A-Za-z0-9]+$/', $value );
    }

    /**
     * Allow only alphabets, spaces and dashes (hyphens and underscores)
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateAlphaDashSpaces( $attribute, $value, $parameters, $validator ) : bool
    {
        return (bool) preg_match( '/^[A-Za-z\s-_]+$/', $value );
    }

    /**
     * Allow only alphabets, numbers, and spaces
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateAlphaNumSpaces( $attribute, $value, $parameters, $validator ) : bool
    {
        return (bool) preg_match( "/^[A-Za-z0-9\s]+$/", $value );
    }

    /**
     * Allow only alphabets, numbers, spaces and dashes (hyphens and underscores)
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateAlphaNumDashSpaces( $attribute, $value, $parameters, $validator ) : bool
    {
        return (bool) preg_match( "/^[A-Za-z0-9\s-_]+$/", $value );
    }

    /**
     * Allow only alphabets, numbers, spaces, periods and dashes (hyphens and underscores)
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateAlphaNumDashPeriodSpaces( $attribute, $value, $parameters, $validator ) : bool
    {
        return (bool) preg_match( "/^[A-Za-z0-9\s-_\.]+$/", $value );
    }

    /**
     * Validates for decimal value given in string
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  array $parameters
     * @param  Validator $validator
     * @return bool
     */
    protected function validateDecimal( $attribute, $value, $parameters, $validator ) : bool
    {
        $start = $parameters[0] ?? '';
        $end   = $parameters[1] ?? '';

        return (bool) preg_match( "/^(\-)?\d{1,".$start."}\.\d{1,".$end."}$/", $value );
    }

    /**
     * Allow only digits, parenthesis, period & dash.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateUsPhone( $attribute, $value, $parameters, $validator ) : bool
    {
        return (bool) preg_match( "/^[\+0-9\(\)\.\-\s]+$/", $value );
    }

    /**
     * Validate mobile number for Saudia Arabia
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     */
    protected function validateSaMobile( $attribute, $value, $parameters, $validator ) : bool
    {
        if ( strlen($value) < 9 ){
            return false;
        }

        if ( substr($value, -9, 1) !== 5 ){
            return false;
        }

        return true;
    }

    /**
     * Validate phone number with the help of package `propaganistas/laravel-phone`
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
//    protected function validatePhone( $attribute, $value, $parameters, $validator )
//    {
//        if ( function_exists('phone') ) {
//            try {
//                return phone($value)->isOfCountry($parameters[0]);
//            } catch (\Exception $e) {
//                return false;
//            }
//        } else {
//            throw new \Exception('Unable to validate your phone number since you do not have phone library package.');
//
//        }
//    }

    /**
     * Validate phone number with the help of package `propaganistas/laravel-phone`
     *
     * @param string $attribute
     * @param string $parameters
     * @param string $validator
     * @param mixed $value
     * @return bool
     */
    protected function validateUniquePhone( $attribute, $value, $parameters, $validator ) : bool
    {
        if ( function_exists('phone') ) {
            try {
                $value = sprintf('+1%s', ltrim(phone($value, 'US')->formatE164(), '+1'));
            } catch (\Exception $e) {}
        }

        $cuArray = [$attribute, $value, $parameters, $validator];

        return call_user_func_array(['parent', 'validateUnique'], $cuArray);
    }

    /**
     * Validate phone number with the help of package `propaganistas/laravel-phone`
     *
     * @param string $attribute
     * @param string $parameters
     * @param string $validator
     * @param mixed $value
     * @return bool
     */
    protected function validateTimestampAfter( $attribute, $value, $parameters, $validator ) : bool
    {
        $value   = Carbon::createFromTimestamp($value)->toDateTimeString();
        $cuArray = [$attribute, $value, $parameters, $validator];

        return call_user_func_array(['parent', 'validateAfter'], $cuArray);
    }

    /**
     * Validate phone number with the help of package `propaganistas/laravel-phone`
     *
     * @param string $attribute
     * @param string $parameters
     * @param string $validator
     * @param mixed $value
     * @return bool
     */
    protected function validateIsoDateAfter( $attribute, $value, $parameters, $validator ) : bool
    {
        $value   = Carbon::parse($value)->timezone('UTC')->toDateTimeString();
        $cuArray = [$attribute, $value, $parameters, $validator];

        return call_user_func_array(['parent', 'validateAfter'], $cuArray);
    }



    /**
     * Validate phone number with the help of package `propaganistas/laravel-phone`
     *
     * @param string $attribute
     * @param string $parameters
     * @param string $validator
     * @param mixed $value
     * @return bool
     */
    protected function validatePasswordCustom( $attribute, $value, $parameters, $validator ) : bool
    {
        #func_get_args() gets the current function arguments and return the array of the arguments

        if(!app()->environment('production')){
            return true;
        }

        if(!call_user_func_array(['parent', 'validateCaseDiff'], func_get_args())){
            return false;
        }

        if(!call_user_func_array(['parent', 'validateNumbers'], func_get_args())){
            return false;
        }

        if(!call_user_func_array(['parent', 'validateLetters'], func_get_args())){
            return false;
        }

        if(!call_user_func_array(['parent', 'validateSymbols'], func_get_args())){
            return false;
        }

        return true;
    }

    /**
     * Validate value after encryption which stored in databsae.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateUniqueEncrypted( $attribute, $value, $parameters, $validator ) : bool
    {
        $value = \App\Classes\RijndaelEncryption::encrypt($value);
        $method = 'validateUnique';

        // First parameter which trigger rule to be executed it is optional, will trigger function if method exist.
        if ( preg_match('%^\[(\w+)\]$%', $parameters[0], $detectMethod) ) {
            $detectMethod = camel_case('validate_'.$detectMethod[1]);
            $method = method_exists($this, $detectMethod) ? $detectMethod : $method;
            array_shift($parameters);
        }

        return call_user_func_array(array($this, $method), [
            $attribute,
            $value,
            $parameters,
            $validator,
        ]);
    }

    protected function validateTimeString( $attribute, $value, $parameters, $validator ) : bool
    {
        return (bool) preg_match('/^(\d|0\d|1\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value);
    }

    protected function validateEdu( $attribute, $value, $parameters, $validator ) : bool
    {
        return ends_with($value, '.edu');
    }

    protected function validateEmptyHtml( $attribute, $value, $parameters, $validator ) : bool
    {
        return !empty(trim(strip_tags($value)));
    }
}
