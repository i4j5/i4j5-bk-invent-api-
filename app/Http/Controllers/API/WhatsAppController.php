<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Chat;
use \Curl\Curl;

class WhatsAppController extends Controller
{
    //protected $amo;

    public function __construct()
    {
        //$this->amo = \App\AmoAPI::getInstance();
    }

    protected function getSizeFile($url)
    {
        $file_open = fopen($url, "r");
        $file_size = 0;
        
        while(($str = fread($file_open, 1024)) != null) {
            $file_size += strlen($str); 
        }

        return $file_size;

    }
    
    public function amocrmWebhook(Request $request)
    {

        $data = $request->input('message');

        $phone = $data['receiver']['phone'];

        // dd((int) $data['timestamp']);
        
        $chat = Chat::where('phone', $phone)->first();

        if (!$chat) {
            Chat::create([
                'phone' => $phone,
                'name' => $phone,
                'amo_chat_id' => $data['conversation']['id']
            ]);
        } else if (!$chat->amo_chat_id) {
            $chat->amo_chat_id = $data['conversation']['id'];
            $chat->save();
        }
        
        $message = Message::create([
            'type' => $data['message']['type'],
            'status' => 0,
            'text' => $data['message']['text'] ? $data['message']['text'] : '',
            'media' => $data['message']['media'] ? $data['message']['media'] : '',
            'thumbnail' => $data['message']['thumbnail'] ? $data['message']['thumbnail'] : '',
            'file_name' => $data['message']['file_name'] ? $data['message']['file_name'] : '',
            'file_size' => (int) $data['message']['file_size'],
            'timestamp' => (int) $data['timestamp'],
            'amo_message_id' => $data['message']['id'],
            'whatsapp_message_id' => '',
        ]);

        $methot = 'sendMessage'; 
        $body = [
            'phone' => $phone,
            'body' => $message->text, 
        ];
        
        
        if ($message->type == 'file' || $message->type == 'picture' || $message->type == 'video') {
            $methot = 'sendFile';
            $body = array_merge($body, [
                'filename' => $message->file_name ? $message->file_name : $this->getSizeFile($message->media),
                'body' => $message->media,
                'caption' => $message->text ? $message->text  : '',
            ]);
        }


        $curl = new Curl(env('WHATSAPP_URL'));

        $url = $methot . '?token=' . env('WHATSAPP_TOKEN');

        $res = $curl->post($url, $body);

        if (isset($res->error)) return $res->error;

        if (!$res->sent) return $res->message;
        
        $message->whatsapp_message_id = $res->id;
        $message->save();


        return 'ok';
    }

    public function whatsappWebhook(Request $request)
    {
        $messages = $request->input('messages') ? $request->input('messages') : [];
        $ack = $request->input('ack') ? $request->input('ack') : [];

        $amo_secret = env('AMO_CHANNEL_SECRET_KEY');
        $amo_scope_id = env('AMO_CHANNEL_SCOPE_ID');

        foreach ($messages as $item) {

            if ($item['fromMe'] && $item['self']) {
                //return $item;
                continue;
            }

            $whatsapp_message_id = (int) $item['id'];
            $phone = explode('@', $item['chatId'])[0];
            $author = explode('@', $item['author'])[0];
            $nane = $item['senderName'];

            //return $phone;

            $dataMessage = [];

            if ($item['type'] == 'chat') {
                $dataMessage = [
                    'type' => 'text',
                    'text' => $item['body'],
                    'media' => null,
                    'file_name' => null,
                    'file_size' => 0,
                ];
            } elseif ($item['type'] == 'image') {
                $dataMessage = [
                    'type' => 'picture',
                    'text' => '',
                    'media' => $item['body'],
                    'file_name' => basename($item['body']),
                    'file_size' => (int) $this->getSizeFile($item['body']),
                ];
            } elseif ($item['type'] == 'video') {
                $dataMessage = [
                    'type' => 'video',
                    'text' => '',
                    'media' => $item['body'],
                    'file_name' => basename($item['body']),
                    'file_size' => (int) $this->getSizeFile($item['body']),
                ];
            }  elseif ($item['type'] == 'ptt') {
                $dataMessage = [
                    'type' => 'video',
                    'text' => 'голосовое сообщение',
                    'media' => $item['body'],
                    'file_name' => basename($item['body']),
                    'file_size' => (int) $this->getSizeFile($item['body']),
                ];
            } else {
                $dataMessage = [
                    'type' => 'file',
                    'text' => '',
                    'media' => $item['body'],
                    'file_name' => basename($item['body']),
                    'file_size' => (int) $this->getSizeFile($item['body']),
                ];
            }


            // Сохраняем данные 

            $chat = Chat::where('phone', $phone)->first();

            if (!$chat) {
                $chat = Chat::create([
                    'phone' => $phone,
                    'name' => $nane,
                    'amo_chat_id' => 0
                ]);
            }

            //dd($dataMessage);

            $timestamp = time();

            $message = Message::create([
                'type' => $dataMessage['type'],
                'status' => 0,
                'text' => $dataMessage['text'],
                'media' => $dataMessage['media'] ? $dataMessage['media'] : '',
                'thumbnail' => $dataMessage['media'] ? $dataMessage['media'] : '',
                'file_name' => $dataMessage['file_name'] ? $dataMessage['file_name'] : '',
                'file_size' => $dataMessage['file_size'] ? $dataMessage['file_size'] : 0,
                'timestamp' => $timestamp,
                'amo_message_id' => '',
                'whatsapp_message_id' => $whatsapp_message_id,
            ]);


            // Формируем данные для amoCRM

            $body = [
                'event_type' => 'new_message',
                'payload' => [
                    'timestamp' => $timestamp,
                    'msgid' => uniqid(),
                    'conversation_id' => $phone,
                    'sender' => [
                        'id' => $phone,
                        'name' => $nane,
                        'profile' => [
                            'phone' => $phone
                        ],
                    ],
                    'message' => $dataMessage
                ]
            ];

            if ($chat->amo_chat_id) {
                $body['payload']['conversation_ref_id'] = $chat->amo_chat_id;
            }

            $signature = hash_hmac('sha1', json_encode($body), $amo_secret);


            // Отправляем в amoCRM
            $curl = new Curl();
            $curl->setHeader('cache-contro', 'no-cache');
            $curl->setHeader('content-type', 'application/json');
            $curl->setHeader('x-signature', $signature);
            $res = $curl->post("https://amojo.amocrm.ru/v2/origin/custom/{$amo_scope_id}", $body);


            $message->amo_message_id = $res->new_message->msgid;
            $message->save();
        }

        foreach ($ack as $item) {

            $message = Message::where('whatsapp_message_id', $item['id'])->first();

            $status = 0; //ОтправленоJnghfdktyj

            if ($item['status'] == 'delivered') {
                $status = 1; //Доставлено
            } elseif ($item['status'] == 'viewed') {
                $status = 2; //Прочитано
            }

            if ($message->status != $status && $message->status < $status) {

                // Формируем данные для amoCRM
                $body = [
                    'delivery_status' => $status,
                ];

                $signature = hash_hmac('sha1', json_encode($body), $amo_secret);

                // Отправляем в amoCRM
                $curl = new Curl();
                $curl->setHeader('cache-contro', 'no-cache');
                $curl->setHeader('content-type', 'application/json');
                $curl->setHeader('x-signature', $signature);
                $res = $curl->post("https://amojo.amocrm.ru/v2/origin/custom/{$amo_scope_id}/{$message->amo_message_id}/delivery_status", $body);

                $message->status = $status;
                $message->save();

            }
        }


        return 'ok';

    }

}