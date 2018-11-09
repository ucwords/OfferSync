<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;
use App\Models\GetCreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'offersLook',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况 打印advertiser offer list
        'offer_list'                => false,       //offer data list
        'offers_item'               => false,       //single offer debug
        'conversion_value_creative' => true,       //数据转化格式后结果 与 素材采集结果  打印对接好的最终结果
    ],
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'http://api.appleadstech.com/v3/api_v3?key=cd5da354f6c54128a8005d83c82b0a2f',
    //'set_cookie'     =>    '',
    //'offer_api_post_json' => '',
   // 'set_header' => '',
    'creative_api'        => '',
    'advertiser_id'  => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {   //接入广告主数据list
        return isset($data[ 'data' ]) ? $data[ 'data' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [    //数据过滤，return false 则跳过当前offer 可自定义过滤条件，如下所示
        'revenue' => function ($var) {
            return (float)$var[ 'payout' ] < 0 ? false : true;
        },
        /*'offer_id'    => function ($var) {
            $arr = [156409, 155558, 154716];
            if (!in_array($var['id'], $arr)) {
                return false;
            }
            return true;
        }*/
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id' => function ($var) {
                return $var['id'];
            },
            'name'            => function ($var) {
                //AE|SA
               // $name = fmtOfferName($var[ 'name' ] . " " . $var[ 'os' ] . " [" . $var[ 'countries' ] . "]");

                return isset($var[ 'name' ]) ? $var[ 'name' ] : null;
            },
            'advertiser_id'   => function ($var) {  //广告主id 与平台上id一致
                return 463;
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
                $payout = isset($var[ 'payout' ]) ? (float)$var[ 'payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'payout' ]) ? (float)$var[ 'payout' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {  //若直接提供preview_url 则直接return。否则就拼接包名。
                return $var['preview_link'];
            },
            'destination_url' => function ($var) { //具体参数对接见广告主侧文档

                return $var[ 'click_url' ] . "&aff_sub1={click_id}&source_id={aff_id}_{source_id}&google_aid={google_aid}&ios_idfa={ios_idfa}";
            },
            'description'     => 'description',    //offer KPI
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {    //匹配offer platform
                $platform = [];
                if ($var[ 'os' ] == 'ios') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'os' ] == 'android') {
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
                $geo = explode(',', $var[ 'countries' ]);  //get 广告主国家
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
        //$icon_url = GetCreativeStorage::dealCreative($item['os'], $item['package']);

        $creative = [];
        //$icon_url = isset($item[ 'icon' ]) ? $item[ 'icon' ] : null;   //offer icon  处理JPG,png 没问题， webp格式自己想办法。
        if ($item['os'] == 'Ios') {
            $platformat = 'ios';
        } else {
            $platformat = 'android';
        }
        $data = GetCreativeStorage::dealCreative('ios', $item['package']);
        if (!empty($data['icon'])) {
            $icon_url = GetCreativeStorage::dealCreative($item['os'], $item['preview_link']);

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
        if (isset($data[ 'screenshot' ]) && !empty($data[ 'screenshot' ])) {  //offer creative
            $creatives = $data[ 'screenshot' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                   // $fitem['url'] = str_replace('https', 'http', $fitem['url']);
                    if (getUrlCode($fitem) != 200) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem) . "." . imageTypeByUrl($fitem);
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