<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HttpCurl;
use App\Models\GetCreativeStorage;

class zhang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zhang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'zhang';

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
        $data = GetCreativeStorage::dealCreative('android','com.daraz.android');
        dd($data);
    }

    public  static  function  changeStatus($offer)
    {
        $api_url = 'https://leanmobiapi.api.offerslook.com/v1/batches/offers/'.$offer.'?status=paused';
        $curl = new HttpCurl;
        $is_success = $curl->setHeader([
            'content-type: application/json',
            'Authorization: Basic ' . self::AuthorizationCode(),
        ])->patch($api_url);
        if ($is_success == false) {
            return $curl->error_info;
        } else {
            return $is_success;
        }

    }

    public static function getOffer()
    {
        $api_url = "http://leanmobiapi.api.offerslook.com/v1/batches/offers?sort=id&fields=id&limit=100&filters[status]=active";
        $curl = new HttpCurl;
        $is_success = $curl->setHeader([
            'content-type: application/json',
            'Authorization: Basic ' . self::AuthorizationCode(),
        ])->get($api_url);
        if ($is_success == false) {
            return $curl->error_info;
        } else {
            return $is_success;
        }


    }

    public static function AuthorizationCode()
    {
        return base64_encode("leanmobiapi:b997396dd7514f81ab8216a22daebaae");
    }


}
