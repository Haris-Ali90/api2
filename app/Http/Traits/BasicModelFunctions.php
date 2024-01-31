<?php
namespace App\Http\Traits;


/**
 * creator : Adnan nadeem
 * Email : adnannadeem1994@gmail.com
 */

trait BasicModelFunctions {

    public function getStatusCodesWithKey($type = null)
    {
        if($type != null)
        {
            $status_codes = config('statuscodes.'.$type);
        }
        else
        {
            $status_codes = config('statuscodes');
        }
        return $status_codes;
    }

    public function getStatusCodes($type = null)
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

}
