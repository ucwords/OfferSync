<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'offersLook',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况
        'offer_list'                => false,       //offer data list
        'offers_item'               => false,       //single offer debug
        'conversion_value_creative' => true,       //数据转化格式后结果 与 素材采集结果
    ],
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'https://feed.insights.ms/v1/offers?access_token=f1812a4b-2f9d-42c4-83c4-9331af9f3c8b',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
   
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'payout' ]['amount'] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'name' ])) {
                return false;
            }
            return true;
        },

    ],
    'conversion_field'    => [
        'offer'          => [
            //'original_offer_id'=>'id',
            'advertiser_offer_id' => function ($var) {
                return $var['id'];
            },
            'name'            => function ($var) {
                if (isset($var['restrictions']['os'])) {
                    if (is_array($var['restrictions']['os'])) {
                        $platform = implode(' ', $var['restrictions']['os']);
                    }                    
                } else {
                    $platform = '';
                }
                if (isset($var[ 'restrictions' ]['country'])) {
                    if (is_array($var['restrictions']['country'])) {
                        $country = implode(' ', $var['restrictions']['country']);
                    } 
                } else {
                    $country = '';
                }

                $name = fmtOfferName($var[ 'name' ] . " " .$platform. " [" .$country. "]");

                return isset($var[ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 101;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return strtotime("now") + 365 * 86400;
            },
            'status'          => function ($var) { return "paused"; },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var[ 'payout' ]['amount']) ? (float)$var[ 'payout' ]['amount'] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payout' ]['amount']) ? (float)$var[ 'payout' ]['amount'] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return isset($var['app_details']['preview_url']) ? $var['app_details']['preview_url'] : null;
             
            },
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{sub_subid}' => '{aff_id}_{source_id}',
                ];

                $var[ 'url' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'url' ]);
   
                return $var[ 'url' ];
            },
            'description'     => ' ',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {

                if (isset($var['restrictions']['os'])) {
                    if (is_array($var['restrictions']['os'])) {
                        $os = implode(' ', $var['restrictions']['os']);
                    }                    
                } else {
                    $os = '';
                }

                $platform = [];
                if ($os == 'ios') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($os == 'android') {
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
                
                //The list of countries is an array
                $geo = $var['restrictions'][ 'country' ];

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
                //dd($target);
                return $geo;
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
            'tag' => function ($var) { return 'CPI';},
        ]
    ],
];