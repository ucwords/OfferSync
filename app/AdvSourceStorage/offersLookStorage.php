<?php
namespace App\AdvSourceStorage;

use App\Models\OffersLook;
use App\Jobs\toInsertLocal;
use App\Models\HandleOffer;
use DB;
use function Symfony\Component\VarDumper\Tests\Fixtures\bar;

class offersLookStorage
{
    public static function save($conversion_result, $creative)
    {
        if (isset($conversion_result['offer']['carrier']) && !empty($conversion_result['offer']['carrier'])) {

            $carrier_arr = $conversion_result['offer']['carrier'];
            unset($conversion_result['offer']['carrier']);
        }
        $carrier_arr = [];

        $exist = OffersLook::offerExist($conversion_result, $conversion_result[ 'offer' ][ 'advertiser_id' ]);
        $exist_arr = anyToArray($exist);
        if (in_array($conversion_result[ 'offer' ][ 'advertiser_id' ], [174])) {
            //更新本地，存在激活。不存在则die.
            $offer_arr = [
                'name' => $conversion_result['offer']['name'],
                'advertiser_id'  => $conversion_result[ 'offer' ][ 'advertiser_id' ],
                'advertiser_offer_id'  => $conversion_result[ 'offer' ][ 'advertiser_offer_id' ],
            ];
            self::saveOffer($offer_arr);
        }

        fmtOut("OffersLook::offerExist Complete!" . $conversion_result[ 'offer' ][ 'name' ] . " Res:" . json_encode($exist_arr));
        if (isset($exist_arr[ 'code' ]) && $exist_arr[ 'code' ] == 0) {
            if ($exist_arr[ 'data' ][ 'totalRows' ] == 0) {
                $result = OffersLook::offerPost(json_encode($conversion_result));
                $offerslook_result = anyToArray($result);

                if (isset($offerslook_result[ 'code' ]) && $offerslook_result[ 'code' ] == 0) {
                    $offer_id = $offerslook_result[ 'data' ][ 'offer' ][ 'id' ];
                    fmtOut("Sync offerslook offer_id:${offer_id}");

                    if (in_array($conversion_result[ 'offer' ][ 'advertiser_id' ], [359, 113, 298, 495, 484])) {
                        foreach ($carrier_arr as $item) {
                            $tag = json_encode(['name' => trim($item)]);
                            $carrier_result = OffersLook::createCarrier($offer_id, $tag); //兼容CPA类型运营商模块 创建Carrier
                            $carrier_result_arr = anyToArray($carrier_result);
                            //dd($carrier_result_arr);
                            fmtOut("Create carrier " .$item,  $carrier_result_arr['message'] . "for offer_id:${offer_id}");
                        }

                    }
                    if (in_array($conversion_result[ 'offer' ][ 'advertiser_id' ], [174])) {
                        self::insertOfferId($offerslook_result);
                    }

                    if (isset($creative[ 'thumbfile' ])) {
                        foreach ($creative[ 'thumbfile' ] as $thumbfile) {
                            $uploadThumbnail_result = OffersLook::uploadThumbnail($thumbfile[ 'local_path' ], $offer_id);
                            if (isset($uploadThumbnail_result[ 'code' ]) && $uploadThumbnail_result[ 'code' ] == 0) {
                                $offer_thumbnail_id = $uploadThumbnail_result[ 'data' ][ 'offer_thumbnail' ][ 'id' ];
                                fmtOut("Sync offerslook offer_id:${offer_id} offer_thumbnail create Success! Id:${offer_thumbnail_id}");
                            } else {
                                fmtOut('Sync offerslook offer_id:'.json_encode($uploadThumbnail_result).'offer_thumbnail create Error!');
                            }

                        }
                    }

                    if (isset($creative[ 'image' ])) {
                        foreach ($creative[ 'image' ] as $image) {
                            $upload_result = OffersLook::uploadCreative($image[ 'local_path' ], $offer_id);
                            if (isset($upload_result[ 'code' ]) && $upload_result[ 'code' ] == 0) {
                                $offer_upload_id = $upload_result[ 'data' ][ 'offer_creative' ][ 0 ][ 'id' ];
                                fmtOut("Sync offerslook offer_id:${offer_id} offer_creative create Success! Id:${offer_upload_id}");
                            } else {
                                fmtOut('Sync offerslook offer_id:'.json_encode($upload_result). 'offer_creative create Error!');
                            }
                        }
                    }
                    if (isset($creative[ 'file' ])) {
                        foreach ($creative[ 'file' ] as $file) {
                            $upload_result = OffersLook::uploadCreative($file[ 'local_path' ], $offer_id);

                            if (isset($upload_result[ 'code' ]) && $upload_result[ 'code' ] == 0) {
                                $offer_upload_id = $upload_result[ 'data' ][ 'offer_creative' ][ 0 ][ 'id' ];
                                fmtOut("Sync offerslook offer_id:${offer_id} offer_creative create Success! Id:${offer_upload_id}");
                            }
                        }
                    }
                } else {
                    fmtOut("OffersLook::offerPost :" . json_encode($result));
                }
            } else {
                $exist_offer_id = $exist_arr[ 'data' ][ 'rowset' ][ 0 ][ 'offer' ][ 'id' ];
                if (in_array($conversion_result[ 'offer' ][ 'advertiser_id' ], [359, 113,298, 495, 484])) {
                    if ($carrier_arr) {
                        foreach ($carrier_arr as $item) {
                            $tag = json_encode(['name' => trim($item)]);
                            $carrier_result = OffersLook::createCarrier($exist_offer_id, $tag); //兼容CPA类型运营商模块 创建Carrier
                            $carrier_result_arr = anyToArray($carrier_result);
                            //dd($carrier_result_arr);
                            fmtOut("Create carrier " . $carrier_result_arr['message'] . "for offer_id:${exist_offer_id}");
                        }
                    }
                }
                if ($exist_offer_id) {
                    $result = OffersLook::offerPut($exist_offer_id, json_encode($conversion_result));
                    //自动更新处理
                    /*if (in_array($conversion_result[ 'offer' ][ 'advertiser_id' ], [174])) {
                        self::insertOfferId(json_decode($result, true));
                    }*/

                }
                fmtOut("OffersLook::offerExist num :" . $exist_arr[ 'data' ][ 'totalRows' ] . " ${exist_offer_id} " . $conversion_result[ 'offer' ][ 'name' ]);
            }
        }
    }


    /**
     * sync offer id
     * @param $offer_data
     */
    public static function insertOfferId($offer_data)
    {
        $offer_id = $offer_data[ 'data' ][ 'offer' ][ 'id' ];
        $advertiser_id = $offer_data[ 'data' ][ 'offer' ][ 'advertiser_id' ];
        $advertiser_offer_id = $offer_data[ 'data' ][ 'offer' ][ 'advertiser_offer_id' ];
        #更新本地
        DB::table('handle_offer')->where('advertiser_id', $advertiser_id)->where('advertiser_offer_id', $advertiser_offer_id)->update([
                'offerslook_id' => $offer_id,
                'offerslook_status' => 1,
                'alive_status'  => 1,
                'update_time'   => date('Y-m-d H:i:s'),
        ]);

    }

    /**
     * 保存自动同步offer
     * @param $offer
     */
    public static function saveOffer($offer)
    {   
        $exists = DB::table('handle_offer')->where('advertiser_id', $offer['advertiser_id'])->where('advertiser_offer_id', $offer['advertiser_offer_id'])->first();
        //dd($exists);
        if ($exists) {
            DB::table('handle_offer')->where('advertiser_id', $offer['advertiser_id'])->where('advertiser_offer_id', $offer['advertiser_offer_id'])->update([
                'name' => $offer['name'],
                'advertiser_id' => $offer['advertiser_id'],
                'advertiser_offer_id' => $offer['advertiser_offer_id'],
                'alive_status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }  else {
            DB::table('handle_offer')->insert([
                'name' => $offer['name'],
                'advertiser_id' => $offer['advertiser_id'],
                'advertiser_offer_id' => $offer['advertiser_offer_id'],
                'alive_status' => 1,
                'create_time' => date('Y-m-d H:i:s'),   
            ]);
        }
    }


}