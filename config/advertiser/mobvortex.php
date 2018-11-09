<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'Local',          //数据落地存储点

    'http_basic_auth'  => ['karen@leanmobi.com', 'rQ2oxPQtgTe5B0msrJNoZoiLsQtSNuK4'],
    'offer_api'           => 'www.mobvortex.com/api/affoffer/index?type=all',
    //'offer_api_post_json' => '',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data) ? $data : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'offer_payout' ] < 0.5 ? false : true;
        },
        'name'    => function ($var) {
            if (empty($var[ 'offer_name' ])) {
                return false;
            }
            return true;
        },
        'country'  => function($var) {
            if ($var[ 'country' ][0]['code'] == 'UK') {
                return false;
            }

            return true;
        }
    ],
    'conversion_field'    => [
        'offer'          => [
            'original_offer_id'=>'appid',
            'name'            => function ($var) {
                if (is_array($var['platform'])) {
                    foreach ($var['platform'] as $k1 => $v1) {
                       $platform[] = $v1['system'];
                    }
                }

                if (is_array($platform)) {
                    $platform = implode(' ', array_unique($platform));
                }

                $name = fmtOfferName($var[ 'offer_name' ] . " " . $platform . " [" . $var[ 'country' ][0]['code'] . "]");

                return isset($var[ 'offer_name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 40;
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
                $payout = isset($var[ 'offer_payout' ]) ? (float)$var[ 'offer_payout' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'offer_payout' ]) ? (float)$var[ 'offer_payout' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                return $var['preview_url'];
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
                return $var[ 'tracking_url' ] . "&aff_sub1={click_id}&source_id={aff_id}&ios_idfa={ios_idfa}";
            },
            //'description'     => 'campaigndesc',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {

                if (is_array($var['platform'])) {
                    foreach ($var['platform'] as $k1 => $v1) {
                       $os[] = $v1['system'];
                    }
                }

                if (is_array($os)) {
                    $os = implode(',', $os);
                }

                if (strpos($os, 'iOS') !== false) {
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
    
                $geo = $var[ 'country' ][0]['code'];
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);

                $target[] = [
                    "type"    => 2,
                    "country" => $geo,
                    "city"    => [],
                ];

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
            'aff_cap_conversion' => function ($var) { return 100; },
            //'aff_cap_payout'
        ],
        /*'offer_category'  =>[      
            //"name" => "API"
            'name' => function ($var) { return 'API';},
        ]*/
    ],
    'conversion_creative' => function ($item) {
        $creative = [];
        $icon_url = isset($item[ 'logo' ]) ?
            $item[ 'logo' ] : null;
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