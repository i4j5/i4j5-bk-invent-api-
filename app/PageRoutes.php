<?php


namespace App;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Http\Controllers\PagesController;
use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PageRoutes
{
    /**
     * Define routes.
     */
    public function routes()
    {
        $this->pages()->each(function(Page $page) {
            Route::get($page->path . '.html', function() use ($page) {
                return App::make(PagesController::class)->callAction('showByPath', ['page' => $page]);
            })->name('page.'.Str::snake($page->path));
        });
    }

    /**
     * Load pages from database.
     * @return Collection|Page[]
     */
    private function pages()
    {

        return Cache::remember(
            'pages_routes',
            Carbon::now()->addWeek(),
            function() {
                try {
                    $pages = Page::all();
                    return $pages;
                }
                catch (\Exception $exception) {
                    return new Collection();
                }
            }
        );
    }
}
