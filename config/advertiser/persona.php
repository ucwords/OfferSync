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
    //'http_basic_auth'  => ['Authorization', '0a80109f94634757a8c230fab6718183'],
    'offer_api'           => 'http://dsp.persona.ly/api/campaigns?token=59bd80988a851339499a6c6ac5ee0dcc',
    //'offer_api_post_json' => '',
    //'set_header'   =>  ["authorization: 0a80109f94634757a8c230fab6718183"],
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'campaigns' ]) ? $data[ 'campaigns' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'payouts' ][0]['usd_payout'] < 0 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'campaign_name' ])) {
                return false;
            }
            return true;
        },
    ],
    'conversion_field'    => [
        'offer'          => [
            'original_offer_id'=>'id',
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'campaign_name' ] . " " . $var[ 'payouts' ][0]['platform'] . " [" . $var[ 'payouts' ][0]['countries'][0] . "]");
                return isset($var[ 'campaign_name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 319;
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
                $payout = isset($var[ 'payouts' ][0]['usd_payout']) ? (float)$var[ 'payouts' ][0]['usd_payout'] : 0;
  
                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payouts' ][0]['usd_payout']) ? (float)$var[ 'payouts' ][0]['usd_payout'] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                if (!empty($var['preview_url_android'])) {
                    return $var['preview_url_android'];
                }
                if (!empty($var['preview_url_ios'])) {
                    return $var['preview_url_ios'];
                }
                
            },
            'destination_url' => function ($var) {

                /*$fill_vars = [
                    '{CLICK_ID}' => '{click_id}',
                   
                    '{SOURCE}'   => '{aff_id}_{source_id}',
                ];
                return str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'trackingLink' ]);*/
                
                return $var[ 'tracking_url' ] . "&clickid={click_id}&subid1={aff_id}_{source_id}&subid2={aff_id}_{source_id}";
            },
            'description'     => function ($var) {
                return ' ';
            },
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'payouts' ][0]['platform'] != 'Android') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'payouts' ][0]['platform'] == 'Android') {
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
                $geo = strtoupper($var[ 'payouts' ][0]['countries'][0]);
                //dd($geo);
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
                        "country" => $countries[ $geo ][ 'country' ],
                        "city"    => [],
                    ];
                }

                //dd($target);
                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) {

                return $var['subscription_caps']['total_cap_limit'];
            },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { return 100; },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[      
            'name' => function ($var) { return 'CPI';},
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'campaign_icon_url' ]) ?
            $item[ 'campaign_icon_url' ] : null;
        
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
        if (isset($item[ 'creatives' ]) && !empty($item[ 'creatives' ])) {
            $creatives = $item[ 'creatives' ];
            if (is_array($creatives)) {
                foreach ($creatives[0] as $key => $fitem) {
                    $fitem['creative_url'] = str_replace('https', 'http', $fitem['creative_url']);
                    if (getUrlCode($fitem['creative_url']) != 200) {
                        continue;
                    }
                    $file_name = basename($fitem['creative_url']);
                    //$file_name = md5($item[ 'icon' ]) . "." . imageTypeByUrl($item[ 'icon_url' ]);
                    $file_url = $fitem['creative_url'];
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