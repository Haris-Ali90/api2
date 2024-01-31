<?php

namespace App\Models;

use App\Models\MetaData;

class UserMeta extends MetaData
{
    // Possible meta values for user object:
    // canceled_trips, gender, school_name, student_organization, graduation_year, postal_code, birth_date, rating, sync_friends, has_facebook_integrated, driving_license_no, vehicle_id_number, vehicle_type, vehicle_make, vehicle_model, vehicle_year, insurance_no, insurance_company, policy_effective_date, policy_expiry_date, driver_documents, unread_notifications, monthly_document_notification, half_monthly_document_notification, documents_valid

    /**
     * @var array
     */
    protected $fillable = ['key', 'value'];

    protected $table = 'user_meta';
}
