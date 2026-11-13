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

The provider loads `config/qrcode.php` from the package. Publish a copy when the application should replace those defaults:

```powershell
php artisan vendor:publish --tag=qrcode-config
```

The copy lands in the application `config/qrcode.php` and replaces the package file. A method such as `size()`, `color()`, `preset()`, `merge()`, or `fitLogo()` still wins over the config for that call.

| Key | Default | Used when |
|-----|---------|-----------|
| `format` | `svg` | `format()` was not called. `svg`, `png`, or `eps`. |
| `size` | `100` | `size()` was not called. Width and height in pixels. |
| `margin` | `0` | `margin()` was not called. Quiet zone in modules. |
| `error_correction` | `L` | `errorCorrection()` was not called. `L`, `M`, `Q`, or `H`. |
| `preset` | `simple` | The name an application can pass to `preset()`. `generate()` does not apply it on its own. |
| `foreground_color` | `#000000` | `color()` was not called. `#RGB` or `#RRGGBB`. |
| `background_color` | `#ffffff` | `backgroundColor()` was not called. `#RGB` or `#RRGGBB`. |
| `module_shape` | `square` | `style()` was not called. `square`, `dot`, or `round`. |
| `module_scale` | `0.5` | Scale of `dot` and `round` modules, between `0` and `1`. |
| `finder_style` | `square` | `finder()` was not called. `square`, `dot`, or `extra-rounded`. |
| `merge_percentage` | `0.2` | `merge()` or `mergeString()` is called without a percentage. |
| `logo.enabled` | `false` | `true` draws `logo.path` when the caller did not call `merge()`, `mergeString()`, or `fitLogo()`. |
| `logo.path` | `images/logo.png` | A public file or an absolute path. |
| `logo.percentage` | `0.42` | Fraction of the QR width occupied by that logo. |
| `logo.gap` | `0.03` | Clear space around the visible logo, as a fraction of its shorter side. |
| `presets` | the eight built-in styles | Classes `preset()` can resolve. The class must extend `Laraigniter\QrCode\Styles\Style`. |

`logo.enabled` set to `false`, and `fitLogo(false)`, both leave the code without a logo. A center logo needs error correction `H`. When `logo.enabled` is `true` and the caller did not set a level, `generate()` selects `H`.

## Generate a QR code

Each static call starts a new generator.

```php
use Laraigniter\QrCode\Facades\QrCode;

$png = QrCode::format('png')
    ->size(110)
    ->generate('https://example.com');
```

`generate()` returns the image contents. Pass a path as the second argument to write that file as well. `dataUri()` returns the same image as a base64 data URI.

```php
QrCode::format('png')->size(110)->dataUri('https://example.com');
```

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

| Level | Restored codewords |
|-------|--------------------|
| `L` | about 7% |
| `M` | about 15% |
| `Q` | about 25% |
| `H` | about 30% |

The fourth argument of `color()` and `backgroundColor()` is the alpha channel. `0` is transparent and `100` is opaque.

```php
QrCode::format('png')->color(255, 0, 0, 25)->backgroundColor(255, 255, 255)->generate('https://example.com');
```

`gradient()` paints the dark modules from one RGB color to another. The type is `vertical`, `horizontal`, `diagonal`, `inverse_diagonal`, or `radial`. SVG, EPS, and an Imagick PNG use it. A PNG drawn by GD keeps the solid foreground color.

```php
QrCode::format('svg')
    ->gradient(255, 0, 0, 0, 0, 255, 'vertical')
    ->generate('https://example.com');
```

`eyeColor()` colors one of the three corner eyes. The eye number is `0`, `1`, or `2`. The first RGB triple is the inner module and the second is the outer frame.

```php
QrCode::format('png')->eyeColor(0, 255, 0, 0, 0, 0, 0)->generate('https://example.com');
```

`eye()` draws those eyes as `square` or `circle` on the SVG, EPS, and Imagick renderers. `finder()` is the GD equivalent and accepts `square`, `dot`, or `extra-rounded`.

```php
QrCode::format('svg')->eye('circle')->generate('https://example.com');
```

`encoding()` selects the character set passed to BaconQrCode. The default is `ISO-8859-1`. Use `UTF-8` for characters outside that set. BaconQrCode also accepts `ISO-8859-2` through `ISO-8859-16`, `SHIFT-JIS`, `WINDOWS-1250`, `WINDOWS-1251`, `WINDOWS-1252`, `WINDOWS-1256`, `UTF-16BE`, `ASCII`, `GBK`, and `EUC-KR`.

```php
QrCode::encoding('UTF-8')->generate('Κείμενο');
```

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

`merge()` and `mergeString()` place an image in the center of a PNG. Call `fitLogo()` before `merge()` so the modules stop at the visible shape of the image, with a thin gap. `clearCenter()` removes a square instead. Use error correction `H`. `merge()` reads a path relative to the application root unless the third argument is `true`. `mergeString()` reads the PNG bytes, which is the form a storage disk returns.

```php
QrCode::format('png')
    ->errorCorrection('H')
    ->fitLogo()
    ->merge('public/images/logo.png', 0.42)
    ->generate('https://example.com');
```

```php
QrCode::format('png')
    ->errorCorrection('H')
    ->mergeString($pngContents, 0.3)
    ->generate('https://example.com');
```

```php
QrCode::format('png')
    ->errorCorrection('H')
    ->merge('https://example.com/logo.png', 0.3, true)
    ->generate('https://example.com');
```

## Helpers

A helper encodes a payload and returns the image. Call `format()`, `size()`, and the other builder methods before it.

```php
QrCode::email('person@example.com', 'Subject', 'Message body');
QrCode::phoneNumber('555-555-5555');
QrCode::SMS('555-555-5555', 'Message body');
QrCode::geo(37.822214, -122.481769);
QrCode::wiFi([
    'ssid' => 'Network Name',
    'encryption' => 'WPA',
    'password' => 'secret',
    'hidden' => 'true',
]);
QrCode::size(220)->BTC('bitcoin-address', 0.0034, [
    'label' => 'Payment',
    'message' => 'Invoice 42',
    'returnAddress' => 'https://example.com/return',
]);
```

`email()` needs a valid address when the first argument is set. `wiFi()` accepts `encryption`, `ssid`, `password`, and `hidden`. `BTC()` writes a `bitcoin:` URI. The amount is optional, and the third argument may include `label`, `message`, and `returnAddress`.

`generate()` also stores any plain string. These prefixes are read by phone cameras without a helper:

| Payload | Example |
|---------|---------|
| Website | `https://example.com` |
| E-mail | `mailto:person@example.com` |
| Phone | `tel:555-555-5555` |
| SMS | `sms:555-555-5555` |
| Location | `geo:37.822214,-122.481769` |
| MeCard | `MECARD:N:Ada Lovelace;TEL:555-555-5555;EMAIL:person@example.com;` |
| vCard | a string that starts with `BEGIN:VCARD` |
| Wi-Fi | `WIFI:T:WPA;S:Network Name;P:secret;;` |

## License

MIT — see [LICENSE.md](LICENSE.md).
