<?php
use Illuminate\Support\Facades\Cache;

return [
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'        => 'http://api.ad688.com/v1?cid=MobHuge&token=6f130f57294544738b6bd5686fcc229e',
    //'offer_api_post_json' => '',
    'creative_api'     => '',
    'geo_api'          => '',
    'offer_list'       => function ($data) {
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return (float)$var[ 'price' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'offer_name' ])) {
                return false;
            }

            return true;
        }
    ],
    'conversion_field' => [
        'offer'          => [
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'offer_name' ]);

                return isset($var[ 'offer_name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 20;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 150;
            },
            'end_date'        => function ($var) {
                return strtotime($var[ "end_date" ]);
            },
            'status'          => function ($var) {
                if ($var[ 'status' ] == 'running') {
                    return "active";
                } else {
                    return "paused";
                }
            },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var[ 'price' ]) ? (float)$var[ 'price' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'price' ]) ? (float)$var[ 'price' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => 'preview_link',
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{tp_aaid}' => '{google_aid}',
                    '{source}'  => '{source_id}',
                    '{idfa}'    => '{ios_idfa}',
                ];

                $var[ 'tracking_link' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'tracking_link' ]);

                return $var[ 'tracking_link' ] . "&sub_id={source_id}";
            },
            'description'     => 'app_desc',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'platform' ] == 'iOS') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'platform' ] == 'Android') {
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
                $geo = explode(',', $var[ 'geo' ]);
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