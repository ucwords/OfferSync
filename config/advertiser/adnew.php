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
    'http_basic_auth'  => ['fanny@leanmobi.com', '1b4ed75059144434a075b43cef67ed13'],
    'offer_api'           => 'http://adnew.api.offerslook.com/aff/v1/batches/offers?type=personal&sort=-id&filters[status]=active&contains=description&offset=1&limit=100',
    //'offer_api_post_json' => '',
    'advertiser_id'  => 484,
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'data' ]['rowset']) ? $data[ 'data' ]['rowset'] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'offer_id'    => function ($var) {
            $arr = [1780];
            if (!in_array($var['offer']['id'], $arr)) {
                return false;
            }
            return true;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            'carrier' => function ($var) {

                if (isset($var['offer']['carrier']) && !empty($var['offer']['carrier'])) {
                    $carrier = $var['offer']['carrier'];
                } else {
                    $carrier = [ ];
                }
                return $carrier;
            },
            'advertiser_offer_id'   => function ($var) {
                return $var['offer']['id'];
            },

            'name'            => function ($var) {

                return isset($var['offer'][ 'name' ]) ? $var['offer'][ 'name' ] : null;
            },
            'advertiser_id'   => function ($var) {
                return 484;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return $var['offer']['end_date'];
            },
            'status'          => function ($var) { return "active"; },
            'offer_approval'  => function ($var) { return 1; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var['offer'][ 'payout' ]) ? (float)$var['offer'][ 'payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var['offer'][ 'payout' ]) ? (float)$var['offer'][ 'payout' ] : 0;

                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var['offer']['preview_url'];
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
                return $var['offer'][ 'tracking_link' ] . "&aff_sub1={click_id}&source_id={aff_id}_{source_id}";
            },
            'description'     => function ($var) {
                if (!empty($var['offer']['description'])) {
                    return $var['offer']['description'];
                }
                return ' ';
                
            },
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if (!empty($var['offer_platform']['target'])) {
                    if (is_array($var['offer_platform']['target'])) {
                        foreach ($var['offer_platform']['target'] as $item) {
                             if ($item['system'] == 'iOS') {
                                $platform[] = [
                                    'platform' => "Mobile",
                                    'system'   => 'iOS',
                                    'version'  => [],
                                    'is_above' => "0",
                                ];
                            }
                            if ($item['system'] == 'Android') {
                                $platform[] = [
                                    'platform' => "Mobile",
                                    'system'   => 'Android',
                                    'version'  => [],
                                    'is_above' => "0",
                                ];
                            }
                        }
                    }
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
                if (isset($var[ 'offer_geo' ]['target']) && !empty($var[ 'offer_geo' ]['target'][0]['country_code'])) {
                    $geo = $var[ 'offer_geo' ]['target'][0]['country_code'];
                } else {
                    return $target = [];
                }

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
            'adv_cap_conversion' => function ($var) {
                if (!empty($var['offer_cap'])) {
                    if (isset($var['offer_cap']['cap_conversion']) && !empty($var['offer_cap']['cap_conversion'])) {
                        return $var['offer_cap']['cap_conversion'];
                    }
                } else {
                    return 100;
                }

            },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[
            'name' => function ($var) { 
               /* switch ($var['offer']['id']) {
                    case 7136:
                        $name = 'CPA, Adult';
                        break;
                    case 6816:
                        $name = 'CPA, Mainstream';
                        break;
                    default:
                        $name = 'CPA, Mainstream';
                        break;
                }*/
                $name = 'CPA';
                return $name;
            },
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item['offer'][ 'thumbnail' ]) ?
            $item['offer'][ 'thumbnail' ] : null;
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
        if (isset($item[ 'offer_creative' ]) && !empty($item[ 'offer_creative' ])) {
            $creatives = $item[ 'offer_creative' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem['url'] = str_replace('https', 'http', $fitem['url']);
                    if (getUrlCode($fitem['url']) != 200) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem['url']) . "." . imageTypeByUrl($fitem[ 'url' ]);
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