<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\HandleOffer;
use App\Models\OffersLook;

class OfferSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offer:sync {adv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'offer同步';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $adv_name = $this->argument('adv');
        $adv_config = config('advertiser.' . $adv_name);
        if (in_array($adv_config['advertiser_id'], [174])) {  //自动更新
            self::changeOfferStatusByAdvertiserId($adv_config['advertiser_id']);
        }
        if (!empty($adv_config)) {
            if (isset($adv_config[ 'commonSync' ]) && $adv_config[ 'commonSync' ] == true) {
                $controller_name = "App\\Http\\Controllers\\commonSyncController";
                $controller = app()->make($controller_name);
                $controller->config_list = ['advertiser.' . $adv_name];
                app()->call([$controller, 'index'], []);

            } else {
                $controller_name = "App\\Http\\Controllers\\${adv_name}Controller";
                $controller = app()->make($controller_name);
                app()->call([$controller, 'index'], []);
            }

        } else {
            die("Error adv source info");
        }

        if (in_array($adv_config['advertiser_id'], [174])) {
            self::changeOfferOnOffersLook($adv_config['advertiser_id']);
            self::toActiveOffer($adv_config['advertiser_id']);
        }

    }

    /**
     * @param $adver_id
     * 该广告主offer alive = 0
     */
    public static function changeOfferStatusByAdvertiserId($adver_id)
    {
        HandleOffer::where('advertiser_id', $adver_id)->update(['alive_status' => 0]);
    }

    public static function changeOfferOnOffersLook($adver_id)
    {   
        $offer_recode = [];
        HandleOffer::where('advertiser_id', $adver_id)
            ->where('alive_status', 0)
            ->where('offerslook_status', 1)
            ->select('offerslook_id')
            ->orderBy('offerslook_id')
            ->chunk(90, function($offer) use (&$offer_recode) {
                foreach ($offer as $item) {
                    $offer_recode[] = $item->offerslook_id;
                    $id[] = $item->offerslook_id;
                }

                if ($offer_recode) {
                    $status = 'paused';
                    $result = OffersLook::toChangOffersLookStatus(implode(',', $id),  $status);
                }
                
        });
         //dd($offer_recode);

        if (!empty($offer_recode)) {
            HandleOffer::whereIn('offerslook_id', $offer_recode)->update([
                'offerslook_status' => 0, 
                'alive_status' => 0,
                ]);
        }

    }

    public static function toActiveOffer($adver_id)
    {
        $offer_recode = [];
        HandleOffer::where('advertiser_id', $adver_id)
            ->where('alive_status', 1)
            ->where('offerslook_status', 0)
            ->select('offerslook_id')
            ->orderBy('offerslook_id')
            ->chunk(90, function($offer) use (&$offer_recode) {
                foreach ($offer as $item) {
                    $offer_recode[] = $item->offerslook_id;
                    $id[] = $item->offerslook_id;
                }

                if ($offer_recode) {
                    $status = 'active';
                    $result = OffersLook::toChangOffersLookStatus(implode(',', $id),  $status);
                }
                
        });
       // dd($offer_recode);

        if (!empty($offer_recode)) {
            HandleOffer::whereIn('offerslook_id', $offer_recode)->update([
                'offerslook_status' => 1, 
                'alive_status' => 1,
                ]);
        }
    }


}
