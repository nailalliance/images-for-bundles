
# Compile

## Dependencies
```shell
brew install cmake autoconf automake libtool pkg-config git curl
```

## spc

```shell
mkdir spc-build && cd spc-build
curl -fsSL https://github.com/crazywhalecc/static-php-cli/releases/latest/download/spc-macos-x86_64.tar.gz -o spc.tar.gz
tar -zxvf spc.tar.gz
chmod +x spc
```

## Download
```shell
./spc download --with-php=8.3 --for-extensions="imagick,ctype,iconv,mbstring,tokenizer,xml,simplexml,dom,fileinfo,filter,posix,pcntl,phar,openssl,yaml,zip"
```

## Compile
```shell
./spc build --build-micro --build-cli "imagick,ctype,iconv,mbstring,tokenizer,xml,simplexml,dom,fileinfo,filter,posix,pcntl,phar,openssl,yaml,zlib"
```

Once finished, you will have:
- `build/root/bin/php`: A standalone PHP interpreter with Imagick.
- `build/root/bin/micro.sfx`: A special binary stub for creating single-file executables.

### Verify Imagick & LCMS

Before packaging, verify that your new static PHP binary supports Imagick and LCMS.

```shell
./build/root/bin/php -r "print_r(Imagick::getQueryConfigureOptions('*'));" | grep lcms
```

### Package into a Single Binary

To create a single executable file (like `my-app`) that contains both your code and the PHP runtime:

#### Create a PHAR (PHP Archive) of your projec

You can use a tool like `box`, but here is a simple script to bundle your project into an `app.phar`. Save this as `build_phar.php` in your project root.

```php
<?php
// build_phar.php
$pharFile = 'app.phar';
if (file_exists($pharFile)) unlink($pharFile);

$phar = new Phar($pharFile);
$phar->startBuffering();

// Add files
$phar->buildFromDirectory(__DIR__, '/^(?!(build_phar\.php|spc-build)).+$/');

// Set stub to the Symfony entry point
$defaultStub = $phar->createDefaultStub('bin/console');
$stub = "#!/usr/bin/env php\n" . $defaultStub;
$phar->setStub($stub);

$phar->stopBuffering();
echo "PHAR created: $pharFile\n";
```

Run it with your system PHP (or the one you just built):

```shell
php -d phar.readonly=0 build_phar.php
```

#### Combine PHAR with Static Runtime

Go back to your `spc-build` directory and use the `micro:combine` command.

```shell
# Assuming your project is at ../images-for-bundles-main
./spc micro:combine ../images-for-bundles-main/app.phar -O my-cli-app
```

#### Run Your Compiled Application

ou now have a file named `my-cli-app`. It is a standalone executable for x86-64 macOS.

```shell
chmod +x my-cli-app
./my-cli-app list
```

