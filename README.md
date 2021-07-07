
# http-file-dispatcher

A simple library to serve static image files & documents over HTTP/S via PHP.


## Example

An OctoberCMS frontend plugin:

```php
use Nfreear\HttpFileDispatcher\FileDispatcher;

class Plugin extends PluginBase
{
    // ...

    public function boot()
    {
        // ...

        App::before(function () {

            FileDispatcher::debug(__METHOD__);

            $dispatcher = new FileDispatcher();

            $dispatcher->setUriRequestPrefix('/file?f=');

            $dispatcher->setFilePath(__DIR__ . '/../../../themes/applaud/content/static-pages/files/');

            $dispatcher->run();
        });

        // ...
    }

    // ...
}
```