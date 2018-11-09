<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'storage'             => 'offersLook',
     'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况
        'offer_list'                => false,       //offer data list
        'offers_item'               => true,       //single offer debug
        'conversion_value_creative' => false,       //数据转化格式后结果 与 素材采集结果
    ],
    'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'        => 'http://sdk.promoadx.com/api/affoffer/index?type=all',
    'creative_api'     => '',
    'geo_api'          => '',
    'offer_list'       => function ($data) {
        return isset($data) ? $data : null;
    },
    'pay_out_rate'     => 0.8,
    'offer_filter'     => [
        'revenue' => function ($var) {
            return $var[ 'payout' ][ 0 ][ 'payout' ] < 0.5 ? false : true;
        },
        /*'preview_url' => function ($var) {
            return empty($var['preview_url']) ? false : true;
        }*/
        
    ],
    'conversion_field' => [
        'offer'     => [
            'advertiser_offer_id' => function ($var) {
                return $var['offer_id'];
            },
            'name'            => function ($var) {
                //$name = str_replace([';', '&', ":", ","], " ", $var[ 'offer_name' ]);
                //$name = str_replace(['(', ')'], ['[', ']'], $name);
                $name = fmtOfferName($var[ 'offer_name' ]);

                return isset($var[ 'offer_name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 7;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return isset($var[ 'end_time' ]) ? strtotime($var[ 'end_time' ]) : null;
            },
            'status'          => function ($var) { return "paused"; },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var[ 'payout' ][ 0 ][ 'payout' ]) ? $var[ 'payout' ][ 0 ][ 'payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payout' ][ 0 ][ 'payout' ]) ? $var[ 'payout' ][ 0 ][ 'payout' ] : 0;

                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => 'preview_url',
            'destination_url' => function ($var) {
                return $var[ 'tracking_url' ] . "&aff_sub1={click_id}&source_id={aff_id}_{source_id}";
            },
            'description'     => 'description',
            'currency'        => 'currency',
        ],

        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'platform' ][0]['system'] == 'iOS') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => ' ',
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'platform' ][0]['system'] == 'Android') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'Android',
                        'version'  => ' ',
                        'is_above' => "0",
                    ];
                }

                return $platform;
            },
        ],

        'offer_geo' => [
            'target' => function ($var) {
                $geo = $var[ 'geo' ];
                $target = [];
                foreach ($geo as $item) {
                    //var_dump($item[ 'city' ]);
                    $target[] = [
                        "type"    => $item[ 'type' ] == 'Include' ? 1 : 2,
                        "country" => $item[ 'code' ],
                        "city"    => isset($item[ 'city' ]) ? explode("-", $item[ 'city' ]) : [],
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
    ],
];