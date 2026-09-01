<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\HomepageWidget;
use App\Models\landlord\LandlordMedia;
use App\Models\landlord\Page;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomepageWidgetController extends Controller
{
    use HasFiles;
    public function index()
    {
        return view('landlord.dashboard.homepage_widget.index');
    }

    public function getWidgets()
    {
        $widgets = HomepageWidget::all();

        $header = $widgets->where('type', 'header')->where('is_global', true)->first();
        $footer = $widgets->where('type', 'footer')->where('is_global', true)->first();

        $bodyWidgets = $widgets->filter(function ($widget) {
            return !in_array($widget->type, ['header', 'footer']);
        })->sortBy('sort_order');

        return view('landlord.dashboard.homepage_widget._widgets', compact('header', 'footer', 'bodyWidgets'));
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'title' => 'required|unique:homepage_widgets,title',
            'type' => 'required',
            'content_type' => 'required',
            'sort_order' => 'required|numeric|min:0'
        ]);


        $widget = HomepageWidget::create($validated);

        return response()->json([
            'message' => 'Widget created successfully',
            'widget' => $widget
        ]);

    }

    public function configureWidget(HomepageWidget $widget)
    {
        $view = '';
        $type = $widget->type;

        switch ($widget->type) {
            case 'slider':
                $view = view('landlord.dashboard.homepage_widget.ajax._slider_edit', compact('widget'))->render();
                break;

            case 'grid':
                $view = view('landlord.dashboard.homepage_widget.ajax._grid_edit', compact('widget'))->render();
                break;

            case 'text':
                $view = view('landlord.dashboard.homepage_widget.ajax._text_edit', compact('widget'))->render();
                break;

            case 'form':
                $view = view('landlord.dashboard.homepage_widget.ajax._form_edit', compact('widget'))->render();
                break;

            case 'header':
                $pages = Page::published()->get();
                $widgetList = HomepageWidget::whereNotIn('type', ['header', 'footer'])->where('is_global', false)->get();
                $view = view('landlord.dashboard.homepage_widget.ajax._header_edit', compact('widget', 'pages', 'widgetList'))->render();
                break;

            case 'footer':
                $pages = Page::published()->get();
                $widgetList = HomepageWidget::whereNotIn('type', ['header', 'footer'])->where('is_global', false)->get();
                $view = view('landlord.dashboard.homepage_widget.ajax._footer_edit', compact('widget', 'pages', 'widgetList'))->render();
                break;

            case 'section':
                $widgetList = HomepageWidget::where('type', 'section')->where('is_global', false)->get();
                $view = view('landlord.dashboard.homepage_widget.ajax._section_edit', compact('widget', 'widgetList'))->render();
                break;

            default:
                $view = view('landlord.dashboard.homepage_widget._body', compact('widget'))->render();
                break;
        }

        return response()->json([
            'view' => $view,
            'type' => $type,
        ]);
    }

    public function updateWidget(Request $request, HomepageWidget $widget)
    {

        //dd($request->all());
        $request->validate([
            'title' => 'required|string|max:255|unique:homepage_widgets,title,' . $widget->id,
            'subtitle' => 'nullable|string|max:255',
            'sort_order' => 'required|integer',
        ]);

        $widget->fill([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'sort_order' => $request->sort_order,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        $method = 'handle' . Str::studly($widget->type) . 'Widget';
        if (method_exists($this, $method)) {
            $this->$method($request, $widget);
        } else {
            $widget->content = [];
            $widget->settings = [];
        }

        $widget->save();
        return response()->json([
            'message' => 'Widget updated successfully',
            'widget' => $widget
        ]);
    }

    public function deleteWidget(HomepageWidget $widget)
    {
        LandlordMedia::where('model_type', HomepageWidget::class)->where('model_id', $widget->id)->update(['used' => false]);

        LandlordMedia::cleanupUnused();

        $widget->delete();

        return response()->json([
            'message' => 'Widget deleted successfully'
        ]);
    }


    private function handleSliderWidget(Request $request, HomepageWidget $widget)
    {
        if ($widget->content_type === 'static') {
            $slidesFiles = $request->file('slides', []);
            $slidesData = $request->input('slides', []);

            // পুরনো মিডিয়া ট্র্যাক করা
            $existingMedia = LandlordMedia::where('model_type', HomepageWidget::class)
                ->where('model_id', $widget->id)
                ->get()
                ->keyBy('path');

            // সব মিডিয়া আগে inactive করা
            LandlordMedia::where('model_type', HomepageWidget::class)
                ->where('model_id', $widget->id)
                ->update(['used' => false]);

            $items = [];

            foreach ($slidesData as $index => $slide) {
                $imagePath = null;

                // ✅ নতুন ইমেজ আপলোড
                if (!empty($slidesFiles[$index]['image']) && $slidesFiles[$index]['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $image = $slidesFiles[$index]['image'];
                    $path = $this->uploadUploadedFile($image, 'landlord/widget/slider', 'public');

                    LandlordMedia::create([
                        'path' => $path,
                        'disk' => 'public',
                        'type' => $image->getClientMimeType(),
                        'original_name' => $image->getClientOriginalName(),
                        'used' => true,
                        'model_type' => HomepageWidget::class,
                        'model_id' => $widget->id
                    ]);

                    $imagePath = $path;
                }
                // ✅ পুরনো ইমেজ থাকলে সেট ব্যবহার
                elseif (!empty($slide['existing_image'])) {
                    $imagePath = $slide['existing_image'];

                    if (isset($existingMedia[$imagePath])) {
                        $existingMedia[$imagePath]->update(['used' => true]);
                    }
                }

                $items[] = [
                    'image' => $imagePath,
                    'text' => $slide['text'] ?? null,
                ];
            }

            $widget->content = ['items' => $items];
        } else {
            // Dynamic content
            $widget->content = [
                'model' => $request->input('model'),
                'limit' => (int) $request->input('limit', 10),
                'order_by' => $request->input('order_by', 'created_at'),
                'order' => $request->input('order', 'desc'),
            ];
        }

        // ✅ Slider settings clean array
        $widget->settings = [
            'width' => $request->input('slider_settings.width', 'w-full'),
            'slides_to_show' => (int) $request->input('slider_settings.slides_to_show', 1),
            'slides_to_show_tablet' => (int) $request->input('slider_settings.slides_to_show_tablet', 1),
            'slides_to_show_mobile' => (int) $request->input('slider_settings.slides_to_show_mobile', 1),
            'show_caption' => $request->boolean('slider_settings.show_caption'),
            'caption_color' => $request->input('slider_settings.caption_color', '#333333'),
            'autoplay_speed' => (int) $request->input('slider_settings.autoplay_speed', 3000),
            'autoplay' => $request->boolean('slider_settings.autoplay'),
            'arrows' => $request->boolean('slider_settings.arrows'),
            'dots' => $request->boolean('slider_settings.dots'),
            'infinite_loop' => $request->boolean('slider_settings.infinite_loop'),
            'show_title_on_top' => $request->boolean('slider_settings.show_title_on_top'),
        ];
    }

    private function handleGridWidget(Request $request, HomepageWidget $widget) {
        if ($widget->content_type === 'static') {

            $itemfiles = $request->file('items', []);
            $itemsData = $request->input('items', []);

            // পুরনো মিডিয়া ট্র্যাক করা
            $existingMedia = LandlordMedia::where('model_type', HomepageWidget::class)
                ->where('model_id', $widget->id)
                ->get()
                ->keyBy('path');

            // সব মিডিয়া আগে inactive করা
            LandlordMedia::where('model_type', HomepageWidget::class)
                ->where('model_id', $widget->id)
                ->update(['used' => false]);

            $items = [];

            foreach ($itemsData as $index => $item) {
                $imagePath = null;

                // ✅ নতুন ইমেজ আপলোড
                if (!empty($itemfiles[$index]['image']) && $itemfiles[$index]['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $image = $itemfiles[$index]['image'];
                    $path = $this->uploadUploadedFile($image, 'landlord/widget/grid', 'public');

                    LandlordMedia::create([
                        'path' => $path,
                        'disk' => 'public',
                        'type' => $image->getClientMimeType(),
                        'original_name' => $image->getClientOriginalName(),
                        'used' => true,
                        'model_type' => HomepageWidget::class,
                        'model_id' => $widget->id
                    ]);

                    $imagePath = $path;
                }
                // ✅ পুরনো ইমেজ থাকলে সেট ব্যবহার
                elseif (!empty($item['existing_image'])) {
                    $imagePath = $item['existing_image'];

                    if (isset($existingMedia[$imagePath])) {
                        $existingMedia[$imagePath]->update(['used' => true]);
                    }
                }

                $items[] = [
                    'image' => $imagePath,
                    'text' => $item['text'] ?? null,
                    'description' => $item['description'] ?? null
                ];
            }

            $widget->content = ['items' => $items];
        } else {
            // Dynamic content
            $widget->content = [
                'model' => $request->input('model'),
                'limit' => (int) $request->input('limit', 10),
                'order_by' => $request->input('order_by', 'created_at'),
                'order' => $request->input('order', 'desc'),
            ];
        }

        // ✅ Grid settings clean array
        $widget->settings = [
            'width' => $request->input('grid_settings.width', 'w-full'),
            'grid_per_row' => (int) $request->input('grid_settings.grid_per_row', 1),
            'grid_gap' => (int) $request->input('grid_settings.grid_gap', 0),
            'show_title_on_top' => $request->boolean('grid_settings.show_title_on_top'),
        ];
    }

    private function handleTextWidget(Request $request, HomepageWidget $widget) {
        $widget->content = [
            'description' => $request->input('content') ?? null,
        ];

        $content = $request->input('content');

        preg_match_all('/<img[^>]+src="([^">]+)"/', $content, $matches);
        $urls = $matches[1] ?? [];

        foreach ($urls as $url) {
            $path = str_replace(asset('storage/').'/', '', $url);
            LandlordMedia::where('path', $path)->update(['used' => true]);
        }

        LandlordMedia::cleanupUnused();

        $widget->settings = [
            'show_title_on_top' => $request->boolean('text_settings.show_title_on_top'),
        ];
    }

    private function handleFormWidget(Request $request, HomepageWidget $widget) {
        $itemsData = $request->input('items', []);

        $items = [];

        foreach ($itemsData as $index => $item) {
            $items[] = [
                'field_type' => $item['field_type'] ?? null,
                'field_label' => $item['field_label'] ?? null,
                'field_name' => $item['field_name'] ?? null,
                'width' => $item['width'] ?? 12,
                'field_value' => $item['field_value'] ?? null,
                'is_required' => $item['is_required'] ?? 0
            ];
        }

        $widget->content = ['items' => $items];

        $widget->settings = [
            'form_submitted_for' => $request->input('form_settings.form_submitted_for'),
            'button_text' => $request->input('form_settings.button_text'),
            'button_color' => $request->input('form_settings.button_color'),
            'button_align' => $request->input('form_settings.button_align'),
            'show_title_on_top' => $request->boolean('form_settings.show_title_on_top'),
        ];
    }

    private function handleSectionWidget(Request $request, HomepageWidget $widget)
    {
        $sectionFiles = $request->file('section', []);
        $sectionData  = $request->input('section', []);

        /* -----------------------------------------------------------------
            STEP 1: সব মিডিয়া used = false করে দেই
        -------------------------------------------------------------------*/
        LandlordMedia::where('model_type', HomepageWidget::class)
            ->where('model_id', $widget->id)
            ->update(['used' => false]);


        /* -----------------------------------------------------------------
            1) BACKGROUND IMAGE
        -------------------------------------------------------------------*/
        if (isset($sectionFiles['bg_image']) && $sectionFiles['bg_image'] instanceof \Illuminate\Http\UploadedFile) {

            $image = $sectionFiles['bg_image'];
            $path = $this->uploadUploadedFile($image, 'landlord/widget/section', 'public');

            LandlordMedia::create([
                'path'          => $path,
                'disk'          => 'public',
                'type'          => $image->getClientMimeType(),
                'original_name' => $image->getClientOriginalName(),
                'used'          => true,
                'model_type'    => HomepageWidget::class,
                'model_id'      => $widget->id
            ]);

        } else {
            $path = $sectionData['existing_bg_image'] ?? ($widget->content['bg_image'] ?? null);
        }


        /* -----------------------------------------------------------------
            2) FLOATING IMAGE 1
        -------------------------------------------------------------------*/
        $float1 = $sectionData['existing_floating_image_1'] ?? ($widget->content['floating_image_1'] ?? null);

        if (isset($sectionFiles['floating_image_1']) && $sectionFiles['floating_image_1'] instanceof \Illuminate\Http\UploadedFile) {

            $file1 = $sectionFiles['floating_image_1'];
            $float1 = $this->uploadUploadedFile($file1, 'landlord/widget/section', 'public');

            LandlordMedia::create([
                'path'          => $float1,
                'disk'          => 'public',
                'type'          => $file1->getClientMimeType(),
                'original_name' => $file1->getClientOriginalName(),
                'used'          => true,
                'model_type'    => HomepageWidget::class,
                'model_id'      => $widget->id
            ]);
        }


        /* -----------------------------------------------------------------
            3) FLOATING IMAGE 2
        -------------------------------------------------------------------*/
        $float2 = $sectionData['existing_floating_image_2'] ?? ($widget->content['floating_image_2'] ?? null);

        if (isset($sectionFiles['floating_image_2']) && $sectionFiles['floating_image_2'] instanceof \Illuminate\Http\UploadedFile) {

            $file2 = $sectionFiles['floating_image_2'];
            $float2 = $this->uploadUploadedFile($file2, 'landlord/widget/section', 'public');

            LandlordMedia::create([
                'path'          => $float2,
                'disk'          => 'public',
                'type'          => $file2->getClientMimeType(),
                'original_name' => $file2->getClientOriginalName(),
                'used'          => true,
                'model_type'    => HomepageWidget::class,
                'model_id'      => $widget->id
            ]);
        }


        /* -----------------------------------------------------------------
            FINAL CONTENT UPDATE
        -------------------------------------------------------------------*/
        $widget->content = [
            'bg_type'      => $sectionData['bg_type'] ?? 'color',
            'bg_color'     => $sectionData['bg_color'] ?? '#ffffff',
            'bg_image'     => $path,
            'bg_image_position' => $sectionData['bg_image_position'] ?? null,
            'bg_image_repeat'   => $sectionData['bg_image_repeat'] ?? null,
            'bg_image_size'     => $sectionData['bg_image_size'] ?? null,
            'bg_gradient_start' => $sectionData['bg_gradient_start'] ?? null,
            'bg_gradient_end'   => $sectionData['bg_gradient_end'] ?? null,
            'bg_gradient_direction' => $sectionData['bg_gradient_direction'] ?? null,

            'body'           => $sectionData['content'] ?? null,
            'sub_heading'    => $sectionData['sub_heading'] ?? null,
            'sub_heading_color' => $sectionData['sub_heading_color'] ?? null,
            'font_size'      => $sectionData['font_size'] ?? null,
            'text_color'     => $sectionData['text_color'] ?? null,
            'text_align'     => $sectionData['text_align'] ?? null,
            'font_weight'    => $sectionData['font_weight'] ?? null,
            'show_customer_counter' => $request->boolean('section.show_customer_counter'),

            'button_text'    => $sectionData['button_text'] ?? null,
            'button_type'    => $sectionData['button_type'] ?? null,
            'button_route'   => $sectionData['button_route'] ?? null,
            'button_url'     => $sectionData['button_url'] ?? null,
            'button_color'   => $sectionData['button_color'] ?? null,
            'button_hover_color' => $sectionData['button_hover_color'] ?? null,
            'button_text_color' => $sectionData['button_text_color'] ?? null,
            'show_button'    => $request->boolean('section.show_button'),

            'floating_image_1' => $float1,
            'show_floating_image_1' => $request->boolean('section.show_floating_image_1'),

            'floating_image_2' => $float2,
            'show_floating_image_2' => $request->boolean('section.show_floating_image_2'),
        ];

        $widget->settings = [
            'width'            => $request->input('section_settings.width', 'full'),
            'show_title_on_top'=> $request->boolean('section_settings.show_title_on_top'),
        ];


        /* -----------------------------------------------------------------
            STEP 4: ব্যবহৃত ইমেজগুলো আবার used = true করে দেই
        -------------------------------------------------------------------*/
        $usedPaths = array_filter([$path, $float1, $float2]);

        if (!empty($usedPaths)) {
            LandlordMedia::where('model_type', HomepageWidget::class)
                ->where('model_id', $widget->id)
                ->whereIn('path', $usedPaths)
                ->update(['used' => true]);
        }
    }


    private function handleHeaderWidget(Request $request, HomepageWidget $widget) {
        $headerFiles = $request->file('header', []);
        $headerData  = $request->input('header', []);

        LandlordMedia::where('model_type', HomepageWidget::class)
            ->where('model_id', $widget->id)
            ->update(['used' => false]);

        $logoType = $headerData['logo_type'] ?? 'site';
        $logoPath = null;

        if ($logoType === 'custom') {

            if (isset($headerFiles['custom_logo']) &&
                $headerFiles['custom_logo'] instanceof \Illuminate\Http\UploadedFile) {

                $image = $headerFiles['custom_logo'];

                $logoPath = $this->uploadUploadedFile($image, 'landlord/widget/header', 'public');

                LandlordMedia::create([
                    'path'          => $logoPath,
                    'disk'          => 'public',
                    'type'          => $image->getClientMimeType(),
                    'original_name' => $image->getClientOriginalName(),
                    'used'          => true,
                    'model_type'    => HomepageWidget::class,
                    'model_id'      => $widget->id
                ]);

            } else {
                $logoPath = $headerData['existing_custom_logo']
                    ?? ($widget->content['custom_logo'] ?? null);

                if ($logoPath) {
                    LandlordMedia::where('path', $logoPath)
                        ->where('model_type', HomepageWidget::class)
                        ->where('model_id', $widget->id)
                        ->update(['used' => true]);
                }
            }

        } else {
            $logoPath = null;
        }

        $showTitle = isset($headerData['show_title']);


        $menus = $headerData['menus'] ?? [];
        $cleanMenus = [];

        foreach ($menus as $menu) {

            if (empty($menu['label'])) { continue; }

            $item = [
                'label' => $menu['label'],
                'type'  => $menu['type'] ?? 'page',
            ];

            if ($menu['type'] === 'custom') {
                $item['url'] = $menu['url'] ?? '#';

            } elseif ($menu['type'] === 'page_widget') {
                $item['url'] = '#'.$menu['widget'] ?? null;

            } else {
                $item['url'] = url($menu['page'] ?? '/');
            }

            $cleanMenus[] = $item;
        }

        $widget->content = [
            'logo_type'  => $logoType,
            'custom_logo'=> $logoPath,

            'show_title' => $showTitle ?? false,

            'menu_text_color' => $headerData['menu_text_color'] ?? '#000000',
            'menu_hover_text_color' => $headerData['menu_hover_text_color'] ?? '#000000',
            'menus' => $cleanMenus,

            'show_language_switcher' => $request->boolean('header.show_language_switcher'),
        ];

        $widget->settings = [
            'width' => $request->input('header_settings.width', 'full'),
            'bottom_border' => $request->boolean('header_settings.bottom_border'),
            'shadow' => $request->boolean('header_settings.shadow'),
            'position' => $request->input('header_settings.position', 'sticky'),
            'background_type' => $request->input('header_settings.background_type', 'transparent'),
            'background_color' => $request->input('header_settings.background_color', '#ffffff'),
        ];
    }

    private function handleFooterWidget(Request $request, HomepageWidget $widget) {
        $footerData = $request->input('footer', []);
        $footerFiles = $request->file('footer', []);

        LandlordMedia::where('model_type', HomepageWidget::class)
            ->where('model_id', $widget->id)
            ->update(['used' => false]);

        $path = null;

        if (isset($footerFiles['bg_image']) && $footerFiles['bg_image'] instanceof \Illuminate\Http\UploadedFile) {

            $image = $footerFiles['bg_image'];
            $path = $this->uploadUploadedFile($image, 'landlord/widget/footer', 'public');

            LandlordMedia::create([
                'path'          => $path,
                'disk'          => 'public',
                'type'          => $image->getClientMimeType(),
                'original_name' => $image->getClientOriginalName(),
                'used'          => true,
                'model_type'    => HomepageWidget::class,
                'model_id'      => $widget->id
            ]);

        } else {
            $path = $footerData['existing_bg_image'] ?? ($widget->content['bg_image'] ?? null);
        }

        $socialLinks = $footerData['social_links'] ?? [];
        $cleanSocialLinks = [];

        foreach ($socialLinks as $link) {
            if (empty($link['url'])) { continue; }

            $cleanSocialLinks[] = [
                'url'   => $link['url'] ?? '#',
                'icon_class' => $link['icon_class'] ?? 'fa-solid fa-ban',
            ];
        }

        $companyLinks = $footerData['company_links'] ?? [];
        $cleanCompanyLinks = [];

        foreach ($companyLinks as $clink) {
            if (empty($clink['label'])) { continue; }

            $item = [
                'label' => $clink['label'],
                'type'  => $clink['type'] ?? 'page',
            ];

            if ($clink['type'] === 'custom') {
                $item['url'] = $clink['url'] ?? '#';

            } elseif ($clink['type'] === 'page_widget') {
                $item['url'] = '#'.$clink['widget'] ?? null;

            } else {
                $item['url'] = url($clink['page'] ?? '/');
            }

            $cleanCompanyLinks[] = $item;
        }

        $quickLinks = $footerData['quick_links'] ?? [];
        $cleanQuickLinks = [];

        foreach ($quickLinks as $qlink) {
            if (empty($qlink['label'])) { continue; }

            $item = [
                'label' => $qlink['label'],
                'type'  => $qlink['type'] ?? 'page',
            ];

            if ($qlink['type'] === 'custom') {
                $item['url'] = $qlink['url'] ?? '#';

            } elseif ($qlink['type'] === 'page_widget') {
                $item['url'] = '#'.$qlink['widget'] ?? null;

            } else {
                $item['url'] = url($qlink['page'] ?? '/');
            }

            $cleanQuickLinks[] = $item;
        }

        $widget->content = [
            'bg_type'      => $footerData['bg_type'] ?? 'color',
            'bg_color'     => $footerData['bg_color'] ?? '#ffffff',
            'bg_image'     => $path,
            'bg_image_position' => $footerData['bg_image_position'] ?? null,
            'bg_image_repeat'   => $footerData['bg_image_repeat'] ?? null,
            'bg_image_size'     => $footerData['bg_image_size'] ?? null,
            'overlay_color' => $footerData['overlay_color'] ?? '#000000',
            'overlay_opacity' => $footerData['overlay_opacity'] ?? 0,
            'bg_gradient_start' => $footerData['bg_gradient_start'] ?? null,
            'bg_gradient_end'   => $footerData['bg_gradient_end'] ?? null,
            'bg_gradient_direction' => $footerData['bg_gradient_direction'] ?? null,
            'description' => $footerData['description'] ?? '',
            'text_color' => $footerData['text_color'] ?? '#000000',
            'background_color' => $footerData['background_color'] ?? '#ffffff',
            'show_logo' => $request->boolean('footer.show_logo') ?? false,
            'show_payment_gateway' => $request->boolean('footer.show_payment_gateway') ?? false,
            'show_newsletter' => $request->boolean('footer.show_newsletter') ?? false,
            'social_links_align' => $footerData['social_links_align'] ?? 'start',
            'icon_color' => $footerData['icon_color'] ?? '#000000',
            'social_links' => $cleanSocialLinks,
            'company_links' => $cleanCompanyLinks,
            'quick_links' => $cleanQuickLinks,
            'copyright_text' => $footerData['copyright_text'] ?? '',
        ];

        $widget->settings = [
            'width' => $request->input('footer_settings.width', 'full'),
        ];
    }

}
