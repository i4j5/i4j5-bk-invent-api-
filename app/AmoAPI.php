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
}
