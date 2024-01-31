<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AboutUsResource extends JsonResource
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

        $content ="We cater to your delivery needs with our fleet of free-lance couriers called Joeys! By registering as a Joey, people seeking courier jobs will find everyday delivery runs to be quite profitable and sustainable, not to mention convenient with our flexible hours and customized shifts. Marrying a fleet based courier service with businesses opens doors to customers for shopping and ordering through JoeyCo's online marketplace when and where they need. JoeyCo takes on the task of connecting businesses with the the right Joey to fulfil their deliveries of both their online and offline sales while providing an online web store and platform to manage their products and promotions with their community.";
        return [
            'content' => $content

        ];
    }
}
