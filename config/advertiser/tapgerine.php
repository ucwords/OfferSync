<?php
return [
    'offer_api'        => 'https://api.tapgerine.net/affiliate/offer/findAll/?token=LwXYQm6LCMHMs9zRjkTCYxWWyw8dp8Vg',
    'creative_api'     => 'https://api.tapgerine.net/affiliate/offer/getCreatives/?token=LwXYQm6LCMHMs9zRjkTCYxWWyw8dp8Vg',
    'geo_api'          => 'https://api.tapgerine.net/info/countries/?token=LwXYQm6LCMHMs9zRjkTCYxWWyw8dp8Vg',
    'offer_list'       => function ($data) {
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return $var[ 'Payout' ] < 0.5 ? false : true;
        }
    ],
    'conversion_field' => [
        'offer'          => [
            'name'            => function ($var) {
                return isset($var[ 'Name' ]) ? fmtOfferName($var[ 'Name' ]) : null;
            },
            'advertiser_id'   => function ($var) {
                return 18;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return isset($var[ 'Expiration_date' ]) ? strtotime($var[ 'Expiration_date' ]) : null;
            },
            'status'          => function ($var) { return "active"; },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                return isset($var[ 'Payout' ]) ? $var[ 'Payout' ] : 0;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                return isset($var[ 'Payout' ]) ? round($var[ 'Payout' ] * 1 * 0.8, 2) : 0;
            },
            'preview_url'     => 'Preview_url',
            'destination_url' => function ($var) {
                return $var[ 'Tracking_url' ] . "&aff_sub={click_id}&sub_id={source_id}";
            },
            'description'     => 'Description',
            'currency'        => 'Currency',
        ],
        'offer_geo'      => [
            'country' => function ($var) {
                return $var[ 'Countries' ];
            }
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                $is_iOS = false;
                $is_Android = false;
                if (isset($var[ 'Platforms' ])) {
                    $platform_list = explode(",", $var[ 'Platforms' ]);
                    foreach ($platform_list as $item) {
                        if (in_array($item, ['iPhone', 'iPad']) && $is_iOS == false) {
                            $is_iOS = true;
                        }
                        if (in_array($item, ['Android']) && $is_Android == false) {
                            $is_Android = true;
                        }
                    }
                }
                if ($is_Android) {
                    $platform[] = [
                        'platform' => "Mobile", 'system' => 'Android',
                        'version'  => [], 'is_above' => "0"];
                }
                if ($is_iOS) {
                    $platform[] = [
                        'platform' => "Mobile", 'system' => 'iOS',
                        'version'  => [], 'is_above' => "0"];
                }

                return $platform;
            },
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 0; },
            //'adv_cap_click'      => function ($var) { return 0; },
            //'adv_cap_conversion' => function ($var) { return 50; },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 2; },
            //'aff_cap_click' => function ($var) { return 50; },
            'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ]
    ],
];