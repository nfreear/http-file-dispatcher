<?php namespace Nfreear\HttpFileDispatcher;

//namespace IET_OU\Frontend\Classes;

/**
 * A simple library to serve static files & documents over HTTP/S via PHP.
 *
 * @copyright © Nick Freear, 25 May 2016.
 * @link  https://gist.github.com/nfreear/742cf6839c871467df0f020b349ef15e
 * @link  http://localhost/applaud/file?f=APPLAuD%20Principles.pdf  Example 1
 * @link  http://localhost/applaud/file?f=Fig%201.jpg  Example 2
 */

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;

class FileDispatcher implements LoggerAwareInterface
{
    const URI_REQUEST_PREFIX = '/file?=';
    const FILE_PATH = '%s/../../../../path/to/files/';

    const PREFIX_REGEX = '/.+[=\?\/]$/';
    // Allow short two-character filenames!
    const URI_REGEX = '/[=\?\/](?P<file>\w[\w%_\-]*\w\.(?P<ext>\w{2,4}))$/';
    const ALLOW_EXT_REGEX = '/^(pdf|docx?|png|jpg)$/i';
    const DISP_INLINE_REGEX = '/^(pdf|png|jpg)$/i';

    protected $uri_request_prefix = self::URI_REQUEST_PREFIX;
    protected $file_path = self::FILE_PATH;
    protected $logger;

    public function setUriRequestPrefix($prefix)
    {
        if (! preg_match(self::PREFIX_REGEX, $prefix)) {
            self::debug([ 'ERROR', 'Bad URI request prefix', $prefix ]);
            throw new InvalidArgumentException('Bad URI request prefix: '. $prefix);
        }
        $this->uri_request_prefix = $prefix;
    }

    public function setFilePath($filePath)
    {
        $this->file_path = $filePath;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        self::debug(__METHOD__);

        $request_uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL);
        //$file_param  = filter_input(INPUT_GET, 'file', FILTER_SANITIZE_URL);

        if (false === strpos($request_uri, $this->uri_request_prefix)) {
            self::debug('no prefix match');
            return;
        }

        if (preg_match(self::URI_REGEX, $request_uri, $matches)) {
            $file = urldecode($matches[ 'file' ]);
            $file_ext = $matches[ 'ext' ];
            $file_path = sprintf($this->file_path, __DIR__) . $file;

            if (! preg_match(self::ALLOW_EXT_REGEX, $file_ext)) {
                return $this->error('Bad file extension', $file, 400);
            }

            if (! file_exists($file_path)) {
                return $this->error('File not found', $file, 404);
            }

            self::dispatch($file, $file_path, $file_ext);
        } else {
            self::debug('no uri match');
        }
    }

    protected static function dispatch($filename, $file_path, $file_ext)
    {
        $info = (object) [
            'file'  => $filename,
            'bytes' => filesize($file_path),
            'mimetype' => self::mimetype($file_path),
            'disposition' => preg_match(self::DISP_INLINE_REGEX, $file_ext) ? 'inline' : 'attachment',
        ];
        $contents = file_get_contents($file_path);

        self::debug([ 'OK', $info ]);

        header('Content-Type: ' . $info->mimetype);
        header('Content-Disposition: ' . sprintf('%s; filename=%s', $info->disposition, $filename));

        echo $contents;
        exit;
    }

    /**
     * @link http://stackoverflow.com/questions/23287341/how-to-get-mime-type-of-a-file-in-php-5-5
     */
    protected static function mimetype($file_path)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $mimetype;
    }

    protected function error($message, $file, $code)
    {
        self::debug([ 'ERROR', $code, $message, $file ]);
        if ($this->logger) {
            return $this->logger->error($message, [ 'file' => $file, 'http_code' => $code ]);
        }
        if (class_exists('\\Response')) {  //&& method_exists('\\Response', 'make')) {
            // https://octobercms.com/forum/post/returning-404-from-a-component
            return \Response::make("$message: $file", $code);
        }
        if (class_exists('\\App')) {
            return \App::abort($code, "$message: $file");
        }
        throw new InvalidArgumentException("HTTP Error. $message: $file", $code);
    }

    public static function debug($obj)
    {
        static $count = 0;
        header(sprintf('X-File-Dispatcher-%02d: %s', $count, json_encode($obj)));
        $count++;
    }
}
