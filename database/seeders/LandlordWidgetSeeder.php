<?php

namespace Database\Seeders;

use App\Models\landlord\HomepageWidget;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LandlordWidgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $widgets = [
            [
                'title' => 'header',
                'subtitle' => 'header',
                'type' => 'header',
                'content' => [
                    'menus' => [
                        [
                            'url' => 'http://sherazipos12.localhost:8080',
                            'type' => 'url',
                            'label' => 'Home'
                        ]
                        
                    ],
                    'logo_type' => 'site',
                    'show_title' => false,
                    'custom_logo' => null,
                    'menu_text_color' => '#000000',
                    'menu_hover_text_color' => '#ffffff',
                    'show_language_switcher' => false
                ],
                'settings' => [
                    'width' => 'container',
                    'position' => 'sticky',
                    'background_type' => 'color',
                    'background_color' => '#ffffff'
                ],
                'sort_order' => 0,
                'is_enabled' => true,
                'is_editable' => true,
                'is_global' => true
            ],
            [
                'title' => 'hero',
                'subtitle' => 'hero',
                'type' => 'section',
                'content' => [
                    
                    
                ],
                'settings' => [

                ],
                'sort_order' => 1,
                'is_enabled' => true,
                'is_editable' => true,
                'is_global' => false
            ],
            [
                'title' => 'footer',
                'subtitle' => 'footer',
                'type' => 'footer',
                'content' => [
                    "bg_type" => "color",
                    "bg_color" => "#1c0303",
                    "bg_image" => "landlord/widget/footer/q1oQ0BseSZmClMWr5ULcgYC1T5lcmZEbd4bOrWvt.png",
                    "show_logo" => true,
                    "icon_color" => "#ffffff",
                    "text_color" => "#ffffff",
                    "description" => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.",
                    "quick_links" => [
                        [
                            "url" => "http://sherazipos12.localhost:8080",
                            "type" => "page",
                            "label" => "Privacy"
                        ]
                    ],
                    "social_links" => [
                        [
                            "url" => "https://facebook.com",
                            "icon_class" => "fa-brands fa-facebook"
                        ],
                        [
                            "url" => "https://youtube.com",
                            "icon_class" => "fa-brands fa-youtube"
                        ],
                        [
                            "url" => "https://www.instagram.com/",
                            "icon_class" => "fa-brands fa-instagram"
                        ]
                    ],
                    "bg_image_size" => "cover",
                    "company_links" => [
                        [
                            "url" => "#hero",
                            "type" => "page_widget",
                            "label" => "Terms & Conditions"
                        ],
                        [
                            "url" => "http://sherazipos12.localhost:8080",
                            "type" => "page",
                            "label" => "Career"
                        ]
                    ],
                    "overlay_color" => "#000000",
                    "copyright_text" => "All right reserved by SheraziIT",
                    "bg_gradient_end" => "#3e458e",
                    "bg_image_repeat" => "no-repeat",
                    "overlay_opacity" => "0.6",
                    "show_newsletter" => true,
                    "background_color" => "#ffffff",
                    "bg_gradient_start" => "#00adee",
                    "bg_image_position" => "center",
                    "social_links_align" => "end",
                    "show_payment_gateway" => false,
                    "bg_gradient_direction" => "to top right"

                ],
                'settings' => [
                    
                ],
                'sort_order' => 0,
                'is_enabled' => true,
                'is_editable' => true,
                'is_global' => true
            ]
        ];

        foreach ($widgets as $widget) {
            if (!HomepageWidget::where('title', $widget['title'])->exists()) {
                HomepageWidget::create($widget);
            }
        }
    }
}
