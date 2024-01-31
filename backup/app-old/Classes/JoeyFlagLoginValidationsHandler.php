<?php

namespace App\Classes;

use App\JoyFlagLoginValidations;
use App\CustomerIncidents;

class JoyFlagLoginValidationsHandler
{

    protected $joey_id;
    protected $flag_incident_id;
    protected $Joey_performance_history_id;
    protected $priority;
    protected $savingData = [];

    public function __construct()
    {

    }

    public function setValues($joey_id,$flag_incident_id,$Joey_performance_history_id)
    {
        $this->joey_id = $joey_id;
        $this->flag_incident_id = $flag_incident_id;
        $this->Joey_performance_history_id = $Joey_performance_history_id;
        return $this;
    }

    public function applyAction()
    {

        $current_date = date('Y-m-d');

        $joy_flag_login_validations_data =  JoyFlagLoginValidations::where('joey_id',$this->joey_id)
            ->orderBy('id','DESC')
            ->first();

        $CustomerIncidentsValue = CustomerIncidents::find($this->flag_incident_id);
        // checking if the validation should applied
        if($CustomerIncidentsValue->is_applied_login_validation  == 0)
        {
            return false;
        }
		$start_date =date("Y-m-d", strtotime("+1 day"));

        // saving data
        $saving_data = [
            'joey_id'=>$this->joey_id,
            'flag_incident_ref_id'=>$this->flag_incident_id,
            'joey_performance_history_id'=>$this->Joey_performance_history_id,
            'priority'=>$CustomerIncidentsValue->priority ?? 1,
            'window_start'=>$start_date,
            'window_end'=>null,
            'is_blocked'=>1,
        ];

        // getting the duration string from label
        /*$regex = '/(\d|days|day|week|weeks|month|months|years|year)/im';
        $str = $CustomerIncidentsValue->label;
        preg_match_all($regex, $str, $matches);

        // adding start date and end date if the label contain any duration
        if(isset($matches[0][0]) && isset($matches[0][1]))
        {   $duration_number = $matches[0][0];
            $duration_period = $matches[0][1];
            $saving_data['window_start'] = date('Y-m-d');
            $saving_data['window_end'] = date('Y-m-d',strtotime($duration_number.' '.$duration_period));

        }*/

        //getting the duration
        $days_duration = $CustomerIncidentsValue->days_duration;

        // adding start date and end date if the label contain any duration
        if(!is_null($days_duration))
        {
            $saving_data['window_start'] = $start_date;
            $saving_data['window_end'] = date('Y-m-d',strtotime($days_duration.' days'));

        }

        // checking the data is not null
        if(is_null($joy_flag_login_validations_data))
        {
            JoyFlagLoginValidations::create(
                $saving_data
            );
            return true;

        }
        elseif($joy_flag_login_validations_data->priority < $CustomerIncidentsValue->priority || ( $saving_data['window_end'] > $joy_flag_login_validations_data->window_end && $joy_flag_login_validations_data->window_end != null))
        {
            JoyFlagLoginValidations::create(
                $saving_data
            );
            return true;

        }

        return false;


    }





}