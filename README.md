## Installed packages

## Laravel:

- [GitHub](https://github.com/plutuss/getid3).
- This package is a wrapper around   [james-heinrich/getid3](https://packagist.org/packages/james-heinrich/getid3).


> [!NOTE]
>  The getID3 package, developed by James Heinrich, is a powerful tool for analyzing metadata of audio and video files. Here are some of its capabilities:

1. **Metadata Extraction**: getID3 can extract various types of metadata from multimedia files, including information about format, codec, duration, bitrate, sample rate, and much more.
2. **Support for Various Formats**: The package supports a wide range of audio and video formats, including MP3, WAV, FLAC, OGG, AAC, WMA, AVI, MPEG, QuickTime, and many others.
3. **Tag Processing**: It is also capable of analyzing and extracting information from metadata tags, such as ID3 tags for MP3 files or similar tags for other formats.
4. **Character Encoding Detection**: getID3 can automatically detect the text encoding used in metadata, allowing for proper handling of different languages and special characters.
5. **Stream Information Retrieval**: This package can analyze the structure of multimedia files and provide information about the audio and video streams contained within them.
6. **Flexibility and Customization**: getID3 provides various options and settings for users, allowing them to customize its behavior according to the specific needs and requirements of their projects.

> [!NOTE]
> These capabilities make the getID3 package a valuable tool for developers working with multimedia files, as well as for creating applications that require analysis and processing of audio and video data.
  

```shell
 composer require plutuss/getid3-laravel
```

## Use Facade MediaAnalyzer
- uploadFile()
- fromLocalFile()
 
```php
<?php

use Plutuss\Facades\MediaAnalyzer;
use Illuminate\Http\Request;

class MediaAnalyzerController extends Controller
{
    public function index(Request $request)
    {
    
    // To add a file locally it must be in storage.
    // So that the Storage facade can read it.
     MediaAnalyzer::fromLocalFile('/video.mov')->getAllInfo();
     
    
     MediaAnalyzer::uploadFile($request->file('video'))->getAllInfo();
  
    }

}

```

- List of available methods
```php
    
    $media = MediaAnalyzer::uploadFile($request->file('video'))
    
    $media->getAllInfo();
    
    $media->getDuration();
    
    $media->comments();
    
    $media->getResolution();
    
    $media->getImage();
    
    $media->getTitle();
    
    $media->getAlbum();
    
    $media->getPlaytime();
    
    $media->getGenres();
    
    $media->getArtist();
    
    $media->getComposer();
    
    $media->getTrackNumber();
    
    $media->getCopyrightInfo();
    
    $media->getFileFormat();
    

```
