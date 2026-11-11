# Laraigniter QR Code

Fluent QR codes for Laraigniter. The `QrCode` facade builds a code through BaconQrCode and returns the image bytes.

## Installation

```powershell
composer require lara-igniter/laraigniter-qrcode
```

Register the provider and the facade alias in `config/hooks.php`:

```php
$hook = Hooks::autoload([
    'providers' => [
        Laraigniter\QrCode\QrCodeServiceProvider::class,
    ],

    'aliases' => [
        'QrCode' => Laraigniter\QrCode\Facades\QrCode::class,
    ],
]);
```

The provider runs on `post_controller_constructor` and registers the built-in presets. PNG output uses the GD renderer for these shapes. Imagick is used for a plain PNG only when the extension is installed.

Publish the style preview images into the public folder:

```powershell
php artisan vendor:publish --tag=qrcode-assets
```

The files land in `public/images/laraigniter-qrcode`.

Publish the default settings when an application should override them:

```powershell
php artisan vendor:publish --tag=qrcode-config
```

The file lands in `config/qrcode.php`. `QrCode::generate()` uses those values. `size()`, `color()`, `preset()`, `merge()`, and `fitLogo()` replace them. `logo.enabled` is `true` or `false`. When it is `true`, `generate()` draws `logo.path`. `fitLogo(false)` leaves the code without a logo. `presets` lists the classes `preset()` can resolve.

## Generate a QR code

Each static call starts a new generator.

```php
use Laraigniter\QrCode\Facades\QrCode;

$png = QrCode::format('png')
    ->size(110)
    ->generate('https://example.com');
```

`generate()` returns the image contents. Pass a path as the second argument to write that file as well.

In a Blade view:

```html
<img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->size(110)->generate('https://example.com')) !!}">
```

## Format, size, and color

Formats are `svg` (the default), `png`, and `eps`.

```php
QrCode::format('png')
    ->size(200)
    ->margin(1)
    ->color(0, 123, 255)
    ->backgroundColor(255, 255, 255)
    ->errorCorrection('H')
    ->generate('https://example.com');
```

`errorCorrection()` accepts `L`, `M`, `Q`, or `H`.

Module shapes from `style()` are `square`, `dot`, and `round`. The second argument is the module scale, between `0` and `1`.

```php
QrCode::format('png')->style('round', 0.6)->generate('https://example.com');
```

`shape()` selects a neighbor-aware pattern. Connected modules stay joined. Free ends and corners are rounded. `finder()` draws the three corner eyes as `square`, `dot`, or `extra-rounded`.

```php
QrCode::format('png')
    ->shape('extra-rounded')
    ->finder('extra-rounded')
    ->generate('https://example.com');
```

`clearCenter()` removes the modules behind a center image. Pair it with error correction `H`. The ratio is the fraction of the matrix that stays empty, between `0` and `1`.

```php
QrCode::format('png')
    ->errorCorrection('H')
    ->clearCenter(0.34)
    ->merge('public/images/logo.png', 0.2)
    ->generate('https://example.com');
```

## Presets

`preset()` applies a style registered on `app('qrcode.styles')`. The package registers:

| Name | Result |
|------|--------|
| `simple` | Black square modules on white |
| `color` | Teal square modules on white |
| `round` | Black rounded modules on white |
| `dots` | Separate circular modules and circular eyes |
| `rounded` | Modules that join on straight runs and round on corners |
| `extra-rounded` | The same joins with a larger outer curve |
| `classy` | Square runs with one rounded corner |
| `classy-rounded` | Square runs with one large rounded corner |

Call `size()` after `preset()` when the caller should choose the pixel size.

```php
QrCode::format('png')->preset('color')->size(110)->generate('https://example.com');
```

An unknown name falls back to `simple`.

## Application style

Extend a package style and register it from the application provider, after `QrCodeServiceProvider`:

```php
use Laraigniter\QrCode\Generator;
use Laraigniter\QrCode\StyleRegistry;
use Laraigniter\QrCode\Styles\ColorStyle;

class BrandStyle extends ColorStyle
{
    public function name(): string
    {
        return 'brand';
    }

    public function label(): string
    {
        return 'Brand';
    }

    protected function red(): int
    {
        return 0;
    }

    protected function green(): int
    {
        return 123;
    }

    protected function blue(): int
    {
        return 255;
    }

    public function apply(Generator $generator): Generator
    {
        return parent::apply($generator)->errorCorrection('H');
    }
}
```

```php
$styles = app('qrcode.styles');

if ($styles instanceof StyleRegistry) {
    $styles->register(new BrandStyle());
}
```

```php
QrCode::preset('brand')->size(110)->generate('https://example.com');
```

## Center image

`merge()` and `mergeString()` place an image in the center of a PNG. Call `fitLogo()` before `merge()` so the modules stop at the visible shape of the image, with a thin gap. `clearCenter()` removes a square instead. Use error correction `H`. `merge()` reads a path relative to the application root unless the third argument is `true`.

```php
QrCode::format('png')
    ->errorCorrection('H')
    ->fitLogo()
    ->merge('public/images/logo.png', 0.42)
    ->generate('https://example.com');
```

## License

MIT — see [LICENSE.md](LICENSE.md).
