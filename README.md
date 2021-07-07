
[![PHP Composer][ci-img]][ci]

# http-file-dispatcher

A simple library to serve static image files & documents over HTTP/S via PHP.

```sh
composer require nfreear/http-file-dispatcher
```

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

## License

* License: `MIT`
* Original [Gist][].

[gist]: https://gist.github.com/nfreear/742cf6839c871467df0f020b349ef15e
  "Date: 2016-05-25"
[ci-img]: https://github.com/nfreear/http-file-dispatcher/actions/workflows/php.yml/badge.svg
[ci]: https://github.com/nfreear/http-file-dispatcher/actions/workflows/php.yml
