<?php


/**
 * Status cords config
 * @Creator Adnan nadeem
 * @date   23/10/2020
 */

return [
    'competed'=>
        [
            "JCO_ORDER_DELIVERY_SUCCESS"=>17,
            "JCO_HAND_DELIEVERY" => 113,
            "JCO_DOOR_DELIVERY" => 114,
            "JCO_NEIGHBOUR_DELIVERY" => 116,
            "JCO_CONCIERGE_DELIVERY" => 117,
            "JCO_BACK_DOOR_DELIVERY" => 118,
            "JCO_OFFICE_CLOSED_DELIVERY" => 132,
            "JCO_DELIVER_GERRAGE" => 138,
            "JCO_DELIVER_FRONT_PORCH" => 139,
            "JCO_DEILVER_MAILROOM" => 144
        ],
    'return'=>
        [
            "JCO_ON_WAY_PICKUP" => 101,
            "JCO_INCIDENT" => 102,
            "JCO_DELAY_PICKUP" => 103,
            "JCO_ITEM_DAMAGED_INCOMPLETE" => 104,
            "JCO_ITEM_DAMAGED_RETURN" => 105,
            "JCO_CUSTOMER_UNAVAILABLE_DELIEVERY_RETURNED" => 106,
            "JCO_CUSTOMER_UNAVAILABLE_LEFT_VOICE" => 107,
            "JCO_CUSTOMER_UNAVAILABLE_ADDRESS" => 108,
            "JCO_CUSTOMER_UNAVAILABLE_PHONE" => 109,
            "JCO_HUB_DELIEVER_REDELIEVERY" => 110,
            "JCO_HUB_DELIEVER_RETURN" => 111,
            "JCO_ORDER_REDELIVER" => 112,
            "JCO_ORDER_RETURN_TO_HUB" => 131,
            "JCO_CUSTOMER_REFUSED_DELIVERY" => 135,
            "CLIENT_REQUEST_CANCEL_ORDER" => 136,
            "DAMAGED_ROAD" => 143
        ],

    'unattempted'=>
        [
            "JCO_ORDER_NEW" => 13,
            "JCO_ORDER_SCHEDULED" => 61,
            "JCO_ORDER_AT_HUB_PROCESSING" => 124
        ],
    'sort' =>
        [
            "JCO_PACKAGES_SORT"  =>133
        ],
    'pickup'=>
        [
           "JCO_HUB_PICKUP"=>121
        ],
        'delay'=>
        [
            "MARK_DELAY"=>255
        ],
    'at_hub_statuses'=>
        [
            'JCO_HUB_PICKUP'=>121,
            'JCO_ORDER_AT_HUB_PROCESSING'=>124,
            'JCO_PACKAGES_SORT'=>133,

        ],
    'status_labels'=>
        [
            "136" => "Client requested to cancel the order",
            "137" => "Delay in delivery due to weather or natural disaster",
            "118" => "left at back door",
            "117" => "left with concierge",
            "135" => "Customer refused delivery",
            "108" => "Customer unavailable-Incorrect address",
            "106" => "Customer unavailable - delivery returned",
            "107" => "Customer unavailable - Left voice mail - order returned",
            "109" => "Customer unavailable - Incorrect phone number",
            "142" => "Damaged at hub (before going OFD)",
            "143" => "Damaged on road - undeliverable",
            "144" => "Delivery to mailroom",
            "103" => "Delay at pickup",
            "139" => "Delivery left on front porch",
            "138" => "Delivery left in the garage",
            "114" => "Successful delivery at door",
            "113" => "Successfully hand delivered",
            "120" => "Delivery at Hub",
            "110" => "Delivery to hub for re-delivery",
            "111" => "Delivery to hub for return to merchant",
            "121" => "Pickup from Hub",
            "102" => "Joey Incident",
            "104" => "Damaged on road - delivery will be attempted",
            "105" => "Item damaged - returned to merchant",
            "129" => "Joey at hub",
            "128" => "Package on the way to hub",
            "140" => "Delivery missorted, may cause delay",
            "116" => "Successful delivery to neighbour",
            "132" => "Office closed - safe dropped",
            "101" => "Joey on the way to pickup",
            "32" => "Order accepted by Joey",
            "14" => "Merchant accepted",
            "36" => "Cancelled by JoeyCo",
            "124" => "At hub - processing",
            "38" => "Draft",
            "18" => "Delivery failed",
            "56" => "Partially delivered",
            "17" => "Delivery success",
            "68" => "Joey is at dropoff location",
            "67" => "Joey is at pickup location",
            "13" => "Waiting for merchant to accept",
            "16" => "Joey failed to pickup order",
            "57" => "Not all orders were picked up",
            "15" => "Order is with Joey",
            "112" => "To be re-attempted",
            "131" => "Office closed - returned to hub",
            "125" => "Pickup at store - confirmed",
            "61" => "Scheduled order",
            "37" => "Customer cancelled the order",
            "34" => "Customer is editting the order",
            "35" => "Merchant cancelled the order",
            "42" => "Merchant completed the order",
            "54" => "Merchant declined the order",
            "33" => "Merchant is editting the order",
            "29" => "Merchant is unavailable",
            "24" => "Looking for a Joey",
            "23" => "Waiting for merchant(s) to accept",
            "28" => "Order is with Joey",
            "133" => "Packages sorted",
            "55" => "ONLINE PAYMENT EXPIRED",
            "12" => "ONLINE PAYMENT FAILED",
            "53" => "Waiting for customer to pay",
            "141" => "Lost package",
            "60" => "Task failure"
        ]
];
