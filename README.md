# <img style="margin-right: 0.2em;" src="public/edit/favicon.png" alt="Setastart Logo" width="48" height="48"> GalaxiaEditor 

GalaxiaEditor is a **Content Management System** (CMS) or a Website **Content Editor**.

It is designed to be **easy to use**, **_very fast_**, and to make content editing a **great** and **effortless experience**.

Editing your website will never again feel like a chore.

---

GalaxiaEditor is developed by [**Setastart**](https://setastart.com) in Spain and Portugal.

We make **multilingual**, **_super fast_**, **accessible**, 100% **custom** websites with **proven SEO results** that our clients edit with GalaxiaEditor (this software).

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
- **WYSIWYG** rich text text editor based on [Basecamp Trix](https://trix-editor.org/).  
  The editor expands automatically to fit the text, so there is only one scrollbar in the page and all the content text is always visible.
- **Simple**, **fast** and **intuitive navigation** designed for each website.
- **Instant** and **powerful content filtering**.
- Diversify your content and connect all kinds of things easily with **multiple fields**.


## Screenshots

![Screenshot - List view](documentation/screenshot/screenshot-list.png) List view with instant filters, tags and connections to other content.

![Screenshot - Item edit](documentation/screenshot/screenshot-item.png) Item edit with side-by-side translations, wysiwyg rich text editor.

![Screenshot - Multiple field](documentation/screenshot/screenshot-fields.png)  Multiple fields customized for each website.

![Screenshot - Image selecto](documentation/screenshot/screenshot-imageselect.png) Image selector with instant filters, multiple selection and showing images that are already in use.


## Benefits for developers

### Total separation of the editor code from your website code.
- Develop each of your PHP 8 websites **any way you like**, using your favorite tools and packages that best suit for each different website needs.
- Use one GalaxiaEditor for **multiple websites** on the same server, simplifying upgrades, optimizing server resources, reducing memory usage and taking advantage of PHP OPCache and preloading.
- Each of your websites uses its own MySQL database and stores its code, assets, cache files and uploaded images **separately from one another**.
- Update GalaxiaEditor using `git pull` as all the code and functionality for your website is outside of GalaxiaEditor directory.

### Autoloaders and dependencies included.
- Allowing you to use a different [Composer](https://getcomposer.org/) for every website you build.
- An autoloader with `Galaxia` and `GalaxiaEditor` namespaces used by GalaxiaEditor.
- An autoloader with just the `Galaxia` core helper classes to be used by your website code.
- Automatic High quality and fast image resizing and great image compression using [libvips](https://github.com/libvips/libvips)

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
- Special `dev` permission to access dev tools and info.
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


## Who is this software for?

**GalaxiaEditor** is made for **web developers** who whish to **build custom websites** the way they prefer, and to provide an editor to make content management easy for themselves, their organization members or clients.

> **Warning**  
> **Only** allow access to the editor to **the people you trust**, like the members of your organization or employees.   
>
> GalaxiaEditor is **not** designed to accept **public** user registrations or logins.  
> 
> All functionality that accepts **user registrations** (user comments, shopping and ecommerce, etc.) **should be done outside GalaxiaEditor**, in your website's code and not use GalaxiaEditor user or session tables.  

### What GalaxiaEditor is NOT designed for:

- Real-time colaborative editing.
- Plugins where the users can install or manage website or editor functionality.
- Low code needs. GalaxiaEditor is made to be programmed by developers and configured through code.
- Public user registrations and logins into the editor. Please read the Warning above.

If anything mentioned above is necessary for your needs, you might consider other software better suited for your case.


## Developing GalaxiaEditor Websites 

In order to develop websites for **GalaxiaEditor**, you are required to know programming in the **PHP 8** language, have **MySQL** language, database design and administration knowledge, **nginx** server configuration, **git** and general **shell** knowledge (know how to use a terminal) for software installation and configuration.

Supported operating systems are **MacOS** and **Linux**.  
Other operating systems where you can meet the software requirements could also work but are untested and unsupported.  


### Software requirements and dependencies

GalaxiaEditor is built on **PHP 8** and developed on the latest active PHP version (currently PHP 8.1).  
Older versions may work 

- PHP 8.1
- MySQL 8.0
- Redis
- [libvips](https://github.com/libvips/php-vips-ext)
- [php-vips-ext](https://github.com/libvips/php-vips-ext)
- NGINX


## Instalation instructions

Todo: add the following instructions - .md files under ./doc 

- Setup the Development environment and install required software.
- Install GalaxiaEditor.
- Creating your first Galaxia website.
  - Code setup
  - MySQL database setup
  - Redis database setup
  - Web server setup
- Configure GalaxiaEditor for your website.
- Updating GalaxiaEditor.
- Deploying GalaxiaEditor on a production server.

Todo: create a minimal website in a new repository and add a link to it.
Todo: create a general example website with basic functionality in a new repository and add a link to it.


## Theory of Operation

In order to explain how GalaxiaEditor works, Let's suppose we have a server with 2 websites that use GalaxiaEditor: `example1.com` and `example2.net`.

### Directory setup

GalaxiaEditor and your websites reside in separate directories inside your web serving directory, `/var/www/galaxia` in this example:

    /var/www/galaxia
    ├── _GalaxiaEditor
    ├── example1.com
    └── example2.net

In this setup, the 3 example websites will share the same GalaxiaEditor code 

No data is ever stored inside `_GalaxiaEditor` and each website stores and manages its own data independently.

In this example the GalaxiaEditor directory starts with an `_` (underscore) to appear first in directory listings, this is a convention to make development easier, but it could have any other name.

> **Note**  
> You are not supposed to change anything inside _GalaxiaEditor directory.  
> This way you can update GalaxiaEditor with just a `git pull`.

### Request entry points or front controllers (index.php)

Each website has a separate entry point or front controller:  
`example1.com/public/index.php`  
`example2.net/public/index.php`

GalaxiaEditor has its own front controller in  `_GalaxiaEditor/public/edit/index.php`

Your web server (nginx) directs all visitors of the url `example1.com/edit` and all urls that that start with `example1.com/edit/` to the GalaxiaEditor front controller.

All the other pages of the website are directed to the `example1.com` front controller.

The same happens with example2.net and any other number of websites.


### Request handling, initialization, loading and routing

GalaxiaEditor knows for which website the request came for, so it can load that website's configuration file (`example1.com/config/app.php`), containing database credentials, languages, timezone and other data.

Then if it finds a session, it loads it, authenticates the user and loads the user's data.  
If there isn't a session or the user fails to authenticate, it shows the login page or the website's error page.

With the user data loaded, GalaxiaEditor parses the website's editor configuration (`example1.com/config/editor.php`), strips all parts of it that the user hasn't permission to, and validates it in order to give developers hints of what is misconfigured.

With the editor configuration loaded, it proceeds to route and serve the request.


### Side effects

When a person uses GalaxiaEditor to edit the content of a website, **one** or **more** of the following **side effects** may happen:

- The website MySQL database content of the website is updated.
- Images are uploaded, resized, renamed or deleted from website/var/media/image directory of the website, along with image metadata files. 
- Cache files are generated or deleted from website/var/cache
- The Redis database is updated. Each website uses its own prefix;


## Thank you for reading

If you like what you've read and are looking for a professionally made website that you can manage with ease for your organization or business, contact us.

Visit [<img src="public/edit/gfx/icon/icon-setastart-64.png" alt="Setastart Logo" width="16" height="16"> setastart.com](https://setastart.com) for more info and contacts.

Rest assured your website is in good hands while you focus on your business or mission.


## License

GalaxiaEditor is licensed under the European Union Public License version 1.2 (EUPL-1.2)

It contains open source code from:
- [FastRoute](https://github.com/nikic/FastRoute)
- [PHP Redis implementation](https://github.com/ziogas/PHP-Redis-implementation)
- [Basecamp Trix](https://github.com/basecamp/trix)



## Copyright

© 2017-2022 Ino Detelić & Zaloa G. Ramos (setastart.com)
