<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

/**
 * Class PagesController
 * @package App\Http\Controllers
 */
class PagesController extends Controller
{
    /**
     * @param Page $page
     * @return View
     */
    public function dynamic(Page $page)
    {
       return view('pages')->with(compact('page'));
    }
}