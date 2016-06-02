# Cranleigh Smugmug Plugin
A wordpress plugin that connects with our Cranleigh SmugMug API

## Installation Instructions
Because this plugin uses the [phpSmug class](https://github.com/lildude/phpSmug) from [@lildude](https://github.com/lildude) you currently need to initialise that class using composer for this plugin to work. 

```sh
$ cd phpSmug/phpSmug
$ composer update
```

### But, I don't have Composer!
Well you better have a read of https://getcomposer.org/

## CSS Selectors
This plugin doesn't come pre-styled, as it's meant to drop in to your own theme and you can customise the design of it. Below are some pointers...
* The whole output is a div with the class `cs_smugmug_container`
* The title is an `<h3>` with the class `cs_smugmug_title`
* The image has the class `img-responsive` to fit in nicely with bootstrap
* The "View and Purchase" `<a>` at the bottom has the class `cs_smugmug_button`

## Example Usage
Use the shortcode `[smugmug]` to invoke the plugin. 

You must set the `path` parameter, which is either the full url on the link to the gallery/album or folder or if you want to cut down on the amount of text, just the filepath.

Example:
```
[smugmug path="https://cranleigh.smugmug.com/2015-2016/Sport/Athletics/Atheletics-Bracknell-April-30/"]
```
OR
```
[smugmug path="/2015-2016/Sport/Athletics/Atheletics-Bracknell-April-30/"]
```

NB: Trailing or preceding slashes are taken care of, do what you want!

## Author(s)
* Fred Bradley [@cranleighschool](https://github.com/cranleighschool)
* LilDude [@lildude](https://github.com/lildude) for phpSmug API
