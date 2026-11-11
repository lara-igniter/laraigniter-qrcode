<?php

$config = [
    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Values used by QrCode::generate() when the caller does not set them.
    | Publish this file with "php artisan vendor:publish --tag=qrcode-config".
    | A published config/qrcode.php replaces these values. size(), color(),
    | preset(), merge(), and fitLogo() still win over the config.
    |
    */

    /**
     * Image format written by generate().
     *
     * Supported: "svg", "png", "eps".
     */
    'format' => 'svg',

    /**
     * Width and height of the image, in pixels.
     */
    'size' => 100,

    /**
     * Quiet zone around the matrix, measured in modules.
     */
    'margin' => 0,

    /**
     * Error correction level.
     *
     * Supported: "L" (about 7%), "M" (about 15%), "Q" (about 25%), "H" (about 30%).
     * A center logo needs "H".
     */
    'error_correction' => 'L',

    /**
     * Preset name used as the application fallback.
     *
     * generate() does not apply this by itself. Call preset() to draw it.
     * The name must exist in "presets" below.
     */
    'preset' => 'simple',

    /**
     * Color of the dark modules. "#RGB" or "#RRGGBB".
     */
    'foreground_color' => '#000000',

    /**
     * Color behind the matrix. "#RGB" or "#RRGGBB".
     */
    'background_color' => '#ffffff',

    /**
     * Shape of a single module when style() is not called.
     *
     * Supported: "square", "dot", "round".
     * Neighbor-aware shapes such as "dots" and "classy" come from a preset.
     */
    'module_shape' => 'square',

    /**
     * Scale of "dot" and "round" modules.
     *
     * A number between 0 and 1. Ignored by "square".
     */
    'module_scale' => 0.5,

    /**
     * Frame drawn on the three corner eyes when finder() is not called.
     *
     * Supported: "square", "dot", "extra-rounded".
     */
    'finder_style' => 'square',

    /**
     * Fraction of the QR width used by merge() when no percentage is passed.
     *
     * A number between 0 and 1.
     */
    'merge_percentage' => 0.2,

    /**
     * Center logo drawn by generate() when merge() and fitLogo() were not called.
     *
     * enabled accepts true or false. false leaves the code without a logo.
     * path is a public file, for example "images/logo.png", or an absolute path.
     * percentage is the fraction of the QR width occupied by the logo.
     * gap is the clear space around the visible logo, as a fraction of its shorter side.
     */
    'logo' => [
        'enabled' => false,
        'path' => 'images/logo.png',
        'percentage' => 0.42,
        'gap' => 0.03,
    ],

    /**
     * Preset name mapped to the style class that preset() resolves.
     *
     * The class must extend Laraigniter\QrCode\Styles\Style.
     * Remove a line to drop a preset, or add one from the application.
     */
    'presets' => [
        'simple' => \Laraigniter\QrCode\Styles\SimpleStyle::class,
        'color' => \Laraigniter\QrCode\Styles\ColorStyle::class,
        'round' => \Laraigniter\QrCode\Styles\RoundStyle::class,
        'dots' => \Laraigniter\QrCode\Styles\DotsStyle::class,
        'rounded' => \Laraigniter\QrCode\Styles\RoundedStyle::class,
        'extra-rounded' => \Laraigniter\QrCode\Styles\ExtraRoundedStyle::class,
        'classy' => \Laraigniter\QrCode\Styles\ClassyStyle::class,
        'classy-rounded' => \Laraigniter\QrCode\Styles\ClassyRoundedStyle::class,
    ],
];
