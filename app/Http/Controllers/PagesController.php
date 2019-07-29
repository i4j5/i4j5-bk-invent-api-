<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Artisan;

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
       return view('pages/visible')->with(compact('page'));
    }

    public function index()
    {        
        $pages = Page::paginate(2);
        return view('pages.index')->with(compact('pages'));
    }

    public function create()
    {
        return view('pages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
            'path' => 'required',
        ]);
        
        $page = Page::create([
            'name' => $request->name,
            'path' => $request->path,
            'content' => $request->content
        ]);

        Artisan::call('cache:clear');

        return redirect('/pages/'.$page->id);
    }

    public function show(Page $page)
    {
        return view('pages.show', compact('page'));
    }

    public function edit(Page $page)
    {
        return view('pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $request->validate([
            'name' => 'required|min:3',
            'path' => 'required',
        ]);
        
        $page->name = $request->name;
        $page->path = $request->path;
        $page->content = $request->content;
        $page->save();
        $request->session()->flash('message', 'Страница успешно изменена!');
        Artisan::call('cache:clear');
        return redirect('pages');
    }

    public function destroy(Request $request, Page $page)
    {
        $page->delete();
        $request->session()->flash('message', 'Страница успешно удалина!');
        Artisan::call('cache:clear');
        return redirect('pages');
    }
}