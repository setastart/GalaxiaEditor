# <img style="margin-right: 0.2em;" src="public/edit/favicon.png" alt="Setastart Logo" width="48" height="48"> GalaxiaEditor 

GalaxiaEditor is a **Content Management System** (CMS) or a Website **Content Editor**.

It is designed to be **easy to use**, **_very fast_**, and to make content editing a **great** and **effortless experience**.

---

GalaxiaEditor is developed by [**Setastart**](https://setastart.com) in Spain and Portugal.

We make **multilingual**, **_super fast_**, **accessible**, 100% **custom** websites with **proven SEO results** that our clients edit with GalaxiaEditor.

Visit our website to read more about our work and to hire our services:  

[<img src="public/edit/gfx/icon/icon-setastart-64.png" alt="Setastart Logo" width="32" height="32"> setastart.com](https://setastart.com)

---

## Features and benefits for editors

- **Side-by-side translations**.
  - Simplify translating by showing localised content in the same view.  
- **Integrated Google Translate**™.
  - Get a **formatted translation** inside the editor with just a click.
- **Built in internationalization** (i18n) and **localisation** (l10n).
  - Integrated in the editor, without needing external tools.
  - Everything is localisable, including image alt text and slugs.
- Upload, reorder and connect **hundreds of images in minutes**.
- **WYSIWYG** text editor based on [Basecamp Trix](https://trix-editor.org/).  
  The editor expands automatically to fit the text, so there is only one scrollbar in the page and all the content text is always visible.
- **Simple**, **fast** and **intuitive navigation** designed for each website.
- **Instant** and **powerful content filtering**.
- Diversify your content and connect all kinds of things easily with **multiple fields**.


## Screenshots


## Benefits for developers

### Total separation of the editor code from your website code.
- Develop each of your PHP 8 websites **any way you like**, using your favorite tools and packages that best suit for each different website needs.
- Use one GalaxiaEditor for **multiple websites** on the same server, simplifying upgrades, optimizing server resources, reducing memory usage and taking advantage of PHP OPCache and preloading.
- Each of your websites uses its own MySQL database and stores its code, asset and uploaded images **separately from one another**.
- Update GalaxiaEditor using `git pull` as all the code and functionality for your website is outside of GalaxiaEditor directory.

### Autoloaders and dependencies included.
- Allowing you to use a different [Composer](https://getcomposer.org/) for every website you build.
- An autoloader with `Galaxia` and `GalaxiaEditor` namespaces used by GalaxiaEditor.
- An autoloader with just the `Galaxia` core helper classes to be used by your website code.
- Automatic High quality fast image resizing and great image compression using [libvips](https://github.com/libvips/libvips)

### Design your database schema according to your website needs
- Be consistent using foreign keys.
- Be efficient and performant using indices best suited for your needs.
- Store each localised version of your content in its own database column.
- Be flexible with one-to-one, one-to-many or many-to-many column relationships.
- Schema convention to simplify your code.

### Minimal dependencies and complexity
- Allows you to increase your project longevity by simplifying upgrades and being always ready for whatever future may bring.
- Allows you to build the fastest dynamic PHP websites by including only what you need.

### Editor configuration in PHP
- No more yaml, xml, toml, ini or other configuration formats or external dependencies. GalaxiaEditor expects the configuration to be a PHP array.
- Use your website constants, functions, models and code in the configuration.
- Configuration array builders help you build the array with type safety, code hinting and completion (in an IDE like PhpStorm). 
- Performant loading by caching a configuration file for every user.
- Built-in advanced permission management.
- Lists with joins from other tables, all filtrable.

### Core helper classes under `Galaxia` namespace include:

- Automatic Image resizing of only used sizes helps to save disk space and reduce computation time.
- PHP `array` and `string` file caching, taking advantage of PHP OPCache.
- Initialization, environment setup and configuration loading.
- User session and authentication.
- Routing using [FastRoute](https://github.com/nikic/FastRoute) from [Nikic](https://github.com/nikic).
- Text transformation, normalisation, transliteration and translation.
- Asset generators and builders. 
- CSS helpers and generators.
- Future proof and lightweight [PHP Redis implementation](https://github.com/ziogas/PHP-Redis-implementation) from [ziogas](https://github.com/ziogas).


## Developing GalaxiaEditor websites 

In order to develop websites for **GalaxiaEditor**, you are required to know programming in the **PHP 8** language, have **MySQL** language, database design and administration knowledge, **nginx** server configuration, and various software installation and configuration on your operating system.

Tested operating systems are **MacOS** for development, and **Linux** for development and production.  
GalaxiaEditor is built on **PHP 8** and tested on the latest active PHP version (currently PHP 8.1).


### Requirements and dependencies

- PHP 8
- MySQL 8.0
- Redis
- [libvips](https://github.com/libvips/php-vips-ext)
- [php-vips-ext](https://github.com/libvips/php-vips-ext)
- NGINX


## Theory of operation

When a person uses GalaxiaEditor to edit the content of a website, **one** or **more** of the following **side effects** may happen:

- The website MySQL database content of the website is updated.
- Images are uploaded, resized, renamed or deleted from website/var/media/image directory of the website, along with image metadata files. 
- Cache files are generated or deleted from website/var/cache
- The Redis database is updated. Each website uses its own prefix;


## License

GalaxiaEditor is licensed under the European Union Public License version 1.2 (EUPL-1.2)

It contains open source code from:
- [PHP Redis implementation](https://github.com/ziogas/PHP-Redis-implementation)
- [FastRoute](https://github.com/nikic/FastRoute)
- [Basecamp Trix](https://github.com/basecamp/trix)



## Copyright

© 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
