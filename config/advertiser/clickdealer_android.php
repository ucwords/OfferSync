<?php
use Illuminate\Support\Facades\Cache;

return [
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_params'     => ['payout'],
    'offer_api'        => 'http://ofwall.com/api/v1?show_creative=true&api_key=5X6nuwvX5X3ePx7wILO4WU4SjGUQ1GE0&pub_id=6737&c=8435&incent=3&os=Android&country=ALL&campaign_limit=300',
    'creative_api'     => '',
    'geo_api'          => '',
    'offer_list'       => function ($data) {
        return isset($data[ 'ads' ]) ? $data[ 'ads' ] : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return $var[ 'payout' ] < 0.5 ? false : true;
        }
    ],
    'conversion_field' => [
        'offer'          => [
            'name'            => function ($var) {
                $name = fmtOfferName($var[ 'title' ] . " Android [" . $var[ 'countries' ] . "]");

                return isset($var[ 'title' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 32;
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
            'preview_url'     => function ($var) {
                return "https://play.google.com/store/apps/details?id=" . $var[ 'appid' ];
            },
            'destination_url' => function ($var) {
                $fill_vars = [
                    '[cid]'       => '{click_id}',
                    '[device_id]' => '{android_id}',
                    '[idfa]'      => '{ios_idfa}',
                    '[gaid]'      => '{google_aid}',
                    '[affsub1]'   => '{source_id}',
                ];

                $var[ 'clickurl' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'clickurl' ]);

                return $var[ 'clickurl' ];
            },
            'description'     => 'description',
            'currency'        => function ($var) { return 'USD'; },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                $platform[] = [
                    'platform' => "Mobile",
                    'system'   => 'Android',
                    'version'  => [],
                    'is_above' => "0",
                ];

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
                $geo = explode(",", $var[ 'countries' ]);
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