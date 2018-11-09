<?php
return [
    'storage'             => 'Local', 
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'        => 'https://api.z2z.org/getApp?affid=68892&android=1&ios=1&apikey=81f3bd12-0b7b-4c1f-90d8-35ca442ddf48',
    //'offer_api_post_json' => '',
    'creative_api'     => '',
    'geo_api'          => '',
    'offer_list'       => function ($data) {
        return isset($data[ 'data' ]) ? $data[ 'data' ] : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return (float)$var[ 'payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'offerName' ])) {
                return false;
            }

            return true;
        }
    ],
    'conversion_field' => [
        'offer'          => [
            'original_offer_id'=>'appid',
            'name'            => function ($var) {
                //AE|SA
                //dd()
                $os = "";
                $os .= $var[ 'Android' ] == "true" ? 'Android' : null;
                $os .= $var[ 'iOS' ] == "true" ? 'iOS' : null;
                $name = fmtOfferName($var[ 'offerName' ] . " " . $os . " [" . $var[ 'accept' ] . "]");

                return isset($var[ 'offerName' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 24;
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
                $payout = isset($var[ 'payout' ]) ? (float)$var[ 'payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payout' ]) ? (float)$var[ 'payout' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => 'previewLink',
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{clickid}'   => '{click_id}',
                    '{gaid}'      => '{google_aid}',
                    '{idfa}'      => '{ios_idfa}',
                    '{publisher}' => '{source_id}',
                    '{traffic}'   => '{source_id}',
                ];

                $var[ 'trackingLink' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'trackingLink' ]);

                return $var[ 'trackingLink' ];
            },
            'description'     => 'offerName',
            'currency'        => 'currency',
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'iOS' ] == 'true') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'Android' ] == 'true') {
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

                $geo = explode(',', $var[ 'accept' ]);
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {

                        $target[] = [
                            "type"    => 2,
                            "country" => $countries[ $item ][ 'name' ],
                            "city"    => [],
                        ];

                    }
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