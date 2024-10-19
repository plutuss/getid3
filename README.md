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
- fromUrl()
 
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
    // The default disk value is taken from the .env file FILESYSTEM_DISK
      $media = MediaAnalyzer::fromLocalFile('/video.mov');
      
      $media->getAllInfo();  

          // OR
      $media = MediaAnalyzer::fromLocalFile(
             path: 'files/video.mov',
             disk: 'public',  // "local", "ftp", "sftp", "s3"
         );
         
      $media->getAllInfo();  
         
         
         // Request file
      $media = MediaAnalyzer::uploadFile($request->file('video'));
      
      $media->getAllInfo();
  
    }
  
}

```

- Easy to use: Just pass a URL and get structured metadata
- Supports multiple media types: Images, videos, and audio files
```php

     $url = 'https://www.example.com/filename.mp3';
     
     $media = MediaAnalyzer::fromUrl($url)
     
     $media->getAllInfo(); 
     
     // or
     $media->getNestedValue('array.key')  

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
    
    $media->getNestedValue('array.key')

```

- You can also refer to the property  

```php

  $media = MediaAnalyzer::uploadFile($request->file('video'))
  
      "video" => array:11 [▶]
      "warning" => array:5 [▶]
      "comments" => array:1 [▶]
      "encoding" => "UTF-8"
      "mime_type" => "video/quicktime"
      "quicktime" => array:11 [▶]
      "playtime_seconds" => 9.56
      "bitrate" => 50294133.891213
      "playtime_string" => "0:10"

    $media->video;
    
    $media->playtime_seconds;
    
    $media->playtime_string;
    
    $media->mime_type;

```



- The **getNestedValue()** method retrieves a value from a deeply nested array using "dot" notation

```php
    $media = MediaAnalyzer::uploadFile($request->file('video'))

          "avdataoffset" => 48
          "avdataend" => 60101538
          "fileformat" => "quicktime"
          "video" => array:11 [▼
            "dataformat" => "quicktime"
            "resolution_x" => 1920.0
            "resolution_y" => 1080.0
            "codec" => "H.264"
            "bits_per_sample" => 24
            "frame_rate" => 25.0
            "bitrate" => 50294133.891213
            "compression_ratio" => 0.040424168829743
          ]
          "warning" => array:5 [▶]
          "comments" => array:1 [▶]


    $media->getNestedValue('video.codec')  // H.264
    $media->getNestedValue('video.resolution_x')   //  1920.0
```

