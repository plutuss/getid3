## Installed packages

## Laravel:

- [GitHub](#).
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
    
    // use Plutuss\Facades\MediaAnalyzer;
     MediaAnalyzer::fromLocalFile('/video.mov')->getAllInfo();
     
     // Request $request
     MediaAnalyzer::uploadFile($request->file('video'))->getAllInfo();
  
    }

}

```
