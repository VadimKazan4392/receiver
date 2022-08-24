<?php

namespace App\Http\Controllers\Webhooks\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Mockery\Exception;
use Illuminate\Support\Facades\Log;

class RequestParserWebhookController extends Controller
{
    private $request_tokens = [
        'school' => '5fe17b706b7e9cb261f086f1cb6bb94a',
        'monro' => '5fe17b706b7f086f1cb6bb94ae9cb261'
    ];
    private $base_url = [
        'school' => 'https://school.easy-mo.ru/channels/requests/',
        'monro' => 'https://easy-mo.ru/channels/requests/'
    ];
    private $client;
    private $request_id;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function prepareDataFromTildaSchool(Request $request)
    {
        try {
//            Log::channel('test')->info(print_r($request->all(), true));
            $data = $request->all();
            $validData = $this->preparationFromTilda($data);
            $body = $this->toValidArray($validData);
            $this->saveBody($body, 'school', 'tilda');
            $result = $this->sendRequest($body, 'school');
            if ($result)
                $this->setSuccess();
            return [
                'statusCode' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function prepareDataFromTildaMonro(Request $request)
    {
        try {
            $data = $request->all();
            $validData = $this->preparationFromTilda($data);
            $body = $this->toValidArray($validData);
            $this->saveBody($body, 'monro', 'tilda');
            $result = $this->sendRequest($body, 'monro');
            if ($result)
                $this->setSuccess();
            return [
                'statusCode' => 200
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function preparationFromTilda(array $data): array
    {
        $name = '';
        $phone = '';

        if (isset($data['Name']))
            $name = $data['Name'];

        if (isset($data['phone']))
            $phone = $data['phone'];

        if (isset($data['COOKIES'])) {
            $cookie_items = explode(';', $data['COOKIES']);
            $cookie_items = collect($cookie_items)->map(function ($i) {
                return trim(urldecode($i));
            })->toArray();
            $utm_items = '';
            foreach ($cookie_items as $key => $item) {
                if (strstr($item, 'TILDAUTM=')) {
                    $utm_items = str_replace('TILDAUTM=', '', $item);
                }
            }
            $utm_items = explode('|||', $utm_items);
            $utms = [];
            foreach ($utm_items as $utm_item) {
                if ($utm_item) {
                    $vals = explode('=', $utm_item);
                    $utms[$vals[0]] = urldecode($vals[1]);
                }
            }
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'utms' => $utms
        ];
    }

    private function toValidArray(array $validData): array
    {

        $json = [
            'service'  => '52e504515303ba212d8250fdaa13ed2a',
            'contacts' => [
                'phone' => str_replace([' ', '(', ')', '+', '-'], '', $validData['phone']),
                'name'  => $validData['name']
            ],
            'extra'    => [
                'utm' => $validData['utms']
            ],
            'cookies'  => [
                'roistat_visit' => []
            ],
            'results'  => [''],
            'answers'  => [['a' => '', 'q' => ['']]],
            'raw'      => [['a' => '', 'q' => ['']]]
        ];
        return $json;
    }

    private function sendRequest(array $body, string $crm_key)
    {
        $response = $this->client->request
        (
            'POST',
            $this->base_url[$crm_key] . $this->request_tokens[$crm_key], [
            'json' => $body
        ]);

        if (json_decode($response->getBody())->status == 'ok')
        {
            return true;
        }
    }

    private function saveBody(array $data, string $crm_type, string $source_type)
    {
        $req = Requests::create([
           'crm_type' => $crm_type,
           'request_info' => json_encode($data),
            'source_type' => $source_type
        ]);

        if ($req)
            $this->request_id = $req->id;
    }

    private function setSuccess()
    {
        Requests::where('id', $this->request_id)->update(['is_requested' => true]);
    }
}
