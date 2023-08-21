<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * apiUrl
     *
     * @var string
     */
    protected $host = 'https://service-chat.qontak.com';


    /**
     * tries
     *
     * @var int
     */
    protected $tries = 0;

    /**
     * getAuth
     *
     * @return void
     */
    public function getAuth()
    {
        try {
            $response = Http::withHeaders(['content-type' => 'application/json'])
                ->withBody(json_encode([
                    'username' => config('qontact.username'),
                    'password' => config('qontact.password'),
                    'grant_type' => 'password',
                    'client_id' => config('qontact.client_id'),
                    'client_secret' => config('qontact.client_secret')
                ]), 'application/json')
                ->accept(['application/json'])
                ->post($this->host . '/oauth/token');
        } catch (\Throwable $e) {
            Log::error('WA Auth Error');
            Log::error(json_encode($e));
        }
        $data = $response->object();
        if (isset($data->access_token)) {
            Cache::put('wa_auth', Carbon::now()->addHours(24));
            return $data;
        } else {
            //$msg = isset($data->error->message) ? $data->error->message : 'Unknown Error';
            Log::error('WA Auth Error');
            Log::error(json_encode($data));
        }
    }

    /**
     * validateNumber
     *
     * @param  mixed $number
     * @return bool
     */
    public function validateNumber(string $phone_number)
    {
        try {
            $auth = Cache::get('wa_auth');
            if (!$auth) {
                $auth = $this->getAuth();
            }
            $response = Http::withHeaders([
                'content-type' => 'application/json',
                'Authorization' => $auth->token_type . ' ' . $auth->access_token
            ])
                ->withBody(json_encode([
                    'channel_integration_id' => config('qontact.channel_id'),
                    'phone_numbers' => [$phone_number]
                ]), 'application/json')
                ->accept(['application/json'])
                ->post($this->host . '/api/open/v1/broadcasts/contacts');
        } catch (\Throwable $e) {
            throw $e;
        }
        $data = $response->object();
        return (isset($data->status) && $data->status == 'success');
    }

    /**
     * sendNotification
     *
     * @param  mixed $phone_number
     * @return void
     */
    public function sendNotification(string $phone_number, string $name, $transaction_type, $transaction_id, $approval_type)
    {
        if (!config('qontact.enabled')) {
            return;
        }
        try {
            $auth = Cache::get('wa_auth');
            if (!$auth) {
                $auth = $this->getAuth();
            }
            $body = [
                'to_number' => $phone_number,
                'to_name' => $name,
                'message_template_id' => config('qontact.template_id'),
                'channel_integration_id' => config('qontact.channel_id'),
                'language' => [
                    'code' => 'en'
                ],
                'parameters' => [
                    'body' => [
                        [
                            'key' => '1',
                            'value' => 'full_name',
                            'value_text' => $name
                        ],
                        [
                            'key' => '2',
                            'value' => 'transaction_type',
                            'value_text' => $transaction_type
                        ],
                        [
                            'key' => '3',
                            'value' => 'approval_type',
                            'value_text' => $approval_type
                        ],
                        [
                            'key' => '4',
                            'value' => 'transaction_id',
                            'value_text' => $transaction_id
                        ]
                    ]
                ]
            ];
            $response = Http::withHeaders([
                'content-type' => 'application/json',
                'Authorization' => $auth->token_type . ' ' . $auth->access_token
            ])
                ->withBody(json_encode($body), 'application/json')
                ->accept(['application/json'])
                ->post($this->host . '/api/open/v1/broadcasts/whatsapp/direct');
        } catch (\Throwable $e) {
            Log::error('WA Error');
            Log::error(json_encode($e));
        }
        $data = $response->object();
        Log::info('WA Response : ' . json_encode($data));
        if (!isset($data->status) || (isset($data->status) && $data->status != 'success')) {
            if (isset($data->error->code) && $data->error->code == 422 && $this->tries < 3) {
                $this->tries++;
                Log::info('WA : Trying... ' . $this->tries);
                Cache::delete('wa_auth');
                return $this->sendNotification($phone_number, $name, $transaction_type, $transaction_id, $approval_type);
            }
            // $msg = isset($data->error->message) ? $data->error->message : 'Unknown Error';
            Log::error('WA Error');
            Log::error(json_encode($data));
        }
    }
}
