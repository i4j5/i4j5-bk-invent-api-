<?php

namespace App;

use \Curl\Curl;
use App\Models\Token;

class AmoAPI
{
    protected static $_instance;
    protected $provider;

    public function __construct()
    {
        $this->provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => env('AMO_CLIENT_ID'),
            'clientSecret'            => env('AMO_CLIENT_SECRET'),
            'redirectUri'             => env('AMO_REDIRECT_URI'),
            'urlAuthorize'            => 'https://www.amocrm.ru/oauth',
            'urlAccessToken'          => 'https://' . env('AMO_DOMAIN') . '.amocrm.ru/oauth2/access_token',
            'urlResourceOwnerDetails' => 'https://' . env('AMO_DOMAIN') . '.amocrm.ru/v3/user'
        ]);
    }

    /**
     * Instance
     */
    public static function getInstance() 
    {
        if (self::$_instance === null) {
            self::$_instance = new self;   
        }
 
        return self::$_instance;
    }

    /**
     * Авторизация
     */
    public function authorization() 
    {
        $authorizationUrl = $this->provider->getAuthorizationUrl();
        
        $_SESSION['oauth2state'] = $this->provider->getState();

        header('Location: ' . $authorizationUrl);
    }

    public function error($text = '') 
    {
        $icq = new Curl();
        $icq->get('https://api.icq.net/bot/v1/messages/sendText', [
            'token' => env('ICQ_TOKEN'),
            'chatId' => env('ICQ_CHAT_ID'),
            'text' => $text,
        ]);
    }

    public function saveTokins($token) 
    {
        Token::create([
            'type' => 'amo-access',
            'value' => $token->getToken(),
            'expires' => $token->getExpires() + 82800000,
            'active' => 1
        ]);

        Token::create([
            'type' => 'amo-refresh',
            'value' => $token->getRefreshToken(),
            'expires' => $token->getExpires() + 5616000000,
            'active' => 1
        ]);
    }

    public function acessToken($code = '')
    {
        try {
        
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            Token::truncate();

            $this->saveTokins($accessToken);

            return true;
    
        } catch (\Exception $e) {
            $this->error('ERROR amoCRM: ' . $e->getMessage());    
            return false;
        }
    }

    public function refreshToken()
    {
        $token = Token::where([
            ['expires', '>=',  time() + 3600000 ], 
            ['type', '=', 'amo-refresh'],
            ['active', '=', 1]
        ])->orderByDesc('expires')->first(); //? Desc
        
        if ($token) {
            try {

                $newAccessToken = $this->provider->getAccessToken('refresh_token', [
                    'refresh_token' => $token->value
                ]);

                $oldAccessTokens = Token::where([
                    ['type', '=', 'amo-access'],
                    ['active', '=', 1]
                ])->get();

                foreach ($oldAccessTokens as $oldAccessToken) {
                    $oldAccessToken->active = 0;
                    $oldAccessToken->save();
                }

                $this->saveTokins($newAccessToken);

                return $newAccessToken;

            } catch (\Exception $e) {  
                $this->error('ERROR: amoCRM refreshToken');  
                return false;
            }

        } else {
            return false;
        }
    }

    public function request($url = '', $method = 'get', $data =[])
    {
        $accessToken = $this->getAccessToken();

        $request = new Curl('https://' . env('AMO_DOMAIN') . '.amocrm.ru');
        
        $request->setHeader('Authorization', 'Bearer ' . $accessToken);
        $request->setHeader('Content-Type', 'application/json');

        $res = $request->{$method}($url, $data);

        if (isset($res->response->error)) {
            $newAccessToken = $this->refreshToken()->getToken();
            $request->setHeader('Authorization', 'Bearer ' . $newAccessToken);
            $res = $request->{$method}($url, $data);
        }
       
        return $res;
    }

    public function getAccessToken()
    {
        $token = Token::where([
            ['expires', '>=',  time() + 3600000],
            ['type', '=', 'amo-access'],
            ['active', '=', 1]
        ])->first();

        if (!$token) {
            $newToken = $this->refreshToken();
            if ($newToken) return $newToken->getToken();
        }

        return $token->value;
    }

    /////
    ////////////////////
    /////

    public function getConcactByPhone($phone)
    {
        $res = $this->request('api/v4/contacts', 'get', [
            'filter[custom_fields_values][][]'
        ]);

        if ( isset($res_embedded->contacts[0]) )
            return $res_embedded->contacts[0];

        return false;
    }

    public function getConcactByID($id)
    {
        $res = $this->request("api/v4/contacts/$id)", 'get');
        return $res;
    }

    public function addContact($params)
    {
        $default_data = [
            'name' => '',
            'phone' => '',
            'email' => '',
            'page_view_tracker' => ''
        ];

        $data = array_merge($default_data, $params);

        $res = $this->request('api/v4/contacts', 'post', [
            '0' => [
                'name' => $data['name'],
                'custom_fields_values' => [
                    '0' => [
                        'field_id' => 75087,
                        'values' => [
                            '0' => [
                                'value' => $data['phone'],
                                'enum_code' => 'MOB'
                            ]
                        ]
                    ],

                    '1' => [
                        'field_id' => 75089,
                        'values' => [
                            '0' => [
                                'value' => $data['email'],
                                'enum_code' => 'WORK'
                            ]
                        ]
                    ],

                    '2' => [
                        'field_id' => 319977,
                        'values' => [
                            '0' => [
                                'value' => $data['page_view_tracker']
                            ]
                        ]
                    ],
                ]
            ]
        ]);

        if ( isset($res_embedded->contacts[0]) )
            return $res_embedded->contacts[0];

        return false;
    }

    public function addLead($params)
    {
        $default_data = [
            'ip' => '',
            'title' => 'LEAD',
            'name' => '',
            'phone' => '',
            'email' => '',
            'google_client_id' => '',
            'metrika_client_id' => '',
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_content' => '',
            'utm_term' => '',
            'landing_page' => '',
            'referrer' => '',
            'trace' => '',
            'tags'=> [],
            'comment' => '',
            'visit' => '',
            'roistat' => '',
            'sudo' => false,
            'page_view_tracker' => '',
        ];

        $data = array_merge($default_data, $params);

        $data['phone']= str_replace(['+', '(', ')', ' ', '-', '_', '*', '–'], '', $data['phone']);
        if (strlen($data['phone']) >= 11) {
            if ($data['phone'][0] == 8) {
                $data['phone'][0] = 7;
            }
        } else if (strlen($data['phone']) == 10) {
            $data['phone'] = '7' . $data['phone'];
        }

        $contact = $this->getConcactByPhone($data['phone']);

        if ($contact) {
            $this->addDeal($data);
        } else {
            return $this->addUnsorted($data);
        }
    }

    public function addDeal($data)
    {
        // Создать Контакт
        // Написать примечание
        // Создать Cделку
        // Связять всё 

    }

    public function addUnsorted($data) 
    {
        $contacts = [];
        $contacts[] = [
            'name' => $data['name'],
            'custom_fields_values' => [
                '0' => [
                    'field_id' => 75087,
                    'values' => [
                        '0' => [
                            'value' => $data['phone'],
                            'enum_code' => 'MOB'
                        ]
                    ]
                ],

                '1' => [
                    'field_id' => 75089,
                    'values' => [
                        '0' => [
                            'value' => $data['email'],
                            'enum_code' => 'WORK'
                        ]
                    ]
                ],

                '2' => [
                    'field_id' => 319977,
                    'values' => [
                        '0' => [
                            'value' => $data['page_view_tracker']
                        ]
                    ]
                ],
            ]
        ];

        $lead_custom_fields_values = [];

        $lead_custom_fields_values[] = [
            'field_id' => 75455,
            'values' => [
                '0' => [
                    'value' => $data['utm_source']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75457,
            'values' => [
                '0' => [
                    'value' => $data['utm_medium']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75461,
            'values' => [
                '0' => [
                    'value' => $data['utm_campaign']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75459,
            'values' => [
                '0' => [
                    'value' => $data['utm_content']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75453,
            'values' => [
                '0' => [
                    'value' => $data['utm_term']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75467,
            'values' => [
                '0' => [
                    'value' => $data['google_client_id']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75469,
            'values' => [
                '0' => [
                    'value' => $data['metrika_client_id']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75451,
            'values' => [
                '0' => [
                    'value' => $data['landing_page']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 75465,
            'values' => [
                '0' => [
                    'value' => $data['referrer']
                ]
            ]
        ];

        $lead_custom_fields_values[] = [
            'field_id' => 173485,
            'values' => [
                '0' => [
                    'value' => $data['visit']
                ]
            ]
        ];

        $leads = [];
        $leads[] = [
            'name' => $data['title'],
            'visitor_uid' => uniqid(),
            'custom_fields_values' => $lead_custom_fields_values
        ];

        $metadata = [
            'ip' => '8.8.8.8',
            'form_id' => 'bk-invent.ru',
            'form_name' => $data['title'],
            'form_sent_at' => time(),
            'form_page' => $data['landing_page'],
            'referer' => $data['referrer'],
        ];

        $unsorted_data = [];
        $unsorted_data[] = [
            'source_uid' => uniqid(),
            'source_name' => 'bk-invent.ru',
            'created_at' => time(),
            '_embedded' => [
                'leads' => $leads,
                'contacts' => $contacts
            ],
            'metadata' => $metadata,
        ];

        $res = $this->request('api/v4/leads/unsorted/forms', 'post', $unsorted_data);
        return $res ;
    }

}
