<?php

namespace Laraigniter\QrCode;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Exception\WriterException;
use BaconQrCode\Renderer\Color\Alpha;
use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Eye\EyeInterface;
use BaconQrCode\Renderer\Eye\ModuleEye;
use BaconQrCode\Renderer\Eye\SimpleCircleEye;
use BaconQrCode\Renderer\Eye\SquareEye;
use BaconQrCode\Renderer\Image\EpsImageBackEnd;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\DotsModule;
use BaconQrCode\Renderer\Module\ModuleInterface;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use BaconQrCode\Renderer\RendererStyle\GradientType;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use BadMethodCallException;
use InvalidArgumentException;
use Laraigniter\QrCode\DataTypes\DataTypeInterface;
use Laraigniter\QrCode\Styles\SimpleStyle;
use RuntimeException;

class Generator
{
    /**
     * @var string
     */
    protected $format = 'svg';

    /**
     * @var int
     */
    protected $pixels = 100;

    /**
     * @var int
     */
    protected $margin = 0;

    /**
     * @var ErrorCorrectionLevel|null
     */
    protected $errorCorrection = null;

    /**
     * @var string
     */
    protected $encoding = Encoder::DEFAULT_BYTE_MODE_ECODING;

    /**
     * @var string
     */
    protected $style = 'square';

    /**
     * @var float|null
     */
    protected $styleSize = null;

    /**
     * @var string|null
     */
    protected $shape = null;

    /**
     * @var string
     */
    protected $finderStyle = 'square';

    /**
     * Fraction of the matrix cleared behind a center image.
     *
     * @var float
     */
    protected $centerClearance = 0.0;

    /**
     * Hide modules that the center image actually covers.
     *
     * @var bool
     */
    protected $fitLogo = false;

    /**
     * @var string|null
     */
    protected $eyeStyle = null;

    /**
     * @var ColorInterface|null
     */
    protected $color = null;

    /**
     * @var ColorInterface|null
     */
    protected $backgroundColor = null;

    /**
     * @var array<int, EyeFill>
     */
    protected $eyeColors = [];

    /**
     * @var Gradient|null
     */
    protected $gradient;

    /**
     * @var string|null
     */
    protected $imageMerge = null;

    /**
     * @var float
     */
    protected $imagePercentage = .2;

    /**
     * Gap around a fitted logo, as a fraction of the shorter logo side.
     *
     * @var float
     */
    protected $logoGap = 0.03;

    /**
     * The caller chose whether a logo is drawn.
     *
     * @var bool
     */
    protected $logoDecided = false;

    public function __construct()
    {
        $this->applyConfig();
    }

    /**
     * @param string $method
     * @param array<int, mixed> $arguments
     *
     * @return string
     */
    public function __call($method, array $arguments)
    {
        $dataType = $this->createClass($method);
        $dataType->create($arguments);

        return $this->generate(strval($dataType));
    }

    /**
     * @throws WriterException
     * @throws InvalidArgumentException
     */
    public function generate(string $text, ?string $filename = null): string
    {
        $reporting = error_reporting();
        error_reporting($reporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            if (!$this->logoDecided) {
                $this->applyConfiguredLogo();
            }

            $qrCode = $this->format === 'png' && (!class_exists(\Imagick::class) || $this->prefersMatrixRenderer())
                ? (new Renderer\GdMatrixRenderer())->render($this, $text)
                : $this->getWriter($this->getRenderer())->writeString($text, $this->encoding, $this->errorCorrection);
        } finally {
            error_reporting($reporting);
        }

        if ($this->imageMerge !== null && $this->format === 'png' && !$this->fitLogo) {
            $merger = new ImageMerge(new Image($qrCode), new Image($this->imageMerge));
            $qrCode = $merger->merge($this->imagePercentage);
        }

        if ($filename) {
            file_put_contents($filename, $qrCode);

            return $qrCode;
        }

        return $qrCode;
    }

    public function dataUri(string $text): string
    {
        $mime = 'image/svg+xml';

        if ($this->format === 'png') {
            $mime = 'image/png';
        } elseif ($this->format === 'eps') {
            $mime = 'application/postscript';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($this->generate($text));
    }

    /**
     * @return $this
     */
    public function merge(string $filepath, ?float $percentage = null, bool $absolute = false): self
    {
        if (function_exists('base_path') && !$absolute) {
            $filepath = rtrim(base_path(), '/\\') . DIRECTORY_SEPARATOR . ltrim($filepath, '/\\');
        }

        $contents = file_get_contents($filepath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read the image merged into the QR code.');
        }

        $this->imageMerge = $contents;
        $this->imagePercentage = $percentage ?? $this->imagePercentage;
        $this->logoDecided = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function mergeString(string $content, float $percentage = .2): self
    {
        $this->imageMerge = $content;
        $this->imagePercentage = $percentage;
        $this->logoDecided = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function size(int $pixels): self
    {
        $this->pixels = $pixels;

        return $this;
    }

    /**
     * @return $this
     */
    public function format(string $format): self
    {
        if (!in_array($format, ['svg', 'eps', 'png'], true)) {
            throw new InvalidArgumentException("\$format must be svg, eps, or png. {$format} is not a valid.");
        }

        $this->format = $format;

        return $this;
    }

    /**
     * Apply a named style registered by the package or the application.
     *
     * @return $this
     */
    public function preset(string $name): self
    {
        $registry = function_exists('app') ? app('qrcode.styles') : null;
        $style = $registry instanceof StyleRegistry && $registry->has($name)
            ? $registry->get($name)
            : new SimpleStyle();

        $generator = $style->apply($this);

        return $generator instanceof self ? $generator : $this;
    }

    /**
     * @return $this
     */
    public function color(int $red, int $green, int $blue, ?int $alpha = null): self
    {
        $this->color = $this->createColor($red, $green, $blue, $alpha);

        return $this;
    }

    /**
     * @return $this
     */
    public function backgroundColor(int $red, int $green, int $blue, ?int $alpha = null): self
    {
        $this->backgroundColor = $this->createColor($red, $green, $blue, $alpha);

        return $this;
    }

    /**
     * @return $this
     */
    public function eyeColor(int $eyeNumber, int $innerRed, int $innerGreen, int $innerBlue, int $outterRed = 0, int $outterGreen = 0, int $outterBlue = 0): self
    {
        if ($eyeNumber < 0 || $eyeNumber > 2) {
            throw new InvalidArgumentException("\$eyeNumber must be 0, 1, or 2.  {$eyeNumber} is not valid.");
        }

        $this->eyeColors[$eyeNumber] = new EyeFill(
            $this->createColor($innerRed, $innerGreen, $innerBlue),
            $this->createColor($outterRed, $outterGreen, $outterBlue)
        );

        return $this;
    }

    /**
     * @param int|string $startRed
     * @param int|string $startGreen
     * @param int|string $startBlue
     * @param int|string $endRed
     * @param int|string $endGreen
     * @param int|string $endBlue
     *
     * @return $this
     */
    public function gradient($startRed, $startGreen, $startBlue, $endRed, $endGreen, $endBlue, string $type): self
    {
        $type = strtoupper($type);
        $this->gradient = new Gradient(
            $this->createColor((int) $startRed, (int) $startGreen, (int) $startBlue),
            $this->createColor((int) $endRed, (int) $endGreen, (int) $endBlue),
            GradientType::$type()
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function eye(string $style): self
    {
        if (!in_array($style, ['square', 'circle'], true)) {
            throw new InvalidArgumentException("\$style must be square or circle. {$style} is not a valid eye style.");
        }

        $this->eyeStyle = $style;

        return $this;
    }

    /**
     * @return $this
     */
    public function style(string $style, float $size = 0.5): self
    {
        if (!in_array($style, ['square', 'dot', 'round'], true)) {
            throw new InvalidArgumentException("\$style must be square, dot, or round. {$style} is not a valid.");
        }

        if ($size <= 0 || $size >= 1) {
            throw new InvalidArgumentException("\$size must be between 0 and 1.  {$size} is not valid.");
        }

        $this->style = $style;
        $this->styleSize = $size;

        return $this;
    }

    /**
     * Neighbor-aware module shape used by the GD renderer.
     *
     * @return $this
     */
    public function shape(string $shape): self
    {
        if (!in_array($shape, ['square', 'dots', 'rounded', 'extra-rounded', 'classy', 'classy-rounded'], true)) {
            throw new InvalidArgumentException("\$shape must be square, dots, rounded, extra-rounded, classy, or classy-rounded. {$shape} is not a valid.");
        }

        $this->shape = $shape;

        return $this;
    }

    /**
     * Finder frame drawn around the three corner eyes.
     *
     * @return $this
     */
    public function finder(string $style): self
    {
        if (!in_array($style, ['square', 'dot', 'extra-rounded'], true)) {
            throw new InvalidArgumentException("\$style must be square, dot, or extra-rounded. {$style} is not a valid.");
        }

        $this->finderStyle = $style;

        return $this;
    }

    /**
     * Leave the center of the matrix empty so a logo does not cover modules.
     *
     * @return $this
     */
    public function clearCenter(float $ratio): self
    {
        if ($ratio < 0 || $ratio >= 1) {
            throw new InvalidArgumentException("\$ratio must be between 0 and 1. {$ratio} is not valid.");
        }

        $this->centerClearance = $ratio;

        return $this;
    }

    /**
     * Stop the modules at the visible edge of the merged image.
     *
     * @return $this
     */
    public function fitLogo(bool $fit = true): self
    {
        $this->fitLogo = $fit;
        $this->logoDecided = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function encoding(string $encoding): self
    {
        $this->encoding = strtoupper($encoding);

        return $this;
    }

    /**
     * @return $this
     */
    public function errorCorrection(string $errorCorrection): self
    {
        $errorCorrection = strtoupper($errorCorrection);
        $this->errorCorrection = ErrorCorrectionLevel::$errorCorrection();

        return $this;
    }

    /**
     * @return $this
     */
    public function margin(int $margin): self
    {
        $this->margin = $margin;

        return $this;
    }

    public function getWriter(ImageRenderer $renderer): Writer
    {
        return new Writer($renderer);
    }

    public function getRenderer(): ImageRenderer
    {
        return new ImageRenderer(
            $this->getRendererStyle(),
            $this->getFormatter()
        );
    }

    public function getRendererStyle(): RendererStyle
    {
        return new RendererStyle($this->pixels, $this->margin, $this->getModule(), $this->getEye(), $this->getFill());
    }

    public function getFormatter(): ImageBackEndInterface
    {
        if ($this->format === 'png') {
            if (!class_exists(\Imagick::class)) {
                throw new RuntimeException('The Imagick PNG renderer is unavailable. PNG output uses the GD renderer.');
            }

            return new ImagickImageBackEnd('png');
        }

        if ($this->format === 'eps') {
            return new EpsImageBackEnd();
        }

        return new SvgImageBackEnd();
    }

    public function getModule(): ModuleInterface
    {
        if ($this->style === 'dot') {
            return new DotsModule((float) $this->styleSize);
        }

        if ($this->style === 'round') {
            return new RoundnessModule((float) $this->styleSize);
        }

        return SquareModule::instance();
    }

    public function getEye(): EyeInterface
    {
        if ($this->eyeStyle === 'square') {
            return SquareEye::instance();
        }

        if ($this->eyeStyle === 'circle') {
            return SimpleCircleEye::instance();
        }

        return new ModuleEye($this->getModule());
    }

    public function getFill(): Fill
    {
        $foregroundColor = $this->color ?? new Rgb(0, 0, 0);
        $backgroundColor = $this->backgroundColor ?? new Rgb(255, 255, 255);
        $eye0 = $this->eyeColors[0] ?? EyeFill::inherit();
        $eye1 = $this->eyeColors[1] ?? EyeFill::inherit();
        $eye2 = $this->eyeColors[2] ?? EyeFill::inherit();

        if ($this->gradient) {
            return Fill::withForegroundGradient($backgroundColor, $this->gradient, $eye0, $eye1, $eye2);
        }

        return Fill::withForegroundColor($backgroundColor, $foregroundColor, $eye0, $eye1, $eye2);
    }

    public function createColor(int $red, int $green, int $blue, ?int $alpha = null): ColorInterface
    {
        if (is_null($alpha)) {
            return new Rgb($red, $green, $blue);
        }

        return new Alpha($alpha, new Rgb($red, $green, $blue));
    }

    public function pixelSize(): int
    {
        return $this->pixels;
    }

    /**
     * Fill empty builder values from config/qrcode.php when that file is loaded.
     *
     * @return void
     */
    private function applyConfig(): void
    {
        if (!$this->configFileIsLoaded()) {
            return;
        }

        $format = config('qrcode.format');

        if (is_string($format) && in_array($format, ['svg', 'eps', 'png'], true)) {
            $this->format = $format;
        }

        $size = config('qrcode.size');

        if (is_numeric($size) && (int) $size > 0) {
            $this->pixels = (int) $size;
        }

        $margin = config('qrcode.margin');

        if (is_numeric($margin) && (int) $margin >= 0) {
            $this->margin = (int) $margin;
        }

        $correction = config('qrcode.error_correction');

        if (is_string($correction) && $correction !== '') {
            $this->errorCorrection($correction);
        }

        $module = config('qrcode.module_shape');
        $moduleSize = config('qrcode.module_scale');

        if (is_string($module) && in_array($module, ['square', 'dot', 'round'], true)) {
            $scale = is_numeric($moduleSize) ? (float) $moduleSize : 0.5;
            $this->style($module, $scale > 0 && $scale < 1 ? $scale : 0.5);
        }

        $finder = config('qrcode.finder_style');

        if (is_string($finder) && in_array($finder, ['square', 'dot', 'extra-rounded'], true)) {
            $this->finder($finder);
        }

        $this->applyHexColor(config('qrcode.foreground_color'), false);
        $this->applyHexColor(config('qrcode.background_color'), true);

        $percentage = config('qrcode.merge_percentage');

        if (is_numeric($percentage) && (float) $percentage > 0 && (float) $percentage < 1) {
            $this->imagePercentage = (float) $percentage;
        }

        $gap = config('qrcode.logo.gap');

        if (is_numeric($gap) && (float) $gap >= 0) {
            $this->logoGap = (float) $gap;
        }
    }

    /**
     * Draw the configured logo when the caller left that choice open.
     *
     * @return void
     */
    private function applyConfiguredLogo(): void
    {
        if (!$this->configFileIsLoaded() || !$this->configLogoEnabled()) {
            return;
        }

        $configuredPath = config('qrcode.logo.path');
        $path = $this->configuredLogoFile(is_string($configuredPath) ? $configuredPath : '');

        if ($path === '') {
            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            return;
        }

        $percentage = config('qrcode.logo.percentage');

        $this->imageMerge = $contents;
        $this->fitLogo = true;

        if (is_numeric($percentage) && (float) $percentage > 0 && (float) $percentage < 1) {
            $this->imagePercentage = (float) $percentage;
        }

        if ($this->errorCorrection === null) {
            $this->errorCorrection('H');
        }
    }

    /**
     * @return bool
     */
    private function configFileIsLoaded(): bool
    {
        if (!function_exists('app') || !function_exists('config')) {
            return false;
        }

        try {
            $repository = app('config');
        } catch (\Throwable $e) {
            return false;
        }

        if (!is_object($repository) || !isset($repository->_config_paths)) {
            return false;
        }

        foreach ((array) $repository->_config_paths as $path) {
            $file = rtrim((string) $path, '/\\') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'qrcode.php';

            if (is_file($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function configLogoEnabled(): bool
    {
        $enabled = config('qrcode.logo.enabled');

        return $enabled === true || $enabled === 1 || $enabled === '1';
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function configuredLogoFile(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));

        if ($path === '') {
            return '';
        }

        if (is_file($path)) {
            return $path;
        }

        $candidates = [];

        if (function_exists('public_path')) {
            $candidates[] = public_path($path);
        }

        if (function_exists('base_path')) {
            $candidates[] = base_path($path);
        }

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @param mixed $color
     * @param bool $background
     *
     * @return void
     */
    private function applyHexColor($color, bool $background): void
    {
        if (!is_string($color)) {
            return;
        }

        $hex = ltrim($color, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return;
        }

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        if ($background) {
            $this->backgroundColor($red, $green, $blue);

            return;
        }

        $this->color($red, $green, $blue);
    }

    public function marginSize(): int
    {
        return $this->margin;
    }

    public function moduleStyle(): string
    {
        return $this->style;
    }

    public function moduleStyleSize(): float
    {
        return $this->styleSize ?? 0.5;
    }

    public function moduleShape(): string
    {
        return $this->shape ?? $this->style;
    }

    public function finderStyle(): string
    {
        return $this->finderStyle;
    }

    public function centerClearance(): float
    {
        return $this->centerClearance;
    }

    public function fitsLogo(): bool
    {
        return $this->fitLogo && is_string($this->imageMerge) && $this->imageMerge !== '';
    }

    public function mergedImage(): ?string
    {
        return $this->imageMerge;
    }

    public function imagePercentage(): float
    {
        return $this->imagePercentage;
    }

    public function logoGap(): float
    {
        return $this->logoGap;
    }

    public function prefersMatrixRenderer(): bool
    {
        return $this->centerClearance > 0
            || $this->fitLogo
            || $this->finderStyle !== 'square'
            || !in_array($this->moduleShape(), ['square', 'dot', 'round'], true);
    }

    public function correctionLevel(): ?ErrorCorrectionLevel
    {
        return $this->errorCorrection;
    }

    public function characterEncoding(): string
    {
        return $this->encoding;
    }

    public function foregroundColor(): ColorInterface
    {
        return $this->color ?? new Rgb(0, 0, 0);
    }

    public function background(): ColorInterface
    {
        return $this->backgroundColor ?? new Rgb(255, 255, 255);
    }

    /**
     * @return array<int, EyeFill>
     */
    public function eyeFills(): array
    {
        return $this->eyeColors;
    }

    protected function createClass(string $method): DataTypeInterface
    {
        $class = $this->formatClass($method);

        if (!class_exists($class)) {
            throw new BadMethodCallException();
        }

        return new $class();
    }

    protected function formatClass(string $method): string
    {
        return 'Laraigniter\\QrCode\\DataTypes\\' . ucfirst($method);
    }
}
