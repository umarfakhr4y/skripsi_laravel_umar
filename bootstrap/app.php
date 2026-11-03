<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Polyfill untuk server hosting yang belum mengaktifkan ekstensi PHP fileinfo
if (!class_exists('finfo')) {
    if (!defined('FILEINFO_NONE')) define('FILEINFO_NONE', 0);
    if (!defined('FILEINFO_SYMLINK')) define('FILEINFO_SYMLINK', 2);
    if (!defined('FILEINFO_MIME')) define('FILEINFO_MIME', 1040);
    if (!defined('FILEINFO_MIME_TYPE')) define('FILEINFO_MIME_TYPE', 16);
    if (!defined('FILEINFO_MIME_ENCODING')) define('FILEINFO_MIME_ENCODING', 1024);

    class finfo {
        private $flags;
        public function __construct(int $flags = FILEINFO_NONE, ?string $magic_file = null) {
            $this->flags = $flags;
        }
        public function buffer(string $string, int $flags = FILEINFO_NONE, $context = null) {
            return false;
        }
        public function file(string $filename, int $flags = FILEINFO_NONE, $context = null) {
            return false;
        }
        public function set_flags(int $flags): bool {
            $this->flags = $flags;
            return true;
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
