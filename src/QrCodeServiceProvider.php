<?php

namespace Laraigniter\QrCode;

use Elegant\Contracts\Hook\Boot;
use Elegant\Contracts\Hook\PostControllerConstructor;
use Elegant\Support\ServiceProvider;
use Laraigniter\QrCode\Styles\Style;

class QrCodeServiceProvider extends ServiceProvider implements Boot, PostControllerConstructor
{
    /**
     * @return void
     */
    public function boot(): void
    {
        if (!is_cli()) {
            return;
        }

        $this->publishes([
            dirname(__DIR__) . '/resources/images/qrcode' => public_path('images/laraigniter-qrcode'),
        ], 'qrcode-assets');

        $this->publishes([
            dirname(__DIR__) . '/config/qrcode.php' => base_path('config/qrcode.php'),
        ], 'qrcode-config');
    }

    /**
     * @param mixed $params
     *
     * @return void
     */
    public function postControllerConstructor(&$params)
    {
        $this->registerConfig();

        $styles = new StyleRegistry();

        foreach ($this->styleClasses() as $class) {
            $style = new $class();

            if ($style instanceof Style) {
                $styles->register($style);
            }
        }

        app('qrcode.styles', $styles);
        app('qrcode', new Generator());
    }

    /**
     * @return void
     */
    public function registerConfig(): void
    {
        if (!function_exists('get_instance')) {
            return;
        }

        try {
            if (!get_instance()) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $this->mergeConfigFrom(dirname(__DIR__) . '/config/qrcode.php', 'qrcode');
    }

    /**
     * @return array<int, class-string<Style>>
     */
    private function styleClasses(): array
    {
        $configured = function_exists('config') ? config('qrcode.presets') : [];
        $classes = [];

        if (is_array($configured)) {
            foreach ($configured as $class) {
                if (is_string($class) && class_exists($class) && is_subclass_of($class, Style::class)) {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }
}
