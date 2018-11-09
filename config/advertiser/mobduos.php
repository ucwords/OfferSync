<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'offersLook',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况 打印advertiser offer list
        'offer_list'                => false,       //offer data list
        'offers_item'               => false,       //single offer debug
        'conversion_value_creative' => false,       //数据转化格式后结果 与 素材采集结果  打印对接好的最终结果
    ],
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'http://alw.vnoke.com/ad?access_key=c41e759fa19f363c94610f367ea2f53b&appid=120056&format=json',
    //'set_cookie'     =>    '',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'advertiser_id'  => '380',
    'geo_api'             => '',
    'offer_list'          => function ($data) {   //接入广告主数据list
        return isset($data['data'][ 'campaigns' ]) ? $data['data'][ 'campaigns' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [    //数据过滤，return false 则跳过当前offer 可自定义过滤条件，如下所示
        'revenue' => function ($var) {
            return (float)str_replace('$', '', $var['payout']) < 0 ? false : true;
        },
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id' => function ($var) {
                return $var['campaign_id'];
            },
            'name'            => function ($var) {
                //AE|SA
                //$name = fmtOfferName($var[ 'title' ] . " [" . $var[ 'allow_country' ] . "]");

                return isset($var[ 'title' ]) ? $var[ 'title' ] : null;
            },
            'advertiser_id'   => function ($var) {  //广告主id 与平台上id一致
                return 380;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return strtotime("now") + 365 * 86400;
            },
            'status'          => function ($var) { return "active"; },
            'offer_approval'  => function ($var) { return 1; },     //一般上S2S设为1, API设置2
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payouts = str_replace('$', '', $var['payout']);
                $payout = isset($payouts) ? (float)$payouts : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payouts = str_replace('$', '', $var['payout']);
                $payout = isset($payouts) ? (float)$payouts : 0;

                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {  //若直接提供preview_url 则直接return。否则就拼接包名。
                return $var['preview_url'];
            },
            'destination_url' => function ($var) { //具体参数对接见广告主侧文档
                /* 方式一，替换广告主参数
                 * $fill_vars = [
                    '{tp_placementid}' => '{click_id}',
                    '{tp_aaid}'        => '{google_aid}',
                    '{tp_sub_affid}'   => '{source_id}',
                ];
                if ($var[ 'opsystem' ] == 'ios') {
                    $fill_vars[ '{tp_aaid}' ] = '{ios_idfa}';
                }

                $var[ 'tracking_link' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'tracking_link' ]);
                */
                //方式二，自己拼接
                return $var[ 'trackurl' ] . "&ext1={click_id}&ext2={aff_id}_{source_id}&gaid={google_aid}&idfa={ios_idfa}";
            },
            'description'     => 'appdesc',    //offer KPI
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {    //匹配offer platform
                $platform = [];
                if ($var[ 'allow_device' ] == 'ios') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'allow_device' ] == 'android') {
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
            'target' => function ($var) { //匹配offer geo
                $countries = [];
                $countries_cache = Cache::store('file')->get('country', []);  //此缓存直接使用就好, 若offerslook有更改，清除缓存，自己搞一份，数据来源API文档
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
                $geo = explode(',', $var[ 'allow_country' ]);  //get 广告主国家
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
                        "country" => $countries[ $var[ 'geo' ] ][ 'country' ],
                        "city"    => [],
                    ];
                }


                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 0; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) { return $var['cap']; },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[     //offer tag  多个以','隔开
            'name' => function ($var) { return 'CPI';},
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'icon_url' ]) ? $item[ 'icon_url' ] : null;   //offer icon  处理JPG,png 没问题， webp格式自己想办法。
        if (!empty($icon_url)) {
            $icon_url = str_replace('https', 'http', $icon_url);
            //if (getUrlCode($icon_url) == 200) {
                //$file_name = basename($icon_url);
                $file_name = md5($icon_url) . "." . imageTypeByUrl($icon_url);
                $file_url = $icon_url;
                $creative[ 'thumbfile' ][] = [
                    'name'       => $file_name,
                    'url'        => $file_url,
                    'local_path' => CreativeStorage::save($file_name, $file_url),
                ];
            //}
        }
        if (isset($item[ 'images' ]) && !empty($item[ 'images' ])) {  //offer creative
            $creatives = $item[ 'images' ];

            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem = str_replace('https', 'http', $fitem);
                    /*if (getUrlCode($fitem[ 0 ]) != 200) {
                        continue;
                    }*/
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem);// . "." . imageTypeByUrl($fitem[ 0 ]);
                    $file_url = $fitem;
                    $creative[ 'image' ][] = [
                        'name'       => $file_name,
                        'url'        => $file_url,
                        'local_path' => CreativeStorage::save($file_name, $file_url),
                    ];

                }
                //dd($creative);

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
        //dd($creative,111);
        return $creative;
    }
];