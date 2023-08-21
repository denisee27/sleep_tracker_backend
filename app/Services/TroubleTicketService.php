<?php

namespace App\Services;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;

class TroubleTicketService
{
    /**
     * auth
     *
     * @var string
     */
    protected $authorization = 'Bearer xzvOowuH6nFdXJH2dz8ZxINFktduohwtbnVzdQ==';

    /**
     * apiUrl
     *
     * @var string
     */
    protected $apiUrl = 'http://35.219.106.161:8080/PatroliApi/getOpenTickets';

    /**
     * get
     *
     * @return void
     */
    public function get()
    {
        try {
            $response = Http::withHeaders(['authorization' => $this->authorization])
                ->accept(['application/json'])
                ->asJson()
                ->get($this->apiUrl);
        } catch (\Throwable $e) {
            throw $e;
        }
        $data = $response->object();
        if (!isset($data->data)) {
            throw new HttpClientException("Open Ticket Web service error", 500);
        }

        return collect($data->data)->sortBy('ticket_number')->all();

        // $items = [];
        // foreach ($data->data as $item) {
        //     if (count($item->customers)) {
        //         foreach ($item->customers as $c) {
        //             $items[] = [
        //                 'ticket_number' => $item->ticket_no,
        //                 'section_name' => $item->section_name,
        //                 'segment_name' => $item->segment_name,
        //                 'customer_name' => $c->customer_name,
        //             ];
        //         }
        //     } else {
        //         $items[] = [
        //             'ticket_number' => $item->ticket_no,
        //             'section_name' => $item->section_name,
        //             'segment_name' => $item->segment_name,
        //             'customer_name' => null
        //         ];
        //     }
        // }

        // return collect($items)->sortBy('ticket_number')->all();
    }
}
