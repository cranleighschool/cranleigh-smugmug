# Cranleigh Smugmug Plugin
A wordpress plugin that connects with our Cranleigh SmugMug API

## Installation Instructions
Because this plugin uses the [phpSmug class](https://github.com/lildude/phpSmug) from [@lildude](https://github.com/lildude) you currently need to initialise that class using composer for this plugin to work. 

```sh
$ cd phpSmug/phpSmug
$ composer update
```

### I don't have Composer
Well you better have a read of https://getcomposer.org/

## Example Usage
Use the shortcode `[smugmug_photos]` to invoke the plugin. 

You must set the `path` parameter, which is either the full url on the link to the gallery/album or folder or if you want to cut down on the amount of text, just the filepath.

Example:
```
[smugmug_photos path="https://cranleigh.smugmug.com/2015-2016/Sport/Athletics/Atheletics-Bracknell-April-30/"]
```
OR
```
[smugmug_photos path="/2015-2016/Sport/Athletics/Atheletics-Bracknell-April-30/"]
```

NB: Trailing or preceding slashes are taken care of, do what you want!
