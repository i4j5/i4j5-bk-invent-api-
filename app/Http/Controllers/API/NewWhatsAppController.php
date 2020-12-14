<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Chat;
use \Curl\Curl;

class NewWhatsAppController extends Controller
{

    protected function getSizeFile($url)
    {
        $file_open = fopen($url, "r");
        $file_size = 0;
        
        while(($str = fread($file_open, 1024)) != null) {
            $file_size += strlen($str); 
        }

        return $file_size;
    }

    protected function getFileName($url)
    {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';

        $str =  basename($url);

        $ext = substr(strrchr($str, '.'), 1);
        $ext = explode('?', $ext)[0];

        if (!$ext) {
            $filename = false;

            file_get_contents($url);

            foreach ($http_response_header as $item) {

                preg_match_all('/filename="*[a-zA-Z0-9_-]+[a-zA-Z0-9\._-]+/i', $item, $result);

                if ($result[0]) {
                    $filename = str_replace(['filename="', 'filename='], '', $result[0][0]);
                }
            }

            if ($filename) {
                $ext = substr(strrchr($filename, '.'), 1);
                //$ext = explode('?', $ext)[0];
            }
        }
        
        $name = substr(str_shuffle($permitted_chars), 0, 5) .'-'. substr(str_shuffle($permitted_chars), 0, 5) .'-'. time() .'-'. substr(str_shuffle($permitted_chars), 0, 5);

        return "$name.$ext";
    }

    
    public function amocrmWebhook(Request $request)
    {

        $whatsappID = 'd1d2dc61-4dcd-4c89-b1da-f87c2ca6e61c';
        $whatsappURL = 'https://api-whatsapp.io/api';
        $whatsappTOKEN = 'c5lal5lqpv0bpylrj4ssj6b5fbs0prug9055hu9eohs=';


        $data = $request->input('message');

        $phone = $data['receiver']['phone'];

        if (Message::where('amo_message_id', $data['message']['id'])->first()) return 'ДУБЛЬ';
        
        $chat = Chat::where('phone', $phone)->first();

        if (!$chat) {
            Chat::create([
                'phone' => $phone,
                'name' => $phone,
                'amo_chat_id' => $data['conversation']['id']
            ]);
        } else {
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

        $url = $methot . '?token=' . env('WHATSAPP_TOKEN');
        $curl = new Curl(env('WHATSAPP_URL') .'/'. env('WHATSAPP_ID'));
        $res = $curl->post($url, $body);
        
        $message->whatsapp_message_id = $res->id;
        $message->save();

        return 'ok';
    }

    public function whatsappWebhook(Request $request)
    {

            
        // $url = 'https://api-whatsapp.io/api/content/f1a401cafb1e9c4b6dda060f11cbe2151e74c3cd';
        // // $url = 'https://app.uiscom.ru/system/media/talk/1347145563/3dd1e8815bde5e7f9bb15831e1f51e63/';

        // $filename = $this->getFileName($url);

        // dd($filename);

        // exit;


        $type = $request->input('type') ? $request->input('type') : false;
        $data = $request->input('data') ? $request->input('data') : [];

        //ack, messages, qrcode, accountUpdate

        $amo_secret = env('AMO_CHANNEL_SECRET_KEY');
        $amo_scope_id = env('AMO_CHANNEL_SCOPE_ID');

        $amo = \App\AmoAPI::getInstance();

        if ($type === 'messages') {
            foreach ($data as $item) {

                $whatsapp_message_id = (int) $item['id'];
                $phone = $item['id'];
                $author = $item['author'];
                $nane = $item['senderName'] ? $item['senderName'] : $phone;
                $avatar = false;

                //$chat_api = new Curl(env('WHATSAPP_URL'));
        
                // $dialogs = $chat_api->get('dialogs?token=' . env('WHATSAPP_TOKEN'))->dialogs;

                // foreach ($dialogs as $dialog) {
                //     if ($dialog->id == $item['chatId']) {
                //         $avatar = $dialog->image;
                //     }
                // }

                if ((bool) $item['fromMe']) {
                    $message = Message::where('whatsapp_message_id', $whatsapp_message_id)->first();
                    if ($message) continue;
                }


                $dataMessage = [];

                if ($item['type'] == 'chat' || $item['type'] == 'vcard') {
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
                        'file_name' => $this->getFileName($item['body']),
                        'file_size' => (int) $this->getSizeFile($item['body']),
                    ];
                } elseif ($item['type'] == 'video') {
                    $dataMessage = [
                        'type' => 'video',
                        'text' => '',
                        'media' => $item['body'],
                        'file_name' => $this->getFileName($item['body']),
                        'file_size' => (int) $this->getSizeFile($item['body']),
                    ];
                }  elseif ($item['type'] == 'audio') {
                    $dataMessage = [
                        'type' => 'video',
                        'text' => 'голосовое сообщение',
                        'media' => $item['body'],
                        'file_name' => $this->getFileName($item['body']),
                        'file_size' => (int) $this->getSizeFile($item['body']),
                    ];
                } else {
                    $dataMessage = [
                        'type' => 'file',
                        'text' => '',
                        'media' => $item['body'],
                        'file_name' => $this->getFileName($item['body']),
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
                            'id' => $author,
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
                } elseif ($avatar) {
                    $body['payload']['sender']['avatar'] = $avatar;
                }

                $signature = hash_hmac('sha1', json_encode($body), $amo_secret);


                // Отправляем в amoCRM
                $curl = new Curl();
                $curl->setHeader('cache-contro', 'no-cache');
                $curl->setHeader('content-type', 'application/json');
                $curl->setHeader('x-signature', $signature);
                $res = $curl->post("https://amojo.amocrm.ru/v2/origin/custom/{$amo_scope_id}", $body);

                if (isset($res->new_message->msgid)) {
                    $message->amo_message_id = $res->new_message->msgid;
                    $message->save();
                } else {
                    unset($body['payload']['sender']['avatar']);
                    $signature = hash_hmac('sha1', json_encode($body), $amo_secret);
                    $curl->setHeader('x-signature', $signature);
                    $res = $curl->post("https://amojo.amocrm.ru/v2/origin/custom/{$amo_scope_id}", $body);

                    if (isset($res->new_message->msgid)) {
                        $message->amo_message_id = $res->new_message->msgid;
                        $message->save();
                    } else {
                        unset($body['payload']['conversation_ref_id']);
                        $signature = hash_hmac('sha1', json_encode($body), $amo_secret);
                        $curl->setHeader('x-signature', $signature);
                        $res = $curl->post("https://amojo.amocrm.ru/v2/origin/custom/{$amo_scope_id}", $body);

                        if (isset($res->new_message->msgid)) {
                            $message->amo_message_id = $res->new_message->msgid;
                            $message->save();
                        } else {
                            $icq = new Curl();
                            $icq->get('https://api.icq.net/bot/v1/messages/sendText', [
                                'token' => env('ICQ_TOKEN'),
                                'chatId' => env('ICQ_CHAT_ID'),
                                'text' => 'WhatsApp: ' . json_encode($res),
                            ]);
                        }
                    }
                }
                

                //Уведомление 
        
                $text = "WhatsApp \n$nane\n$phone";
                $deal = null;

                $res = $amo->request('/api/v4/leads', 'get', [
                    'query' => $phone
                ]);

                if ($res && isset($res->_embedded) && isset($res->_embedded->leads)) {
                    $deal = $res->_embedded->leads[0];
                    $text = $text . "\n$deal->name\nhttps://bkinvent.amocrm.ru/leads/detail/$deal->id";
                }

                $text = "$text\n\n" . $item['body'];
                
                (new Curl())->get('https://api.icq.net/bot/v1/messages/sendText', [
                    // 'token' => '001.1127437940.0574669410:756518822',
                    // 'chatId' => 'bkinvent_sales',
                    'token' => env('ICQ_TOKEN'),
                    'chatId' => env('ICQ_CHAT_ID'),
                    'text' => $text,
                ]);
                
            }
        }

        if ($type === 'ack') {
            foreach ($data as $item) {

                $message = Message::where('whatsapp_message_id', $item['id'])->first();

                $status = 0; //Отправлено

                if ($item['status'] === 'received') {
                    $status = 1; //Доставлено
                } elseif ($item['status'] === 'viewed') {
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
        }

        return 'ok';
    }

}