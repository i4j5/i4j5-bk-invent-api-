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
            'clientId' => '8a100548-aa76-426c-a031-28cb3f165550',
            'clientSecret' => 'eKlgyyYD3v5YdlGIujn0RnJ262WZMGCJVkMgemDex8OpsK9unf3mZlVD79f0EcUd',
            //'redirectUri' => 'https://private.bk-invent.ru/api/amo/auth',
            'redirectUri' => 'https://bk-invent.ru',
            'urlAuthorize'            => 'https://www.amocrm.ru/oauth',
            'urlAccessToken'          => 'https://bkinvent.amocrm.ru/oauth2/access_token',
            'urlResourceOwnerDetails' => 'https://bkinvent.amocrm.ru/v3/user'
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

    public function authorization() 
    {
        $authorizationUrl = $this->provider->getAuthorizationUrl();
        
        $_SESSION['oauth2state'] = $this->provider->getState();
        
        header('Location: ' . $authorizationUrl);
    }

    public function saveTokins($token) 
    {
        Token::create([
            'type' => 'amo-access',
            'value' => $token->getToken(),
            'expires' => $token->getExpires() + 82800000,
        ]);

        Token::create([
            'type' => 'amo-refresh',
            'value' => $token->getRefreshToken(),
            'expires' => $token->getExpires() + 5616000000,
        ]);
    }


    public function acessToken($code = '')
    {
        try {
        
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);
            
            $this->amo->saveTokins($accessToken);

            return $accessToken;
    
        } catch (\Exception $e) {    
            return false;
        }

    }

    public function refreshToken()
    {
        $token = Token::where([
            ['expires', '>=',  time() + 3600000 ], 
            ['type', '=', 'amo-refresh']
        ])->orderByDesc('expires')->first(); //? Не Desc
        
        if ($token) {

            try {

                $newAccessToken = $this->provider->getAccessToken('refresh_token', [
                    'refresh_token' => $token->value
                ]);

                $this->saveTokins($newAccessToken);

                return $newAccessToken;

            } catch (\Exception $e) {    
                return false;
            }

        } else {
            return false;
        }
    }

    public function request($url = '', $method = 'get', $data =[])
    {
        $accessToken = $this->getAccessToken();

        //return $accessToken;

        $request = new Curl('https://bkinvent.amocrm.ru');
        
        $request->setHeader('Authorization', 'Bearer ' . $accessToken);
        //$request->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        return $request->get('/api/v2/account');

        //return $request->{$method}($url, $data);
    }

    public function getAccessToken()
    {
        $token = Token::where([
            ['expires', '>=',  time() + 3600000],
            ['type', '=', 'amo-access']
        ])->first();

        //dd($token);

        //Нужна более сложная проверка 

        if (!$token) {
            $token = $this->refreshToken();

            dd($token);

            //???

            if ($token) return $token->getToken();
        }


        return $token->value;
    }
}
