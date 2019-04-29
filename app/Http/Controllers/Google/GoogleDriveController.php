<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;

class GoogleDriveController extends Controller
{
    protected $service;

    public function __construct()
    {
        $client = new \Google_Client();
        $client->setAuthConfig('');
        $client->addScope(\Google_Service_Drive::DRIVE);
        $this->service = new \Google_Service_Drive($client);
    }

    public function index()
    {
        echo 'ok';
    }
}
