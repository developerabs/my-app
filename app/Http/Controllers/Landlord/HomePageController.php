<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Page;
use App\Models\landlord\Blog;
use App\Models\landlord\Feature;
use App\Models\landlord\HomepageWidget;
use Illuminate\Http\Request;

class HomePageController extends Controller
{
    public function index()
    {
        $widgets = HomepageWidget::whereNotIn('type', ['header', 'footer'])->where('is_global', false)->orderBy('sort_order')->get();

        $sections = [];

        foreach ($widgets as $key => $widget) {

            switch ($widget->type) {
                case 'text':
                     if($widget->content_type == 'static')
                        $widget['content'] = $this->processGridData($widget);
                    $sections[$key] = view('landlord.layouts.frontend_partials.text', compact('widget'))->render();
                    break;
                case 'slider':
                    $widget['content'] = $this->getSliderContent($widget);
                    //dd($widget['content']);
                    $sections[$key] = view('landlord.layouts.frontend_partials.slider', compact('widget'))->render();
                    break;
                case 'section':
                    $sections[$key] = view('landlord.layouts.frontend_partials.section', compact('widget'))->render();
                    break;
                case 'grid':
                    $sections[$key] = view('landlord.layouts.frontend_partials.grid', compact('widget'))->render();
                    break;
                case 'form':
                    $sections[$key] = view('landlord.layouts.frontend_partials.form', compact('widget'))->render();
                    break;
                default:
                    $sections[$key] = view('landlord.layouts.frontend_partials.section', compact('widget'))->render();
                    break;

            }
        }

        return view('landlord.index', compact('sections'));
    }

    private function processGridData($widget)
    {
        $content = $widget->content;
        if (empty($widget->content) || !is_array($widget->content)) {
            return ['items' => []];
        }

        // // STATIC GRID
        if (($widget->content_type ?? 'static') === 'static') {
            return [
                'items' => $content['items'] ?? []
            ];
        }

        // DYNAMIC GRID
        if (($widget->content_type  ?? '') == 'dynamic') {

            $model = $content['model'] ?? null;
            $limit = $content['limit'] ?? 10;
            $orderBy = $content['order_by'] ?? 'id';
            $order = $content['order'] ?? 'asc';
            // dd($model);

            if ($model && ($model)) {
                return [
                    'items' => $model::orderBy($orderBy, $order)
                        ->limit($limit)
                        ->get()
                        ->map(function ($item) {

                        return [
                                "icon" => $item->icon,
                                "text" => $item->name,
                                "description" => $item->description,

                                ];


                        })
                        ->toArray()
                ];
            }
        }

        return ['items' => []];
    }

    private function getSliderContent($widget)
    {
        $content = $widget->content;
        if (empty($widget->content) || !is_array($widget->content)) {
            return ['items' => []];
        }

        // // STATIC GRID
        if (($widget->content_type ?? 'static') === 'static') {
            return [
                'items' => $content['items'] ?? []
            ];
        }

        // DYNAMIC GRID
        if (($widget->content_type  ?? '') == 'dynamic') {

            $model = $content['model'] ?? null;
            $limit = $content['limit'] ?? 10;
            $orderBy = $content['order_by'] ?? 'id';
            $order = $content['order'] ?? 'asc';

            if ($model && ($model)) {

                return [
                    'items' => $model::orderBy($orderBy, $order)
                        ->limit($limit)
                        ->get()
                        ->map(function ($item) use ($model) {

                        return [
                                "text" => $item->name ?? $item->title ?? '',
                                "image" => $item->image ?? '',
                                "icon_class" => $item->icon ?? '',
                                "url" => $model == 'App\Models\landlord\Blog' ? route('landlord.view.Blog', $item->slug) : '#',
                            ];
                        })
                        ->toArray()
                ];
            }
        }

        return ['items' => []];

    }


    public function viewPage($slug)
    {
        $page = Page::where('slug', $slug)->where('status', 'published')->first();

        if (!$page) {
            abort(404);
        }
        //return $page;
        return view('landlord.page_details', compact('page'));
    }

    public function viewBlog($slug)
    {
        $page = Blog::where('slug', $slug)->where('status', 'published')->first();

        if (!$page) {
            abort(404);
        }
        //return $page;
        return view('landlord.page_details', compact('page'));
    }
}
