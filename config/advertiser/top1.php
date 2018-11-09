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
    'offer_api'           => 'http://api.top1mobiapi.com/api/v2?key=PiE0HJPXJyA=',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
   
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var[ 'offerName' ])) {
                return false;
            }

            return true;
        },
        'country'  => function ($var) {
            if ($var['subOffers'][0][ 'country' ] == 'All') {
                return false;
            }

            return true;
        },
        'payout'  => function ($var) {
            if ($var['subOffers'][0]['payout'] == 0) {
                return false;
            }

            return true;
        }

    ],
    'conversion_field'    => [
        'offer'          => [
        'original_offer_id'=>'offerID',
            'name'            => function ($var) {

                $name = fmtOfferName($var[ 'offerName' ] . " " .$var['platform']. " [" .$var['subOffers'][0]['country']. "]");

                return isset($var[ 'offerName' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 114;
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
                $payout = isset($var['subOffers'][0]['payout']) ? (float)$var['subOffers'][0]['payout'] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var['subOffers'][0]['payout']) ? (float)$var['subOffers'][0]['payout'] : 0;

                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return isset($var['previewLink']) ? $var['previewLink'] : null;
             
            },
            'destination_url' => function ($var) {

                return $var[ 'trackingLink' ].'?cid={click_id}&ac={aff_id}';
            },
            'description'     => 'description',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $os = $var['platform'];
                $platform = [];
                if (strpos($os, 'IOS') !== false) {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if (strpos($os, 'Android') !== false) {
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
                $countries_cache = Cache::store('file')->get('countries', []);
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
      			
      			//The list of countries is an array
                $geo = $var['subOffers'][0][ 'country' ];

                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ $item ][ 'name' ])) {
                            $target[] = [
                                "type"    => 2,
                                "country" => $countries[ $item ][ 'name' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 2,
                        "country" => $countries[ $var['subOffers'][0][ 'country' ] ][ 'name' ],
                        "city"    => [],
                    ];
                }
                //dd($target);
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
        /*'offer_category'  =>[      
            //"name" => "API"
            'tag' => function ($var) { return 'API';},
        ]*/
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'icon' ]) ? $item[ 'icon' ] : null;
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
        if (isset($item[ 'creative' ]) && !empty($item[ 'creative' ])) {
            $creatives = $item[ 'creative' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    //dd($fitem);
                    $fitem[ 'url' ] = str_replace('https', 'http', $fitem[ 'url' ]);
                    if (getUrlCode($fitem[ 'url' ]) != 200) {
                        continue;
                    }
                    $file_name = basename($fitem[ 'url' ]);
                    //$file_name = md5($item[ 'icon' ]) . "." . imageTypeByUrl($item[ 'icon_url' ]);
                    $file_url = $fitem[ 'url'];
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
        //dd($creative);
        return $creative;
    }
];