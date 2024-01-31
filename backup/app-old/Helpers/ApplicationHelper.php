<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\Sprint;
/**
 * Phone Format
 */
function phoneFormat($value)
{

    try {
        $phone = sprintf('+1%s', ltrim(phone($value, 'CA')->formatE164(), '+1'));

    } catch (\Exception $e) {
        $phone = $value;
    }
    return $phone;
}

/**
 * JWT Auth
 */
if (!function_exists('jwt')) {
    function jwt()
    {
        return auth()->guard('api');
    }
}

/**
 * JWT Auth
 */
if (!function_exists('jwt_manger')) {
    function jwt_manger()
    {
        return auth()->guard('manager-api');
    }
}

/**
 * slug Maker
 */
function SlugMaker($value, $replacement = '_')
{
    $value = trim(strtolower($value));
    $value = preg_replace("/-$/", "", preg_replace('/[^a-z0-9]+/i', $replacement, strtolower($value)));
    return $value;
}

/**
 * check Permission exist or not
 */
function check_permission_exist($permission, $matching_array)
{
    return in_array(explode('|', $permission)[0], $matching_array);

}

/**
 * this function check the user have permission to access this route
 */

function can_access_route($matching_data_value, $permission_data_array)
{
    //checking the current user is super admin
    //getting super admin role id
    $super_admin_role_id = config('app.super_admin_role_id');
    if (Auth::user()->role_type == $super_admin_role_id) {
        return true;
    }

    // checking the matching data type is array or string
    $matching_data_value_type = gettype($matching_data_value);
    if ($matching_data_value_type == 'string') {
        //checking the route name exist in permissions array
       // return in_array($matching_data_value, $permission_data_array);
    } elseif ($matching_data_value_type == 'array') {
        //checking the route names exist in permissions array
       // $matching_count = count(array_intersect($matching_data_value, $permission_data_array));
      //  return ($matching_count > 0) ? true : false;
        return true;
    }

    // default return false
    return false;


}

/**
 * this function check the user have authority to view cards
 */
function can_view_cards($matching_value, $rights_array)
{
    //checking the current user is super admin

    //getting super admin role id
    $super_admin_role_id = config('app.super_admin_role_id');

    if (Auth::user()->role_type == $super_admin_role_id) {
        return true;
    }

    if ($rights_array) {
        //checking the card view rights exist name exist in permissions array
        return in_array($matching_value, $rights_array);
    }

    return false;

}


/**
 * Has Permission Access
 */
function HasPermissionAccess($user_type, $matching_data, $permissions_array)
{
    /*checking user type*/
    if ($user_type == 'admin') {
        return true;
    }

    /*now checking matching data type */
    $matching_data_type = gettype($matching_data);
    if ($matching_data_type == 'array') {
        $match_data = count(array_intersect($permissions_array, $matching_data));
        if ($match_data > 0) {
            return true;
        }
    } elseif ($matching_data_type == 'string') {
        return in_array($matching_data, $permissions_array);
    }

    //default return typ false
    return false;

}


// function  to convert datetime  string to other  time zons
function ConvertTimeZone($dataTimeString,$CurrentTimeZone = 'UTC' ,$ConvertTimeZone = 'UTC',$format = 'Y-m-d H:i:s')
{
    return Carbon::parse($dataTimeString, $CurrentTimeZone)->setTimezone($ConvertTimeZone)->format($format);
}

function getStatusCodes($type = null)
{
    if($type != null && $type != '')
    {
        $status_codes = config('statuscodes.'.$type);
        $status_codes = array_values($status_codes);
    }
    else
    {
        $status_codes = config('statuscodes');
        foreach($status_codes as $key => $value)
        {
            $status_codes[$key] = array_values($value);
        }

    }

    return $status_codes;
}

/*function for calculate  difference between two date and time*/
function DifferenceTwoDataTime($dataTime1,$dataTime2,$format = 'H:i:s')
{
    // checking the both date are fromted
    if(!DateTime::createFromFormat('Y-m-d H:i:s', $dataTime1)) // checking the date time is format
    {
        return 'start date not set ';
    }
    elseif(!DateTime::createFromFormat('Y-m-d H:i:s', $dataTime2))
    {
        return 'end date not set ';
    }

    // checking the format is for hourly based
    if($format == 'H:i:s')
    {
        $date_time1 = new DateTime($dataTime1);
        $date_time2 = new DateTime($dataTime2);
        $difference = $date_time1->diff($date_time2);
        $hours = ($difference->days * 24) + $difference->h;
        $hours = ($hours > 9)? $hours :'0'.$hours;
        $minutes = ($difference->i > 9) ? $difference->i : '0'.$difference->i;
        $second = ($difference->s > 9 )? $difference->s :'0'.$difference->s ;
        $result = $hours.':'.$minutes.':'.$second;
    }
    else
    {
        $time1 = Carbon::parse($dataTime1);
        $time2 = Carbon::parse($dataTime2);
        $totalDuration = $time1->diffInSeconds($time2);
        $result = gmdate($format, $totalDuration);
    }

    return $result;
}

function HoursToMinutes($data)
{

    $time_data = array_map('intval',explode(':',$data));

    if(!isset($time_data[1]))
    {
        return $time_data = 0;
    };
    $hours_to_minutes = $time_data[0] * 60;
    return  $hours_to_minutes + $time_data[1];
}
function getPercentageValue($percentage,$value2)
{
    $return_data = 0;

    if($percentage != ''  && $value2 != '' && $value2 != null && $percentage != null)
    {
        $percentage = (float) $percentage;
        $value2 = (float) $value2;
        if($percentage >= 0 && $value2 > 0 )
        {
            $return_data = ($percentage / 100) * $value2;
        }

    }

    return $return_data;
}
function TimeStringToSeconds($data)
{

    $time_data = array_map('intval',explode(':',$data));

    if(!isset($time_data[1]))
    {
        return $time_data = 0;
    }
    $hours_in_seconds  = $time_data[0] * 3600;
    $minutes_in_seconds = $time_data[1] * 60;
    $seconds = $time_data[2];
    return  $hours_in_seconds + $minutes_in_seconds + $seconds;
}
function ConvertSecondsToHours($seconds)
{
    return $seconds / 3600;
}

function changeHubIds($hub){
    if($hub == 4){
        $hub = 16;//montreal
    }
    if($hub == 22){
        $hub = 19;// ottawa
    }
    if($hub == 23){
        $hub = 17; //toronto
    }
    if($hub == 26){
        $hub = 129; // vancouver
    }
    return $hub;
}


function ModelFindByType($userType,$email)
{
    if ($userType == 'guest')
    {
        $userModel='App\Models\\'.'JoeycoUsers';
        return $userModel::where('email_address','=',$email)->first();
    }
    elseif ($userType == 'onboarding')
    {
        $userModel='App\Models\\'.'Onboarding';
        return $userModel::where('email','=',$email)->first();
    }
    elseif ($userType == 'dashboard')
    {
        $userModel='App\Models\\'.'Dashboard';
        return $userModel::where('email','=',$email)->first();
    }
    elseif ($userType == 'joey')
    {
        $userModel='App\Models\\'.'Joey';
        return $userModel::where('email','=',$email)->first();
    }
    else
    {
        return  null;
    }
}
