1<?php
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
    'offer_api'           => 'http://api.mobiletraffic.de/affiliate/offer/findAll/?token=x8mnh0S1KBsuy7NWorCeDisuUFVm19er',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {    
        $result=[];
        if(is_array($data['offers'])){
            foreach($data['offers'] as $k => $item){
                $offer_json_data = file_get_contents('http://api.mobiletraffic.de/affiliate/offer/getCreatives/?token=x8mnh0S1KBsuy7NWorCeDisuUFVm19er&offer_id='.$k);
                $offer_data = json_decode($offer_json_data,true);
                if($offer_data['success']=='true'){
                    $result[]= array_merge($item, $offer_data);
                }
            }
        }

        return $result;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'Payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            if (empty($var[ 'Name' ])) {
                return false;
            }
            return true;
        },
        'Tracking_url'   => function ($var) {
            if (empty($var[ 'Tracking_url' ])) {
                return false;
            }
            return true;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            'name'            => function ($var) {

                $name = fmtOfferName($var[ 'Name' ] . " " .$var['Platforms']. " [" . $var[ 'Countries' ] . "]");
                
                return isset($var[ 'Name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 91;
            },
            'start_date'      => function ($var) {
            
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
                $payout = isset($var[ 'Payout' ]) ? (float)$var[ 'Payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'Payout' ]) ? (float)$var[ 'Payout' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var[ 'Preview_url' ];
                
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
               
                return $var[ 'Tracking_url' ] . "&dv1={click_id}&nw_sub_aff={aff_id}_{source_id}";
            },
            //'description'     => 'campaigndesc',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {           
                return $var['Platforms'];
            },
        ],
        'offer_geo'      => [
            'target' => function ($var) {
                $countries = [];
                $countries_cache = Cache::store('file')->get('countries', []);
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
                $geo = explode(',', $var[ 'Countries' ]);
                //dd($geo);
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
                        "country" => $countries[ $var[ 'Countries' ] ][ 'name' ],
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
        ]
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'Icon_url' ]) ? $item[ 'Icon_url' ] : null;

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
        if (isset($item[ 'creatives' ]) && !empty($item[ 'creatives' ])) {
            $creatives = $item[ 'creatives' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem['url'] = str_replace('https', 'http', $fitem['url']);
                    if (getUrlCode($fitem['url']) != 200) {
                        continue;
                    }
                     //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem['name']) . "." . imageTypeByUrl($fitem['url']);
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