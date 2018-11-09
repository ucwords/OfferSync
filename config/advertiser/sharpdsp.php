<?php
//http://sharpdsp.com/apiv1/apib/feedads?token=6fb0b5df-0ae2-4339-a191-074936af2d03
use Illuminate\Support\Facades\Cache;

return [
    'offer_api'        => 'http://sharpdsp.com/apiv1/apib/feedads?token=6fb0b5df-0ae2-4339-a191-074936af2d03',
    'creative_api'     => '',
    'geo_api'          => '',
    'offer_list'       => function ($data) {
        return isset($data[ 'data' ]) ? $data[ 'data' ] : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return $var[ 'payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var){
            //dd($var['name'],123);
            return empty($var[ 'name' ]) ? false : true;
        }
    ],
    'conversion_field' => [
        'offer'          => [
            'name'            => function ($var) {
                $name = fmtOfferName($var[ 'name' ] . " " . $var[ 'opsystem' ] . " [" . implode(" ", $var[ 'countries' ]) . "]");

                return isset($var[ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 37;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return strtotime("now") + 365 * 86400;
            },
            'status'          => function ($var) { return "active"; },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var[ 'payout' ]) ? $var[ 'payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payout' ]) ? $var[ 'payout' ] : 0;

                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => 'previewurl',
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{tp_placementid}' => '{click_id}',
                    '{tp_aaid}'        => '{google_aid}',
                    '{tp_sub_affid}'   => '{source_id}',
                ];
                if ($var[ 'opsystem' ] == 'ios') {
                    $fill_vars[ '{tp_aaid}' ] = '{ios_idfa}';
                }

                $var[ 'clickurl' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'clickurl' ]);

                return $var[ 'clickurl' ] ;
            },
            'description'     => 'name',
            'currency'        => function ($var) { return 'USD'; },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'opsystem' ] == 'ios') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'opsystem' ] == 'android') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'Android',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }

                return $platform;
            },
        ],
        'offer_geo'      => [
            'target' => function ($var) {
                $countries = [];
                $countries_cache = Cache::store('file')->get('countries', []);
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
                $geo = $var[ 'countries' ];
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ $item ][ 'name' ])) {
                            $target[] = [
                                "type"    => 2,
                                "country" => $countries[ $item ][ 'name' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 2,
                        "country" => $countries[ $var[ 'geo' ] ][ 'name' ],
                        "city"    => [],
                    ];
                }


                return $target;
            }
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
    ]
];