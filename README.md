# 1. About GalaxiaEditor

Galaxia is a web publishing platform designed for:

    - multilanguage everywhere
    - easy publishing
    - rapid development
    - feature versatility
    - correct HTML5, RSS, SITEMAP code generation
    - great performance



# Philosophy
The general idea is to separate the editor from the website.


# Theory of operation
When a person uses GalaxiaEditor to edit the content of the website, the following may happen:
- The MySQL database of the website is updated
- Images are uploaded, resized, or deleted from website/var/media/image directory of the website
- Cache files are generated or deleted from website/var/cache
- The Redis database is updated


# Dependencies
GalaxiaEditor is built on PHP and tested on the latest active PHP version (currently PHP 8.1)
MySQL 8.0
Redis
LibVips
A working web server such as NGINX



# Prerequisites to build a website that uses GalaxiaEditor to edit the content
- Know PHP
- Know Linux server administration
- 

# Installation
- 

# Screenshots

# Websites that use GalaxiaEditor
Visit setastart.com


#  



# Caching


# Licenses
GalaxiaEditor is licensed under the European Union Public License version 1.2 (EUPL-1.2)
It contains code from  



# 2. Glossary

- Reader:
    - Public that visits the website.
- Editor:
    - Creates, edits and publishes the content and metadata.
- Developer
    - Person who creates the functionality and design of the websites.
    - Has access to edit the Galaxia templates, css.
    - Has access to edit the database schema directly (to add languages, specialized fields)
    - Has privileged access to GalaxiaEditor, to setup special functionality to be used or not by editors.


# 3. Features

- Multilanguage capabilities (localization).
    - Localized titles, content, slugs, image alts everywhere.
    - Custom localization fields (set by developers, used by publishers).
    - Get any url in another language:
        - Language switcher links to the same page in other languages.
        - Meta language links for SEO.

- Content
    Can be pages, blog posts, product pages, news, events, photo, image gallery, timetables, anything that the developer defines.
    Article types:
        - Pages
            - Parent-child relationships are defined by url.
        - Articles
            - Serve various purposes, e.g. blog posts, products, news, events, etc.
            - Drafts, Publish Scheduling.

- Tags
    - Organizes content.
    - Tag groups.
    - Browse content by tags.
    - Default tags.

- Images
    - Upload auto-rotates and saves custom sizes
    - Get all images uploaded to a certain page or article for:
        - Galleries
        - Thumbnails
        - Random images

- Content editor
    - To be fully customized by the developer so the editors only use what they need
    - Trix WYSIWYG HTML editing.

- History




# 4. directory and file structure

## 4.1. Galaxia (this project)

Contains documentation and a central php classes to be used by all websites.


### 4.1.1. files and directories

- /classes/
    - Don't edit these files.
    - Internal app logic, for the editor AND the website.
    - Classes are autoloaded only when used.
    - **[todo]** Contains:
        - App (one instance as $app) used everywhere
        - User (one instance as $user) used only on /edit
        - Session (one instance as $session) used only on /edit

- /documentation/
    - readme.md (this file)
    - troubleshooting.md
    - **[todo]** nginx server setup
    - **[todo]** mysql database schema file

- /composer.json
    - Edit to declare dependencies (php packages, libraries, utils, scripts)
    - Used by composer - https://getcomposer.org

- /composer.lock
    - Don't edit this file, created automatically.

- /composer.phar (if present)
    - Composer executable

- /vendor/
    - Don't edit these files
    - Where composer stores your dependencies
    - Generated automatically with composer - https://getcomposer.org


## 4.2. Public Website

### 4.2.1. Files

- /config/app.php
    - Main app configuration
    - Sets up app, including routes


### 4.2.2. directories

- /var/cache/
    - Don't edit these files.
    - routes.cache
        - FastRoute cache
        - Check /initialization.php for route setup.
        - Delete the file to regenerate route cache automatically.

<!-- - /editor/ -->
<!--     - You can edit these files. -->
<!--     - Contains logic, layouts and templates for the editor. -->

- /templates/
    - You can edit these files.
    - Contains the logic (.php) and the views (.phtml) for the public website.
    - This is kind of like like the current theme directory in wordpress, but it's not called that way, because the CSS styling is not here

- /public/
    - index.php
        - Don't edit this file.
        - This is the entry point and should have no logic or configuration.
    - No other .php files here.
    - All the other files you can edit.
    - Global error .html files:
        - 403-global.html
        - 404-global.html
        - 50x-global.html
    - Put css, js and graphic files here.

- /scripts/
    - Logic invocated on demand by the app or user through the terminal
    - Cron scripts






# Galaixa Editor


## list (pages blogs, etc)

    - indication for draft/special/published
    - picture
    - title in every language
        - main language bolder
    - slug in every language
    - date Modified



## database specification

- if an input ends with an underscore, like 'value_', galaxia will treat it as a multilingual field.
    - 'value_' becomes 'value_pt', 'value_en', according to your site language settings
    - underscores cannot be used as a string value, only as elements (to allow cloning)

- there must be a 'pages' table.
    - there must be 'status', 'title_', 'slug_' and 'pageType' columns.

- each table must have a primary key.
    - primary key cannot end with an underscore.

- Foreign keys across tables must have the same column name.

- Use unique names for columns to be joined

- select: first column of every table must be the primary key.
    - naming: pages -> pageId, events -> eventId, etc.
    - this key will be used to build the php array that contains the data.

- special column name prefixes:
    - timestamp

- special column names:
    - status
    - slug
    - slug_
    - title_
    - pageType
    - name
    - value
    - value_
    - position



## permissions

- the array with the key 'gcPerms' declares the necessary permissions in its values.

- on every page load, G::$conf is searched for any 'gcPerms' key.
    - when the key is found the users permissions are checked.
    - the parent of any element that doesn't have permissions is deleted.





## form input types


### input


#### text inputs, basic support



    - [x] email
        - [x] maxlength
    - [x] number
        - [x] min
        - [x] max
        - [ ] step
    - [x] password
        - [x] minlength
        - [x] maxlength
    - [x] text
        - [x] maxlength
    - [x] textarea
        - [ ] maxlength
        - [ ] rows
    - [x] trix
    - [x] radio
        - [x] options are radio options
    - [x] select
        - [x] options are select options


#### future support

input checkbox "value-1"="label-1" !"value-2"="label-2"
select "value-1"="label-1" !"value-2"="label-2"

    - input checkbox
    - input file
    - select > option (optgroup not yet supported)



#### unsupported form input types (lowest priority future support)
    - datalist








# favicon
- there should be a favicon.png file inside the resources directory, to be used by the editor.
- pack various sizes into one ico file using an online tool or imagemagick with:
    - convert favicon.png -define icon:auto-resize=16,32,64 -compress zip favicon.ico





# extra

- To be used on nginx server, not tested on Apache.


© Ino Detelic 2017-2018
