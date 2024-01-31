<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrivacyPolicyResource extends JsonResource
{
    private $_token = '';

    public function __construct($resource)
    {

        parent::__construct($resource);

    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $content ="In order to complete your order successfully, JoeyCo™ requires your personal information. Under no circumstances will any of your personal data or personal identity be shared and will be used solely within JoeyCo. This information could possibly be used for statistical analysis and to create reports that will help us serve you better and could potentially be shared with affiliated parties, however your personal identity will not be compromised. In the unfortunate event of any legal complications, JoeyCo will be obliged to share your personal information with legal authorities. JoeyCo understands that your information shared with us is sensitive and thus acts in accordance with the Personal Information Protection and Electronic Documents Act Canada.";
        return [

            'content' => $content
        ];
    }
}
