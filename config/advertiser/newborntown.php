<?php
use Illuminate\Support\Facades\Cache;

//newborntown
return [
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'offersLook',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况
        'offer_list'                => false,       //offer data list
        'offers_item'               => false,       //single offer debug
        'conversion_value_creative' => false,       //数据转化格式后结果 与 素材采集结果
    ],
    'offer_params'     => ['payout'],
    'offer_api'        => 'http://pspm.pingstart.com/api/v2/campaigns?token=8bd5848d-1660-4c3b-943e-219b00d657b4&publisher_id=1610',
    'creative_api'     => '',
    'geo_api'          => '',
    'offer_list'       => function ($data) {
        return isset($data[ 'campaigns' ]) ? $data[ 'campaigns' ] : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return $var[ 'payout' ] < 0.5 ? false : true;
        }
    ],
    'conversion_field' => [
        'offer'          => [
            'advertiser_offer_id' => function ($var) {
                return $var['id'];
            },
            'name'            => function ($var) {
                $platform = $var[ 'targeting' ][ 'platform' ];
                $geo = implode(' ', $var[ 'targeting' ][ 'geo' ]);
                $name = fmtOfferName($var[ 'campaign_name' ] . "${platform} [" . $geo . "]");

                return isset($var[ 'campaign_name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 25;
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
                $payout = isset($var[ 'payout' ]) ? $var[ 'payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payout' ]) ? $var[ 'payout' ] : 0;

                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var[ 'product_info' ][ 'preview_link' ];
            },
            'destination_url' => function ($var) {
                return $var[ 'tracking_link' ] . "&sub_1={click_id}&publisher_slot={source_id}";
            },
            'description'     => function ($var) {
                return $var[ 'product_info' ][ 'native_one_sentence_description' ];
            },
            'currency'        => function ($var) { return 'USD'; },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                $platform[] = [
                    'platform' => "Mobile",
                    'system'   => $var[ 'targeting' ][ 'platform' ],
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
                $geo = array_values($var[ 'targeting' ][ 'geo' ]);
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        $target[] = [
                            "type"    => 2,
                            "country" => $item,
                            "city"    => [],
                        ];
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
            'name' => function ($var) { return 'CPI';},
        ]
    ]
];