# cranleigh-smugmug
A wordpress plugin that connects with our cranleigh smugmug api

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