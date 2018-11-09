<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Cache;
use App\Models\HttpCurl;

use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    
        $data = Cache::store('file')->get('country');
        dd($data);
        $coutry_json_data = self::countryGet();
        //dd($coutry_json_data);
        $coutry_arry_data = json_decode($coutry_json_data, true);
        foreach ($coutry_arry_data['data']['dict_country'] as $k => $v) {
            $key[] = $v['code'];
        }
        $result_data = array_combine($key, $coutry_arry_data['data']['dict_country']);
        Cache::forever('country', json_encode($result_data));
        //dd($result_data);
    }

    public static function countryGet()
    {
        $api_url = "http://leanmobi.api.offerslook.com/v1/dict_countries";
        $curl = (new HttpCurl);
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
        return base64_encode("XXX:XXX");
    }

}
