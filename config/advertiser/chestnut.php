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
    'offer_api'           => 'http://api.ichestnut.net/v1/apps/get?code=b439ae996acd07ba9517a1cc7e374e9d',
    //'set_cookie'     =>    '',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'advertiser_id'  => '375',
    'geo_api'             => '',
    'offer_list'          => function ($data) {   //接入广告主数据list
        return isset($data[ 'apps' ]) ? $data[ 'apps' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [    //数据过滤，return false 则跳过当前offer 可自定义过滤条件，如下所示
        'revenue' => function ($var) {
            return (float)$var[ 'offers' ][0]['payout'] < 0 ? false : true;
        },
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id' => function ($var) {
                $var[ 'offers' ][0]['offer_id'];
            },
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var['offers'][0]['offer_name'] . " " . $var[ 'offers' ][0]['platforms'][0] . " [" . $var[ 'offers' ][0]['countries'][0] . "]");

                return isset($var['offers'][0]['offer_name']) ? $name : null;
            },
            'advertiser_id'   => function ($var) {  //广告主id 与平台上id一致
                return 375;
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
                $payout = isset($var[ 'offers' ][0]['payout']) ? (float)$var[ 'offers' ][0]['payout'] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'offers' ][0]['payout']) ? (float)$var[ 'offers' ][0]['payout'] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {  //若直接提供preview_url 则直接return。否则就拼接包名。
                return $var['offers'][0]['preview_link'];
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
                return $var['offers'][0]['tracking_link'] . "&click={click_id}&aff_sub={aff_id}_{source_id}";
            },
            'description'     => function ($var) {
                return isset($var['offers'][0]['description']) ? $var['offers'][0]['description'] : '';
            },    //offer KPI
            'currency'        => function () {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {    //匹配offer platform
                $platform = [];
                if ($var['offers'][0]['platforms'][0] == 'IOS') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var['offers'][0]['platforms'][0] == 'ANDROID') {
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
                $geo = $var['offers'][0]['countries'];  //get 广告主国家
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

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) {
                return $var['offers'][0]['cap_daily'];
                },
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
        $icon_url = isset($item[ 'icons' ]) ? $item[ 'icons' ] : null;   //offer icon  处理JPG,png 没问题， webp格式自己想办法。

        if (!empty($icon_url)) {
            foreach ($icon_url as $item) {
                $item['url'] = str_replace('https', 'http', $item['url']);
                if (exif_imagetype($item['url']) == false) {
                    continue;
                }
                if (getUrlCode($item['url']) == 200) {
                    //$file_name = basename($icon_url);
                    $file_name = md5($item['url']) . "." . exif_imagetype($item['url']);
                    $file_url = $item['url'];
                    $creative[ 'thumbfile' ][] = [
                        'name'       => $file_name,
                        'url'        => $file_url,
                        'local_path' => CreativeStorage::save($file_name, $file_url),
                    ];
                    break ;
                }
            }
        }
        if (isset($item[ 'creatives' ]) && !empty($item[ 'creatives' ])) {  //offer creative
            $creatives = $item[ 'creatives' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem['url'] = str_replace('https', 'http', $fitem['url']);
                    if (exif_imagetype($item['url']) == false) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem['url']) . "." . exif_imagetype($fitem['url']);
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