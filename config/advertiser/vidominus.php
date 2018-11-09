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
        'conversion_value_creative' => false,       //数据转化格式后结果 与 素材采集结果
    ],
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'http://api.vidoadsminus.com?request_uri=/offer/Getoffer/getOffer&app_id=127&app_key=df48ebbef6540c1d607a3dad85053df0',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'advertiser_id'      => 339,
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'price' ] < 0? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'name' ])) {
                return false;
            }
            return true;
        },

        'id'  => function ($var) {
            $arr = [2383935];
            if (!in_array($var['id'], $arr)) {
                return false;
            }
            return true;
        }
    ],
    'conversion_field'    => [
        'advertiser_offer_id' => function ($var) {
            return $var['id'];
        },
        'offer'          => [
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'name' ] . " " . $var[ 'platform' ] . " [" . $var[ 'countries' ][0] . "]");

                return isset($var[ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                //return 163;
                return 339;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return strtotime("now") + 365 * 86400;
            },
            'status'          => function ($var) { return "active"; },
            'offer_approval'  => function ($var) { return 1; },
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
                    '{aff_sub}' => '{click_id}',
                    '{gaid}'        => '{google_aid}',
                    '{channel}'   => '{aff_id}_{source_id}',
                    '{idfa}'    =>  '{ios_idfa}',
                    '{android}'    =>  '{android_id}',
                ];
               return $var[ 'click_url' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'click_url' ]);
            },
            'description'     => 'kpi',
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
                $countries_cache = Cache::store('file')->get('country', []);
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
                $geo = $var[ 'countries' ];
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ $item ][ 'country' ])) {
                            $target[] = [
                                "type"    => 1,
                                "country" => $countries[ $item ][ 'country' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 1,
                        "country" => $countries[ $var[ 'geo' ] ][ 'name' ],
                        "city"    => [],
                    ];
                }


                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) { return $var['daily_cap']; },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[
            //"name" => "API"
            'name' => function ($var) { return 'CPI';},
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'icon' ]) ? $item[ 'icon' ] : null;
        if (!empty($icon_url)) {
            $icon_url = str_replace('https', 'http', $icon_url);
            if (getUrlCode($icon_url) == 200) {
                //$file_name = basename($icon_url);
                $file_name = md5($icon_url) . "." . imageTypeByUrl($icon_url);
                $file_url = $icon_url;
                $creative[ 'thumbfile' ][] = [
                    'name'       => $file_name,
                    'url'        => $file_url,
                    'local_path' => CreativeStorage::save($file_name, $file_url),
                ];
            }
        }
        if (isset($item[ 'images' ]) && !empty($item[ 'images' ])) {
            $creatives = explode(',', $item[ 'images' ]);
           // return $creatives;
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem= str_replace('https', 'http', $fitem);
                    if (getUrlCode($fitem) != 200) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem) . "." . imageTypeByUrl($fitem);
                    $file_url = $fitem[ 0 ];
                    $creative[ 'image' ][] = [
                        'name'       => $file_name,
                        'url'        => $file_url,
                        'local_path' => CreativeStorage::save($file_name, $file_url),
                    ];
                }

            } elseif (is_string($creatives)) {
                //$file_name = basename($creatives);
                $file_name = md5($creatives) . "." . imageTypeByUrl($creatives);
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