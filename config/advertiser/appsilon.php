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
    'offer_api'           => 'http://api4.appsilon.kr/?mode=API&type=ALL&token=$1$dS20HRrQ$x5nLNrKwNFBRQbdAjL09K0',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'advertiser_id'      => '25',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        $result=[];
        if(is_array($data)){
            foreach($data['data'] as $k => $item){
                $offer_id = $item['offerId'];
                $offer_json_data = file_get_contents('http://api4.appsilon.kr/?mode=API&type=DETAIL&token=$1$dS20HRrQ$x5nLNrKwNFBRQbdAjL09K0&offerId='.$offer_id);
                //dd($offer_json_data);
                $offer_data = json_decode($offer_json_data,true);
                //dd(11,$offer_data);
                if($offer_data['returnCode']=='SUCCESS_0000'){
                    //dd(111,$offer_data['data'][0]);
                    $result[]= array_merge($item, $offer_data['data'][0]);
                    dd($result);
                }
            }
            //dd(111, $result);
            return $result;
        }

    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        /*'revenue' => function ($var) {
            //dd($var);
            return (float)$var[ 'offerPayoutMoney' ] < 0 ? false : true;
        },
        'name'    => function ($var) {

            if (empty($var[ 'offerName' ])) {
                return false;
            }

            return true;
        }*/
        'offer_id'    => function ($var) {
            $arr = [ 1933, 1927];
            if (!in_array($var['offerId'], $arr)) {
                return false;
            }
            return true;
        },
    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id '  => function ($var) {
        
                return $var['offerId'];
            }, 
            'name'            => function ($var) {
                if (strpos($var['offerPrivewUrl'], 'google') != false) {
                    $platform = "Android";
                } elseif (strpos($var['offerPrivewUrl'], 'itunes') != false) {
                    $platform = "iOS";
                } 
                $name = fmtOfferName($var[ 'offerName' ] . " " .$platform. " [" . $var[ 'targetCountry' ] . "]");
                
                return isset($var[ 'offerName' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 25;
            },
            /*'start_date'      => function ($var) {
                return $var['offerStartDate'];
                //return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return $var['offerEndDate'];
                //return strtotime("now") + 365 * 86400;
            },*/
            'status'          => function ($var) { return "active"; },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var[ 'offerPayoutMoney' ]) ? (float)$var[ 'offerPayoutMoney' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'offerPayoutMoney' ]) ? (float)$var[ 'offerPayoutMoney' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var[ 'offerPrivewUrl' ];
                
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
               
                return $var[ 'offerTrakingLink' ] . "&dv1={click_id}&nw_sub_aff={aff_id}_{source_id}";
            },
            'description'     => 'offerKPI',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {

                $platform = [];
                if (strpos($var['offerPrivewUrl'], 'itunes') != false) {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if (strpos($var['offerPrivewUrl'], 'google') != false) {
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
                $geo = explode(',', $var[ 'targetCountry' ]);
                //dd($geo);
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
                //dd($target);
                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) {
                return $var['offerDailyCap'];
                },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { return 50; },
            //'aff_cap_payout'
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'offerThumbnai1' ]) ? $item[ 'offerThumbnail' ] : null;
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
        if (isset($item[ 'offerCreatives1' ]) && !empty($item[ 'offerCreatives1' ])) {
            $creatives = $item[ 'offerCreatives' ];
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