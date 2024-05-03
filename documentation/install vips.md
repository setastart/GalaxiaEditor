## Install Vips

### MacOs
Install with homebrew
```
brew install vips
```
Prevent updates with ```brew pin vips```

### Linux
sudo apt install build-essential


First download vips, extract, check dependencies with ./configure
```
wget https://github.com/libvips/libvips/releases/download/v8.14.2/vips-8.14.2.tar.gz
tar xf vips-8.14.2.tar.gz
cd vips-8.14.2.tar.gz
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
apt install libwebp-dev

apt install pngquant
apt install libimagequant-dev
```
check if everything is correct and install
```
meson setup build-dir
cd build-dir
ninja
ninja test
ninja install
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
