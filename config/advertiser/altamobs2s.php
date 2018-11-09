<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'offersLook',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => true,       //三方api获取情况
        'offer_list'                => false,       //offer data list
        'offers_item'               => false,       //single offer debug
        'conversion_value_creative' => false,       //数据转化格式后结果 与 素材采集结果
    ],
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'http://api.altamob.com/adfetch/v2/s2s/campaign/fetch',
    'set_header'   =>  ["token: 8a8c5edd-d631-4fa2-b534-a21a830ca108"],
    //'offer_api_post_json' => '',
    'advertiser_id' => 165,
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'data' ]) ? $data[ 'data' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'appName' ])) {
                return false;
            }
            return true;
        },
        'offer_id'  => function ($var) {
            switch ($var['offerId']) {
                case 150672223:
                    return true;
                    break;
                case 150591815:
                    return true;
                    break;
                case 148723450:
                    return true;
                    break;
                case 150591794:
                    return true;
                    break;
                case 148638634:
                    return true;
                    break;
                default:
                    return false;
                    break;
            }
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id' => 'offerId',
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'appName' ] . " " . $var[ 'platforms' ][0] . " [" . $var[ 'supportedCountries' ][0]['country'] . "]");

                return isset($var[ 'appName' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 165;
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
            	return $var['previewLink'];
            },
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{transaction_id}' => '{click_id}',
                    '{gaid}'        => '{google_aid}',
                    '{p}'   => '{aff_id}_{source_id}',
                    '{geo}'  => '{country_id}',
                    '{aid}'  => '{android_id}',
                ];
                $var[ 'trackingLink' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'trackingLink' ]);
                return $var[ 'trackingLink' ];
            },
           // 'description'     => 'campaigndesc',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
           
                if ($var[ 'platforms' ][0] == 'ios') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [$var['minOsVersion']],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'platforms' ][0] == 'android') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'Android',
                        'version'  => [$var['minOsVersion']],
                        'is_above' => "0",
                    ];
                }

                return $platform;
            },
        ],
        'offer_geo'      => [
            'target' => function ($var) {
                /*$countries = [];
                $countries_cache = Cache::store('file')->get('countries', []);
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
                foreach ($var['supportedCountries'] as $item) {
                	$geo[] = array_values($item);
                }
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ $item[0] ][ 'name' ])) {
                            $target[] = [
                                "type"    => 2,
                                "country" => $countries[ $item[0] ][ 'name' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {*/
                    $target[] = [
                        "type"    => 2,
                        "country" => $var[ 'supportedCountries' ][0]['country'],
                        "city"    => [],
                    ];
                //}


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
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'appIconUrl' ]) ?
            $item[ 'appIconUrl' ] : null;
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
        if (isset($item[ 'cover_url' ]) && !empty($item[ 'cover_url' ])) {
            $creatives = $item[ 'cover_url' ];
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