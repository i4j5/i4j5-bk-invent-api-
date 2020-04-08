<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Token;

class AmoController extends Controller
{
    protected $amo;

    public function __construct()
    {
        $this->amo = \App\AmoAPI::getInstance();
    }
    
    public function auth(Request $request)
    {


        $a = $this->amo->request('/api/v2/account');

        dd($a); 




        if (!isset($_GET['code'])) {
            $this->amo->authorization();
            exit;
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
        
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }
            
            return 'Invalid state'; 
        
        } else {
            if($this->amo->acessToken($_GET['code'])) {
                return 'ok';
            }
        }

        return 'error';

    }

    public function refreshToken(Request $request)
    {
        // if ($this->amo->refreshToken()) {
        //     return 'ok';
        // }

        // return 'error';

        return $this->amo->refreshToken();
    }

}
