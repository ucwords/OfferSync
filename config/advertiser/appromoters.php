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
    'offer_api'           => 'https://api.appromoters.com/v2/active_offers?publisher_id=1413&publisher_token=HN7iZjWpeBdB3HuRGKT1yQ&creatives=true',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'advertiser_id'  =>   '349',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'activeoffers' ]) ? $data[ 'activeoffers' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'payout' ] < 0 ? false : true;
        },
        'offer_id'    => function ($var) {
            $arr = [1450372,1462990];
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
            'name'            => function ($var) {
                //AE|SA
                //$name = fmtOfferName($var[ 'name' ] . " " . $var[ 'os' ] . " [" . $var[ 'countries' ] . "]");

                return isset($var[ 'offer_name' ]) ? fmtOfferName($var[ 'offer_name' ]) : null;
            },
            'advertiser_id'   => function ($var) {
                return 349;
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
                if ($var[ 'app_os' ] == 'android') {
                    return 'https://play.google.com/store/apps/details?id=' . $var[ 'packagename' ];
                }
                if ($var[ 'app_os' ] == 'ios') {
                    return 'https://itunes.apple.com/us/app/id' . $var[ 'packagename' ];
                }
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
                return $var[ 'click_url' ] . "&publisher_data1={click_id}&placement_id={aff_id}_{source_id}";
            },
            'description'     => function ($var) {
                return json_encode($var['kpis']);
            },
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'app_os' ] == 'ios') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'app_os' ] == 'android') {
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
                $geo = $var[ 'country' ];
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ strtoupper($item)][ 'country' ])) {
                            $target[] = [
                                "type"    => 1,
                                "country" => $countries[ strtoupper($item) ][ 'country' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 1,
                        "country" => $countries[ $var[ 'geo' ] ][ 'country' ],
                        "city"    => [],
                    ];
                }


                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) { return $var['conversion_cap'];},
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
        $icon_url = isset($item[ 'app_icon' ]) ? $item[ 'app_icon' ] : null;
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
        if (isset($item[ 'creatives1' ]) && !empty($item[ 'creatives1' ])) {
            $creatives = $item[ 'creatives' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem[ 'url' ] = str_replace('https', 'http', $fitem['url']);
                    if (getUrlCode($fitem['url']) != 200) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem[ 'url']) . "." . imageTypeByUrl($fitem['url']);
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