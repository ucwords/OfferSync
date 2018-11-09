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
    'offer_api'           => 'http://api.3point14.affise.com/3.0/partner/offers?API-Key=d3afb0cd4b3c9fa5747c120498769de7b5139027',
    //'offer_api_post_json' => '',
    'advertiser_id' => 206,
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'offer_id'    => function ($var) {
            $arr = [749612,749377,722933,707281,895609,882504,814307,777696,749536,705457,668134,814386,740720,358197,396335,687783,739396,723802,740829,749376,734567,745074,749355,749357,749341,745141,749349,749353,749359,749361,749360,749365,749481,749476,749383,749376,749381,749467,749377,347231,749365,316530,652771,358197,396335,347231,133114,133114,668134,316530,602857,625891,667377,749361,668136,668139,749536,668140,668145,74950,745141,687783,705457,722933,707281,745074,723802,749341,749349,749353,749360,749355,749357,749359,749505,749503,749502,749481,749476,749467,749463,749457,749384,749383,749381] ;
            if (!in_array($var['id'], $arr)) {
                return false;
            }
            return true;
        },
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id'    => function ($var) {
                return $var['id'];
            },
            'original_offer_id'  => function ($var) {

                return $var['id'];
            },
            'name'            => function ($var) {
                if (strpos($var['preview_url'], 'google') != false) {
                    $platform = "Android";
                } elseif (strpos($var['preview_url'], 'itunes') != false) {
                    $platform = "iOS";
                }

                $name = fmtOfferName($var[ 'title' ] . " " . $platform . " [" . $var[ 'countries' ][0] . "]");

                return isset($var[ 'title' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 206;
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
                //dd($var);
                //return $var[ 'payments' ][0]['revenue'];
                $payout = isset($var[ 'payments' ][0]['revenue']) ? (float)$var[ 'payments' ][0]['revenue'] : 0;
                //dd($payout);
                return round($payout, 2);
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payments' ][0]['revenue']) ? (float)$var[ 'payments' ][0]['revenue'] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {

                if ($var['preview_url']) {
                    return $var['preview_url'];
                }
                if (strpos($var['preview_url'], 'google') != false) {
                    return 'https://play.google.com/store/apps/details?id=' . $var[ 'pkgname' ];
                } elseif (strpos($var['preview_url'], 'itunes')) {
                    return 'https://itunes.apple.com/us/app/id' . $var[ 'pkgname' ];
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
                return $var[ 'link' ] . "&sub1={click_id}&sub2={aff_id}_{source_id}&sub3={google_aid}";
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

                if (strpos($var['preview_url'], 'google') != false) {
                    $os = "Android";
                } elseif (strpos($var['preview_url'], 'itunes') != false) {
                    $os = "iOS";
                }

                $platform = [];
                if ($os == 'iOS') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($os== 'Android') {
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
                //dd($geo);
                //dd($countries[ $geo ][ 'code' ]);
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ strtoupper($item) ][ 'country' ])) {
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
            'adv_cap_conversion' => function ($var) {
                if (!empty($var['caps'][0]['value'])) {
                    return $var['caps'][0]['value'];
                } else {
                    return  100;
                }

            },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[
            //"name" => "CPI"
            'name' => function ($var) { return 'CPI';},
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'logo' ]) ?
            $item[ 'logo' ] : null;
        if (!empty($icon_url)) {
            $icon_url = str_replace('https', 'http', $icon_url);
            if (getUrlCode($icon_url) == 200) {
                //$file_name = basename($icon_url);
                $file_name = md5($icon_url) . "." . exif_imagetype($icon_url);
                $file_url = $icon_url;
                $creative[ 'thumbfile' ][] = [
                    'name'       => $file_name,
                    'url'        => $file_url,
                    'local_path' => CreativeStorage::save($file_name, $file_url),
                ];
            }
        }
        //dd($creative);
        if (isset($item[ 'creatives1' ]) && !empty($item[ 'creatives1' ])) {
            $creatives = $item[ 'creatives' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem[ 'file_name' ] = str_replace('https', 'http', $fitem[ 'file_name' ]);
                    if (getUrlCode($fitem[ 'file_name']) != 200) {
                        continue;
                    }

                    //$file_name = basename($fitem['file_name']);
                    $file_name = md5($fitem[ 'file_name']) . "." . exif_imagetype($fitem['file_name']);
                    $file_url = $fitem['file_name'];

                    $creative[ 'image' ][] = [
                        'name'       => $file_name,
                        'url'        => $file_url,
                        'local_path' => CreativeStorage::save($file_name, $file_url),
                    ];
                }

            } elseif (is_string($creatives)) {
                //$file_name = basename($creatives);
                $file_name = md5($creatives) . "." . exif_imagetype($creatives);
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