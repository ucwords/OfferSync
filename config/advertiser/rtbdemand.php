<?php
use Illuminate\Support\Facades\Cache;

return [
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'        => 'http://retail9.com/genxml.php?affid=321162&item=1000&img=1',
    //'offer_api_post_json' => '',
    'creative_api'     => '',
    'geo_api'          => '',
    'offer_list'       => function ($data) {
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return $var[ 'payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'name' ]) || empty($var[ 'preview_url' ])) {
                return false;
            }

            return true;
        },
        'os'      => function ($var) {
            $is_os = false;
            if (strpos($var[ 'preview_url' ], 'google.com') != false
                || strpos($var[ 'preview_url' ], 'itunes.apple.com') != false
            ) {
                $is_os = true;
            }

            return $is_os;
        }
    ],
    'conversion_field' => [
        'offer'          => [
            'name'            => function ($var) {
                $var[ 'opsystem' ] = '';
                if (strpos($var[ 'preview_url' ], 'google.com') != false) {
                    $var[ 'opsystem' ] .= ' Android';
                }
                if (strpos($var[ 'preview_url' ], 'itunes.apple.com') != false) {
                    $var[ 'opsystem' ] .= ' iOS';
                }
                $name = fmtOfferName($var[ 'name' ] . " " . $var[ 'opsystem' ] . " [" . $var[ 'countries' ] . "]");

                return isset($var[ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 19;
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
            'preview_url'     => 'preview_url',
            'destination_url' => function ($var) {
                /*$fill_vars = [
                    '{tp_placementid}' => '{click_id}',
                    '{tp_aaid}'        => '{google_aid}',
                    '{tp_sub_affid}'   => '{source_id}',
                ];
                if ($var[ 'opsystem' ] == 'ios') {
                    $fill_vars[ '{tp_aaid}' ] = '{ios_idfa}';
                }

                $var[ 'tracking_link' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'tracking_link' ]);
                */
                return $var[ 'tracking_link' ] . "&aff_sub={click_id}&aff_sub2={source_id}";
            },
            'description'     => 'description',
            'currency'        => 'currency',
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if (strpos($var[ 'preview_url' ], 'google.com') != false) {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if (strpos($var[ 'preview_url' ], 'itunes.apple.com') != false) {
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
                $geo = explode(',', $var[ 'countries' ]);
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