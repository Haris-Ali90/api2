<?php

namespace App\Http\Resources;

use App\Models\SprintTaskHistory;
use App\Models\StatusMap;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class RouteStatusListResource extends JsonResource
{
    public function __construct($request)
    {
        parent::__construct($request);


    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [

            'pickup_delay_status' => [
                [
                'id' => 103,
                'description' => 'Delay at pickup',

                ],
                [
                    'id' => 102,
                    'description' => 'Joey Incident',

                ],
                [
                    'id' => 137,
                    'description' => 'Delay in delivery due to weather or natural disaster',

                    ],
                    [
                        'id' => 140,
                        'description' => 'Package missorted, may cause delay.',

                    ]
           ],



           'dropoff_delay_status' => [
                //  [
                // 'id' => 140,
                // 'description' => 'Package missorted',

                // ],
                [
                    'id' => 102,
                    'description' => 'Joey Incident',

                ],
                [
                    'id' => 137,
                    'description' => 'Delay in delivery due to weather or natural disaster',

                ],

              ],


            'pickup_return_status' => [
                [
                'id' => 104,
                'description' => 'Damaged on road - delivery will be attempted',

                ],
                [
                    'id' => 105,
                    'description' => 'Item damaged - returned to merchant ',

                ],
                [
                    'id' => 111,
                    'description' => 'Delivery to hub for return to merchant',

                ],
                [
                    'id' => 119,
                    'description' => 'Unable to load - due to vehicle capacity',

                ]
            ],



            'dropoff_return_status' => [
                [
                    'id' => 104,
                    'description' => 'Damaged on road - delivery will be attempted',

                    ],
                    [
                        'id' => 105,
                        'description' => 'Item damaged - returned to merchant ',

                    ],
                    [
                        'id' => 106,
                        'description' => 'Customer unavailable - delivery returned',

                    ],
                    [
                        'id' => 107,
                        'description' => 'Customer unavailable - Left voice mail - order returned',

                    ],
                    [
                        'id' => 108,
                        'description' => 'Customer unavailable-Incorrect address',

                        ],
                        [
                            'id' => 109,
                            'description' => 'Customer unavailable - Incorrect phone number',

                        ],
                        [
                            'id' => 131,
                            'description' => 'Office closed - returned to hub',

                        ],
                        [
                            'id' => 135,
                            'description' => 'Customer refused delivery',

                        ],
//                        [
//                            'id' => 140,
//                            'description' => 'Package missorted',
//
//                        ],
//                        [
//                            'id' => 140,
//                            'description' => 'Package to be re-assigned due to size',
//
//                        ],
                        [
                            'id' => 110,
                            'description' => 'Delivery to hub for re-delivery',

                        ]

            ],

            'dropoff_status' => [
                [
                    'id' => 114,
                    'description' => 'Successfully delivered at door',

                    ],
                    [
                        'id' => 117,
                        'description' => 'left with concierge',

                    ],
                    [
                        'id' => 118,
                        'description' => 'left at back door',

                    ],
                    [
                        'id' => 132,
                        'description' => 'Office closed - safe dropped',

                    ],
                    [
                        'id' => 138,
                        'description' => 'Delivery left in the garage',

                        ],
                        [
                            'id' => 139,
                            'description' => 'Delivery left on front porch',

                        ],
                        [
                            'id' => 144,
                            'description' => 'Delivered to mailroom',

                        ]

            ]


        ];
    }
}
