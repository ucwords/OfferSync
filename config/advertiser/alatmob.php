<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'offersLook',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况
        'offer_list'                => false,       //offer data list
        'offers_item'               => true,       //single offer debug
        'conversion_value_creative' => true,       //数据转化格式后结果 与 素材采集结果
    ],
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'http://api.metalex.io/index.php?m=server&p=getoffer&sid=105&secret=4f9568903c9ec6b2d81a22c98b760a7a',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'datas' ]) ? $data[ 'datas' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'price' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'app_name' ])) {
                return false;
            }
            return true;
        },
        'country' => function ($var) {
        	if ($var[ 'countries' ][0] == 'ZZ') {
                return false;
            }

            return true;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            //'advertiser_offer_id' => ,
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'app_name' ] . " " . $var[ 'platform' ] . " [" . $var[ 'countries' ][0] . "]");

                return isset($var[ 'app_name' ]) ? $name : null;
            },
            'advertiser_offer_id' => function ($var) {
                return $var['id'];
            },

            'advertiser_id'   => function ($var) {
                return 60;
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
                    '{aff_sub}'  => '{click_id}',
                    '{android}'  => '{google_aid}',
                    '{idfa}'   => '{ios_idfa}',
                    '{channel}' => '{aff_id}_{source_id}',
                ];

                $var[ 'click_url' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'click_url' ]);
                //dd($var[ 'click_url' ] . "&dv1={click_id}");
                return $var[ 'click_url' ];
            },
            'description'     => 'des',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'platform' ] == 'ios') {
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
                $countries = [];
                $countries_cache = Cache::store('file')->get('countries', []);
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
                $geo = $var[ 'countries' ][0];
                
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ $item ][ 'name' ])) {
                            $target[] = [
                                "type"    => 2,
                                "country" => $countries[ $item ][ 'name' ],
                                //'country_code' => ,
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 2,
                        "country" => $countries[ $var[ 'countries' ][0] ][ 'name' ],
                        //"country_code" => ,
                        "city"    => [],
                    ];
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
        $icon_url = isset($item[ 'app_icon' ]) ?
            $item[ 'app_icon' ] : null;
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

        if (isset($item[ 'material' ]) && !empty($item[ 'material' ])) {
            $creatives = $item[ 'material' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    
                    $fitem = str_replace('https', 'http', $fitem);
                    if (getUrlCode($fitem) != 200) {
                        continue;
                    }
                    $file_name = basename($fitem);
                    //$file_name = md5($item[ 'icon' ]) . "." . imageTypeByUrl($item[ 'icon_url' ]);
                    $file_url = $fitem;
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