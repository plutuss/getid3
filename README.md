## Installed packages

## Laravel:

- [GitHub](https://github.com/plutuss/getid3).
- This package is a wrapper around   [james-heinrich/getid3](https://packagist.org/packages/james-heinrich/getid3).

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
