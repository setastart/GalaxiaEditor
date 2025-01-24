## Install Vips

### MacOs
Install with homebrew
```
brew install vips
```
Prevent new version updates with ```brew pin vips```

### Linux
sudo apt install build-essential

First download vips, extract and install dependencies.
```
wget https://github.com/libvips/libvips/releases/download/v8.16.0/vips-8.16.0.tar.gz
tar xf vips-8.16.0.tar.gz
cd vips-8.16.0.tar.gz
```
missing dependencies
```
apt install meson ninja-build
apt install libgirepository1.0-dev

apt install pkg-config
apt install libglib2.0-dev
apt install libexpat1-dev
apt install libjpeg-turbo8-dev
apt install libpng-dev

apt install pngquant
apt install libimagequant-dev
```
check if everything is correct and install
```
meson setup
cd build
meson compile
meson test
meson install
```
vips is now installed at ```/usr/local```
vips binaries: ```/usr/local/bin/```
libvips.so ```/usr/local/lib/libvips.so```

### vips extension for php on MacOS
```
pecl install vips
```

### vips extension for php on Linux
```
apt install php-dev
pecl install vips
```

### add vips extension to php.ini
```
[VIPS]
extension="vips.so"
```
