<?php

namespace App\Classes;

use App\Models\JoeysZoneSchedule;
use App\Models\SprintZone;
use App\Models\SprintZoneSchedule;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\ZoneSchedule;
use App\Models\ZoneVendorRelationship;

class Schedule
{

    /**
     * @param $sprintData
     */
    public static function scheduleAvailability($sprintData, $sprint_id)
    {
        $vendor = Vendor::find($sprintData['sprint_creator_id']);
        $zoneVendorRelationship = ZoneVendorRelationship::where('vendor_id', $vendor->id)->pluck('zone_id')->first();

        $sprintZone = [
            'sprint_id' => $sprint_id,
            'zone_id' => $zoneVendorRelationship,
        ];

        $sprintZone = SprintZone::create($sprintZone);

        // check date
        $startTime = $sprintData['sprint_start_time'];
        $start = (explode(":",$startTime));

        $endTime = $sprintData['sprint_end_time'];
        $end = (explode(":",$endTime));

        $start_date = date('Y-m-d'). ' '.$start[0] .':00';
        $end_date = date('Y-m-d'). ' '.$end[0] .':00';


        $zoneSchedule = ZoneSchedule::whereZoneId($sprintZone->zone_id)
            ->where('is_display', 1)
            ->where('start_time', '<=', $start_date)
            ->where('end_time', '>=', $end_date)
            ->where('vehicle_id', $sprintData['sprint_vehicle_id'])
            ->first();

        if(!empty($zoneSchedule)){
            
            $vehicles = Vehicle::find($sprintData['sprint_vehicle_id']);
            $sprintZoneScheduleCount = SprintZoneSchedule::where('zone_schedule_id', $zoneSchedule->id)->count();
            $shiftSprintsCount = $vehicles->capacity * $zoneSchedule->capacity;

            if($shiftSprintsCount == $sprintZoneScheduleCount){
                
                $zoneSchedule = ZoneSchedule::create([
                    'zone_id' => $sprintZone->zone_id,
                    'vehicle_id' => $sprintData['sprint_vehicle_id'],
                    'start_time' => $start_date,
                    'end_time' => $end_date,
                    'capacity' => 1,
                    'is_display' => 1,
                ]);

                $joeySchedule = [
                    'joey_id' => 1,
                    'zone_schedule_id' => $zoneSchedule->id,
                    'start_time' => $start_date,
                ];

                JoeysZoneSchedule::create($joeySchedule);
                SprintZoneSchedule::create([
                    'sprint_id' => $sprint_id,
                    'zone_schedule_id' => $zoneSchedule->id
                ]);
            }

            if($shiftSprintsCount != $sprintZoneScheduleCount){
               
                SprintZoneSchedule::create([
                    'sprint_id' => $sprint_id,
                    'zone_schedule_id' => $zoneSchedule->id
                ]);
            }

        }else{
           
            $zoneSchedule = ZoneSchedule::create([
                'zone_id' => $sprintZone->zone_id,
                'vehicle_id' => $sprintData['sprint_vehicle_id'],
                'start_time' => $start_date,
                'end_time' => $end_date,
                'capacity' => 1,
                'is_display' => 1,
            ]);

            $joeySchedule = [
                'joey_id' => 1,
                'zone_schedule_id' => $zoneSchedule->id,
                'start_time' => $start_date,
            ];

            JoeysZoneSchedule::create($joeySchedule);

            SprintZoneSchedule::create([
                'sprint_id' => $sprint_id,
                'zone_schedule_id' => $zoneSchedule->id
            ]);
        }

        return 'success';
    }

}
