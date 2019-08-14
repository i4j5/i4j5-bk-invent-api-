<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

    public function index()
    {        
        $pages = Page::paginate(10);
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
            'path' => ['required', 'unique:pages'],
            'content' => 'required',
        ]);

        $page = Page::create([
            'name' => $request->name,
            'path' => $request->path,
            'content' => $request->content
        ]);

        Artisan::call('cache:clear');

        return redirect('/'.$page->path.'.html');
    }

    public function show(Page $page)
    {
        // Сделать ридерект
        return view('pages.show', compact('page'));
    }

    public function showByPath(Page $page)
    {
        // $page = Page::where('path', $path)->first();
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
            // 'path' => 'required',
            'path' => 'required|unique:pages,path,'.$page->id,
            'content' => 'required',
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

    public function imageUpload(Request $request){
        $image = $request->file('upload');
        
        $image->store('public');
        $url = asset('storage/'.$image->hashName());


        return response()->json(array(
            'url' => $url,
            'uploaded'=> true,
        ));
    }
}