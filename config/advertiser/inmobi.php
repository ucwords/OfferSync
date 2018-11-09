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
    'offer_api'           => 'https://api.hasoffers.com/Apiv3/json?NetworkId=wadogo&Target=Affiliate_Offer&Method=findMyApprovedOffers&api_key=9cba4bb3517b1edc6c7ec81b1275aba21fb70c61ff1de974ae5e4b46a995fa7f&contain%5B%5D=TrackingLink&contain%5B%5D=Country&contain%5B%5D=OfferCategory&limit=1000',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'offer_list'          => function (array $data) {
        $result = [];
        //dd($data['response']['data']['data']['349893']['Offer']);
        if(is_array($data['response']['data']['data'])){
            //dd($data['response']['data']);
            foreach($data['response']['data']['data'] as $item){
                //foreach($item as $k => $v) {
                    $result[] = $item;
                //}               
            }
        }
        //dd($result);
        return $result;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            //dd($var);
            return (float)$var['Offer'][ 'default_payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            if (empty($var['Offer'][ 'name' ])) {
                return false;
            }
            return true;
        },
        'platform' => function ($var) 
        {
            if (strpos($var['Offer'][ 'preview_url' ], 'google.com') != false) {
                return true;
            }
            if (strpos($var['Offer'][ 'preview_url' ], 'itunes.apple.com') != false) {
                return true;
            }
            return false;
        },
    ],

    'conversion_field'    => [
        'offer'          => [
           'name'            => function ($var) {
            if (strpos($var['Offer'][ 'preview_url' ], 'google.com') != false) {
                    $platform = "Android";
                }
                if (strpos($var['Offer'][ 'preview_url' ], 'itunes.apple.com') != false) {
                    $platform = "iOS";
                }
                //AE|SA
                $countrys =implode("," , array_keys($var[ 'Country' ]));
                $name = fmtOfferName($var['Offer'][ 'name' ] . " " . $platform . " [" . $countrys . "]");

                return isset($var['Offer'][ 'name' ]) ? $name : null;
            },
            'advertiser_offer_id' => function ($var) {
                return $var['Offer']['id'];
            },

            'advertiser_id'   => function ($var) {
                return 68;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return strtotime("now") + 365 * 86400;
            },
            'status'          => function ($var) { return "paused"; },
            'offer_approval'  => function ($var) { return 2; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var['Offer'][ 'default_payout' ]) ? (float)$var['Offer'][ 'default_payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var['Offer'][ 'default_payout' ]) ? (float)$var['Offer'][ 'default_payout' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var['Offer']['preview_url'];
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
                return $var['TrackingLink'][ 'click_url' ] . "&aff_sub={click_id}&Aff_sub2={aff_id}_{source_id}";
            },
            'description'     => function ($var) {
                return $var['Offer']['description'];
            },
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
               $platform = [];
                if (strpos($var['Offer'][ 'preview_url' ], 'google.com') != false) {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'Android',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if (strpos($var['Offer'][ 'preview_url' ], 'itunes.apple.com') != false) {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
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
                $geo = array_keys($var[ 'Country' ]);
                
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
                        "country" => $countries[ $var[ 'Country' ] ][ 'name' ],
                        "city"    => [],
                    ];
                }


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
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'icon' ]) ?
            $item[ 'icon' ] : null;
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

        return $creative;
    }
];