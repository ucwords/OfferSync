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
    'offer_api'           => 'http://api.actwebmedia.affise.com/3.0/offers?limit=1000',
    //'offer_api_post_json' => '',
    'set_header'   =>  ["Api-Key: c6a086067062975ecdf1ff4bb07897d123ec88a7"],
    'creative_api'        => '',
    'advertiser_id'       => 488,
    'geo_api'             => '',
    'offer_list'          => function ($data) {

        return isset($data['offers']) ? $data['offers'] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var['payments'][0]['revenue'] < 0 ? false : true;
        },
        'offer_id'    => function ($var) {
            $arr = [1291, 1289, 1275, 1267, 1265];
            if (!in_array($var['id'], $arr)) {
                return false;
            }
            return true;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            /*'carrier' =>  function ($var) {
                return $var['carrier'];
            },*/

            'name'            => function ($var) {

                $name = fmtOfferName($var[ 'title' ]);

                return isset($var[ 'title' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 488;
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
                $payout = isset($var[ 'payments' ][0]['revenue']) ? (float)$var[ 'payments' ][0]['revenue'] : 0;
    
                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payments' ][0]['revenue']) ? (float)$var[ 'payments' ][0]['revenue'] : 0;

                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {

                return $var['preview_url'];
                
            },
            'destination_url' => function ($var) {

                //&aff_sub={click_id}&aff_sub2={aff_id}_{source_id}&ios_ifa={ios_idfa}&google_aid={google_aid}";

                return $var[ 'link' ] . "&sub1={click_id}&sub2={aff_id}_{source_id}";
            },
            'description'     => 'description',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                return $platform = [];

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
                        if (isset($countries[ strtoupper($item) ][ 'country' ])) {
                            $target[] = [
                                "type"    => 2,
                                "country" => $countries[ strtoupper($item) ][ 'country' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 2,
                        "country" => $countries[ $var[ 'countries' ][0] ][ 'country' ],
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
            //'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[      
            'name' => function ($var) { return 'CPA';},
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'logo' ]) ? $item[ 'logo' ] : null;
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
                foreach ($creatives as $key => $fitem) {
                    $fitem['file_name'] = str_replace('https', 'http', $fitem['file_name']);
                    if (getUrlCode($fitem['file_name']) != 200) {
                        continue;
                    }
                    $file_name = basename($fitem['file_name']);
                    //$file_name = md5($item[ 'icon' ]) . "." . imageTypeByUrl($item[ 'icon_url' ]);
                    $file_url = $fitem['file_name'];
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