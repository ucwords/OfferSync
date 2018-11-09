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
    'offer_api'           => 'http://api.mobra.in/v1/campaign/feed?skip=0&limit=500',
    //'offer_api_post_json' => '',
    'set_cookie'     => 'mobrain_api=eyJpdiI6ImJKdGpQc3pubzhicE4wcnRjSllzT3hUUG9Od1pyYUFKUTE5NWtCQkxHQms9IiwidmFsdWUiOiJiVENEMHg4aFNRUkFTelNBZnlZK1I4VmcyQmxobkJmZFhkbUtVbVMzcGdGUzBCU3NSdDNwNEU1cW1RVzUwUjBVdkxIYWFLVUx6eWhldDBDZE40c0xDZz09IiwibWFjIjoiOTFhNGJkMjVhZjZhYzNjYTMwZGQwNDFkOWI3NjkzNTY2MTgyY2EzOWE3NDRhOTkyOWZiNzI0YTQyNGU1ZWFiMyJ9',
    'creative_api'        => '',
    //curl --data 'user=fanny@leanmobi.com&password=123456' -i https://api.mobra.in/v1/auth/login
    'advertiser_id'  => 298,
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'data' ]) ? $data[ 'data' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'payout' ] < 0 ? false : true;
        },

        'offer_id'    => function ($var) {
            $arr = ['nj64x8e', '92inytr', '9vbqrns', 'aqipji5', '1zkgqj3', 'dzdxvar', 'bfmuyzv', '3u0lqyc'];
            if (!in_array($var['offer_id'], $arr)) {
                return false;
            }
            return true;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id' => function ($var) {
                return $var['offer_id'];
            },
            'advertis' => function ($var) {
                return $var['category_group'];
            },
            'carrier' => function ($var) {
                $carrier_origin = strtolower(implode(',' ,$var['carriers']));
                $carrier_origin_arr = explode(',', $carrier_origin);
                foreach ($carrier_origin_arr as  $item) {
                    $carrier[] = str_replace(substr($item, strpos($item, ' ')), '', $item);
                }
                return $carrier;
            },

            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'name' ] . "  " . " [" . $var[ 'countries' ][0] . "]");

                return isset($var[ 'name' ]) ? $name : null;
            },

            'advertiser_id'   => function ($var) {
                return 298;
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
            'preview_url'     => function ($var) {
                return $var['app_id'];
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
                return $var[ 'click_url' ] . "sid={click_id}&p={aff_id}_{source_id}";
            },
            'description'     => 'restrictions',
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
                $countries_cache = Cache::store('file')->get('country', []);
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
                                "type"    => 1,
                                "country" => $countries[ $item ][ 'name' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 1,
                        "country" => $countries[ $geo ][ 'country' ],
                        "city"    => [],
                    ];
                }


                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) { return 100; },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
           // 'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[

            'name' => function ($var) {
                $tag = $var['flow_type'];
                $tag_one = $var['category_group'];
                return $tag.',CPA,'.$tag_one;
            },
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'icon' ]) ?
            $item[ 'icon' ] : null;
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
        if (isset($item[ 'creatives' ]) && !empty($item[ 'creatives' ])) {
            $creatives = $item[ 'creatives' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem[ 0 ] = str_replace('https', 'http', $fitem[ 0 ]);
                    if (getUrlCode($fitem[ 0 ]) != 200) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem[ 0 ]) . "." . imageTypeByUrl($fitem[ 0 ]);
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