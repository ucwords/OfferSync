<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'Local',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况
        'offer_list'                => false,       //offer data list
        'offers_item'               => false,       //single offer debug
        'conversion_value_creative' => false,       //数据转化格式后结果 与 素材采集结果
    ],
    'http_basic_auth'  => ['karen@leanmobi.com', '3c75f8d9ec3c457bb3113885fbac012b'],
    'offer_api'           => 'https://bingmob.api.offerslook.com/aff/v1/batches/offers?filters[status]=active&type=personal&offset=1&limit=50',
    'advertiser_id'   => 139,
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'data' ][ 'rowset' ]) ? $data[ 'data' ][ 'rowset' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var['offer'][ 'payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var['offer'][ 'name' ])) {
                return false;
            }

            return true;
        },
        'status' => function ($var) {
            return $var['offer']['status'] == 'active' ? true : false;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            'original_offer_id'=> function($var) {
                return $var['offer']['id'];
            },
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var['offer'][ 'name' ] . " " . $var[ 'offer_platform' ]['target'][0]['system'] . " [" . $var[ 'offer_geo' ]['target'][0]['country_code'] . "]");

                return isset($var['offer'][ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 139;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return $var['offer']['end_date'];
            },
            'status'          => function ($var) { return "paused"; },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var['offer'][ 'payout' ]) ? (float)$var['offer'][ 'payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var['offer'][ 'payout' ]) ? (float)$var['offer'][ 'payout' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var['offer']['preview_url'];
            },
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
                return $var[ 'offer' ]['tracking_link'] . "&aff_sub1={click_id}&source_id={aff_id}_{source_id}&aff_sub2={aff_id}_{source_id}";
            },
            'description'     => function ($var) {
                return $var['offer']['description'];
            },
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var['offer_platform'][ 'target' ][0]['system'] == 'iOS') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var['offer_platform'][ 'target' ][0]['system'] == 'Android') {
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
                $geo = array_column($var[ 'offer_geo' ]['target'], 'country_code');
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
        ],
        'offer_category'  =>[      
            //"name" => "API"
            'name' => function ($var) { return 'API';},
        ]
    ],
];