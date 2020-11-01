# Galaxify Trix:

- 1.0.0 works
- 1.1.0 shift+enter does not work on safari
- 1.1.1 shift+enter does not work on safari
- 1.2.0 shift+enter does not work on safari

- use only dist/trix.js
    - Galaxia uses its own trix.css
    - Don't use the 'core' version


- the changes will:
    - use <p> instead of <div> for normal blocks
    - add h2 subheading
    - use [shift+enter] to insert a <br> and [enter] to close current scope and add a new one.


## src/trix/config/block_attributes.coffee
```
  default:
    tagName: "p"
    breakOnReturn: true
    parse: false

  heading2:
    tagName: "h2"
    terminal: true
    breakOnReturn: true
    group: false
```


## src/trix/config/lang.coffee
```
  heading2: "Subheading"
```


## src/trix/config/toolbar.coffee
```
        <button type="button" class="trix-button trix-button--icon trix-button--icon-heading-2" data-trix-attribute="heading2" title="#{lang.heading2}" tabindex="-1">#{lang.heading2}</button>
```
remove <span> with attachFiles
remove spacer <span>


## src/trix/models/block.coffee
``` line 104
  breaksOnReturn: ->
    getBlockConfig(@getLastAttribute() ? "default").breakOnReturn
```


## src/trix/models/line_break_insertion.coffee
```
  shouldInsertBlockBreak: ->
    if @block.hasAttributes() and @block.isListItem() and not @block.isEmpty()
      @startLocation.offset isnt 0
    else
+     @breaksOnReturn unless @shouldBreakFormattedBlock()
```

## Gemfile
```
gem 'uglifier', '~> 3.2'
```

### use hombebrew ruby and older bundler
brew install ruby
gem uninstall bundler
gem install bundler -v 1.17.3

### this will install dependencies locally under ./vendor
bundle install --path vendor/bundle

### setup
bin/setup

### build
bin/blade build

### run
bin/rackup
