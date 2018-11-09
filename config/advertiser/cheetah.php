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
    'offer_api'           => 'http://api-pub.peg.cmcm.com/feed?key=8vpmhkrzoipx3hs5pgkiyze38zhod8gx',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'advertiser_id'  =>   '191',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'data' ][ 'campaigns' ]) ? $data[ 'data' ][ 'campaigns' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var['adSets'][0][ 'cappings' ][0][ 'pay_out' ] < 0 ? false : true;
        },
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id'    => function ($var) {

            },
            'name'            => function ($var) {
                //AE|SA
                if ($var['adSets'][0]['platform'] == 1) {
                    $os = 'ios';
                } elseif ($var['adSets'][0]['platform'] == 2) {
                    $os = 'android ';
                 }
                 //dd($var['adSets'][0][ 'cappings' ]);
                $name = fmtOfferName($var[ 'name' ] . " " . $os . " [" . $var['adSets'][0][ 'cappings' ][0]['country'] . "]");

                return isset($var[ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 191;
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
                $payout = isset($var['adSets'][0][ 'cappings' ][0][ 'pay_out' ]) ? (float)$var['adSets'][0][ 'cappings' ][0][ 'pay_out' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var['adSets'][0][ 'cappings' ][0][ 'pay_out' ]) ? (float)$var['adSets'][0][ 'cappings' ][0][ 'pay_out' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var['preview_url'];
                /*if ($var[ 'os' ] == 'android') {
                    return 'https://play.google.com/store/apps/details?id=' . $var[ 'pkgname' ];
                }
                if ($var[ 'os' ] == 'ios') {
                    return 'https://itunes.apple.com/us/app/id' . $var[ 'pkgname' ];
                }*/
            },
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{transaction_id}' => '{click_id}',
                    '{advertising_id}' => '{google_aid}',
                    '{android_id}'     => '{android_id}',
                    '{pub_id}'     => '{offer_id}',
                    '{sub_id}'     => '{aff_id}_{source_id}',
                ];
                if ($var['adSets'][0]['platform'] == 1) {
                    $fill_vars[ '{advertising_id}' ] = '{ios_idfa}';
                }

                return $var['adSets'][0]['creatives'][0][ 'click_url' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var['adSets'][0]['creatives'][0][ 'click_url' ]);

                //return $var[ 'clkurl' ] . "&dv1={click_id}&nw_sub_aff={source_id}";
            },
            'description'     => 'description',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var['adSets'][0]['platform'] == 1) {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var['adSets'][0]['platform'] == 2) {
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
                $geo = $var['adSets'][0][ 'cappings' ][0]['country'];
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ $item ][ 'country' ])) {
                            $target[] = [
                                "type"    => 2,
                                "country" => $countries[ $item ][ 'country' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 2,
                        "country" => $countries[ $geo][ 'country' ],
                        "city"    => [],
                    ];
                }


                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) { return $var['adSets'][0]['cappings'][0]['daily_cap']; },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ],
        /*'offer_category'  =>[      
            //"name" => "API"
            'name' => function ($var) { return 'API';},
        ]*/
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item['adSets'][0]['creatives'][0][ 'icon' ]) ?
            $item['adSets'][0]['creatives'][0][ 'icon' ] : null;
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
        if (isset($item['adSets'][0]['creatives'][0][ 'images' ]) && !empty($item['adSets'][0]['creatives'][0][ 'images' ])) {
            $creatives = $item['adSets'][0]['creatives'][0][ 'images' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem['url'] = str_replace('https', 'http', $fitem['url']);
                    if (getUrlCode($fitem['url']) != 200) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem['url']) . "." . imageTypeByUrl($fitem['url']);
                    $file_url = $fitem['url'];
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