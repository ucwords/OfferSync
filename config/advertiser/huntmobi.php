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
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'http://api.wowogp.com/index.php?m=advert&p=getoffer&app_id=104&app_key=d448393da4ffc2f13de6b81211f00d19',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {

            return (float)$var[ 'price' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'name' ])) {
                return false;
            }
            return true;
        },
        'destination_url' => function ($var) {
            return empty($var['tracklink']) ? false : true;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'name' ] . " " . $var[ 'platform' ] . " [" . $var[ 'countries' ] . "]");

                return isset($var[ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 131;
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
                $payout = isset($var[ 'price' ]) ? (float)$var[ 'price' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'price' ]) ? (float)$var[ 'price' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var['preview_url'];              
            },
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{clickid}' => '{click_id}',
                    '{gaid}'        => '{google_aid}',
                    '{idfa}'        => '{ios_idfa}',
                    '{subid}'   => '{aff_id}',
                ];

                return str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'tracklink' ]);
                
            },
            'description'     => function ($var) {
                return $var['description'];
            },
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'platform' ] == 'ios' || $var['platform'] == 'iphone') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'platform' ] == 'android') {
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
                
                $geo = explode(',', $var[ 'countries' ]);
                
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
                //dd($target);
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
            'name' => function ($var) { return 'CPI';},
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'icon' ]) ?
            $item[ 'icon' ] : null;
        if (!empty($icon_url)) {
            $icon_url = str_replace('https', 'http', $icon_url);
            if (getUrlCode($icon_url) == 200) {
                $file_name = basename($icon_url);
                //$file_name = md5($item[ 'icon' ]) . "." . imageTypeByUrl($item[ 'icon_url' ]);
                $file_url = $icon_url;
                $creative[ 'thumbfile' ][] = [
                    'name'       => $file_name,
                    'url'        => $file_url,
                    'local_path' => CreativeStorage::save($file_name, $file_url),
                ];
            }
        }
        if (isset($item[ 'creative' ]) && !empty($item[ 'creative' ])) {
            $creatives = $item[ 'creative' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    //dd($fitem);
                    $fitem[ 'url' ] = str_replace('https', 'http', $fitem[ 'url' ]);
                    if (getUrlCode($fitem[ 'url' ]) != 200) {
                        continue;
                    }
                    $file_name = basename($fitem[ 'url' ]);
                    //$file_name = md5($item[ 'icon' ]) . "." . imageTypeByUrl($item[ 'icon_url' ]);
                    $file_url = $fitem[ 'url'];
                    $creative[ 'image' ][] = [
                        'name'       => $file_name,
                        'url'        => $file_url,
                        'local_path' => CreativeStorage::save($file_name, $file_url),
                    ];
                }

            } elseif (is_string($creatives)) {
                $file_name = basename($creatives);
                //$file_name = md5($item[ 'icon' ]) . "." . imageTypeByUrl($item[ 'icon_url' ]);
                $file_url = $creatives;
                $creative[ 'image' ][] = [
                    'name'       => $file_name,
                    'url'        => $file_url,
                    'local_path' => CreativeStorage::save($file_name, $file_url),
                ];
            }
        }
        return $creative;
    }
];