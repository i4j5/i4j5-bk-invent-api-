<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
// use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class MediaController extends Controller
{

    public function __construct()
    {
    }

    public function talk(Request $request, $filename)
    {        
        $path = "media/talk/$filename.mp3";

        if (!Storage::exists($path)) abort(404);

        return 
            (new Response(Storage::get($path)))
                ->header('Content-Type', Storage::mimeType($path));
    }

}