<?php

namespace CoffeeCode\Router;

/**
 * Class CoffeeCode Router
 *
 * @author Robson V. Leite <https://github.com/robsonvleite>
 * @package CoffeeCode\Router
 */
class Router extends Dispatch
{
    /** @var int|false */
    protected $cacheTime = false;

    /** @var array<string>|null allowlisted asset extensions (lowercase, no dot). null = allow all */
    protected ?array $assetsAllowExt = null;

    /** @var bool whether to follow symlinks when serving assets */
    protected bool $assetsFollowSymlinks = false;


    /**
     * Router constructor.
     *
     * @param string $projectUrl
     * @param null|string $separator
     */
    public function __construct(string $projectUrl, ?string $separator = ":")
    {
        parent::__construct($projectUrl, $separator);
    }

    /**
     * Compute a robust base URL from current request context.
     * - Honors X-Forwarded-Proto/Host/Port/Prefix (common proxies/load balancers)
     * - Falls back to HTTPS/HTTP and HTTP_HOST
     * - Includes script base directory when present (subpath deployments)
     * - Returns WITHOUT trailing slash (Router already normalizes)
     */
    public static function autoBaseUrl(): string
    {
        // Scheme
        $scheme =
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== '')
                ? explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]
                : null;
        if (!$scheme) {
            $https = $_SERVER['HTTPS'] ?? null;
            $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
        }
        $scheme = strtolower($scheme);

        // Host and port (prefer forwarded host if provided)
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Normalize host: if forwarded host includes multiple values, take first
        if (strpos($host, ',') !== false) {
            $host = trim(explode(',', $host)[0]);
        }

        // Optional forwarded prefix (e.g., when app is mounted under a subpath by a proxy)
        $prefix = $_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? '';
        $prefix = is_string($prefix) ? rtrim($prefix, '/') : '';
        if ($prefix === '/') {
            $prefix = '';
        }

        // Script base directory (when running behind built-in server or subfolder)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = ($basePath === '/' ? '' : rtrim($basePath, '/'));

        // Prefer proxy prefix when present, otherwise script base
        $path = $prefix !== '' ? $prefix : $basePath;

        $url = $scheme . '://' . $host . $path;
        return rtrim($url, '/');
    }

    /**
     * Create a Router instance using the detected base URL.
     */
    public static function auto(?string $separator = ":"): self
    {
        return new self(self::autoBaseUrl(), $separator);
    }

    /**
     * @param string $assetsPath
     */
    public function assets(string $assetsPath): void
    {
        $path = parse_url($this->path, PHP_URL_PATH) ?? '/';

        // Normalize and safely resolve paths
        $root = rtrim($assetsPath, '/') . '/';
        $realRoot = realpath($root);

        // If the configured assets root doesn't exist, do nothing (safe fail)
        if ($realRoot === false || !is_dir($realRoot)) {
            return;
        }

        // Build candidate path relative to the real root and resolve canonically
        $candidate = $realRoot . DIRECTORY_SEPARATOR . ltrim($path, '/');
        $realFile = realpath($candidate);

        // Symlink policy: block if symlink and not allowed
        if (!$this->assetsFollowSymlinks && (is_link($candidate) || ($realFile !== false && is_link($realFile)))) {
            return;
        }

        // Serve only files that are inside the assets root (prevents traversal)
        if ($realFile && is_file($realFile) && strpos($realFile, rtrim($realRoot, DIRECTORY_SEPARATOR)) === 0) {

            // Allowlist policy: restrict extensions when configured
            if ($this->assetsAllowExt !== null) {
                $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
                if (!in_array($ext, $this->assetsAllowExt, true)) {
                    return;
                }
            }

            $mimeType = $this->getMimeType($realFile);
            $lastModified = filemtime($realFile);
            $eTag = md5_file($realFile);
            $size = filesize($realFile);

            header("Content-Type: {$mimeType}");
            header("Accept-Ranges: bytes");

            if ($this->cacheTime !== false) {
                header("Cache-Control: public, max-age={$this->cacheTime}");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
                header("ETag: \"{$eTag}\"");

                // If not modified, return 304 without Content-Length/body
                if ($this->notModified($lastModified, $eTag)) {
                    header("HTTP/1.1 304 Not Modified");
                    exit;
                }
            } else {
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
            }

            // Support HEAD requests: send headers only, include Content-Length, no body
            if ($this->httpMethod === 'HEAD') {
                header("Content-Length: {$size}");
                exit;
            }

            // Basic single-range support (bytes=start-end)
            $range = $_SERVER['HTTP_RANGE'] ?? null;
            if ($range && preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
                $start = ($m[1] !== '') ? (int)$m[1] : 0;
                $end = ($m[2] !== '') ? (int)$m[2] : ($size - 1);
                if ($start <= $end && $start < $size) {
                    $end = min($end, $size - 1);
                    $length = $end - $start + 1;

                    header("HTTP/1.1 206 Partial Content");
                    header("Content-Range: bytes {$start}-{$end}/{$size}");
                    header("Content-Length: {$length}");

                    $fp = fopen($realFile, 'rb');
                    if ($fp !== false) {
                        fseek($fp, $start);
                        $remaining = $length;
                        $chunk = 8192;
                        while ($remaining > 0 && !feof($fp)) {
                            $read = ($remaining > $chunk) ? $chunk : $remaining;
                            $buffer = fread($fp, $read);
                            if ($buffer === false) {
                                break;
                            }
                            echo $buffer;
                            $remaining -= strlen($buffer);
                            flush();
                        }
                        fclose($fp);
                    }
                    exit;
                }
            }

            // No range: send full content length and stream
            header("Content-Length: {$size}");
            $this->readFileChunked($realFile);
            exit;
        }
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function getMimeType(string $filePath): string
    {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
            'xml' => 'application/xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }

    /**
     * @param int $lastModified
     * @param string $eTag
     * @return bool
     */
    private function notModified(int $lastModified, string $eTag): bool
    {
        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            : false;
        $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH'])
            ? trim($_SERVER['HTTP_IF_NONE_MATCH'])
            : false;

        // Normalize quotes around ETag values for consistent comparison
        $stripQuotes = static fn($v) => is_string($v) ? trim($v, "\"") : $v;
        $eTag = $stripQuotes($eTag);
        if ($ifNoneMatch !== false) {
            $ifNoneMatch = $stripQuotes($ifNoneMatch);
        }

        if ($ifModifiedSince && $ifNoneMatch) {
            return ($ifNoneMatch === $eTag && $ifModifiedSince >= $lastModified);
        } elseif ($ifNoneMatch) {
            return $ifNoneMatch === $eTag;
        } elseif ($ifModifiedSince) {
            return $ifModifiedSince >= $lastModified;
        }

        return false;
    }

    /**
     * @param string $file
     */
    private function readFileChunked(string $file): void
    {
        $chunkSize = 8192; // 8KB chunks
        $handle = fopen($file, 'rb');

        if ($handle === false) {
            return;
        }

        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            flush();
        }

        fclose($handle);
    }

    /**
     * @param string $assetsPath
     * @return Router
     */
    public function setAssets(string $assetsPath): Router
    {
        $this->assetsPath = rtrim($assetsPath, '/') . '/';
        return $this;
    }

    /**
     * @param int|false $time
     * @return Router
     */
    public function setCache(int|false $time): Router
    {
        $this->cacheTime = $time;
        return $this;
    }

    /**
     * Restrict served asset extensions. Pass lowercase names without dot, e.g. ['css','js','png'].
     * Set to an empty array to block all; set to null (via setAssetAllowExtensionsAll) to allow all.
     *
     * @param array $extensions
     * @return Router
     */
    public function setAssetAllowExtensions(array $extensions): Router
    {
        $normalized = array_values(array_unique(array_map('strtolower', $extensions)));
        $this->assetsAllowExt = $normalized;
        return $this;
    }

    /**
     * Allow all extensions (disable allowlist).
     *
     * @return Router
     */
    public function setAssetAllowExtensionsAll(): Router
    {
        $this->assetsAllowExt = null;
        return $this;
    }

    /**
     * Control symlink following behavior when serving assets. Default: false (blocked).
     *
     * @param bool $follow
     * @return Router
     */
    public function setAssetFollowSymlinks(bool $follow): Router
    {
        $this->assetsFollowSymlinks = $follow;
        return $this;
    }


    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function get(
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $this->addRoute("GET", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function post(
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $this->addRoute("POST", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function put(
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $this->addRoute("PUT", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function patch(
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $this->addRoute("PATCH", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function delete(
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $this->addRoute("DELETE", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function head(
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $this->addRoute("HEAD", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function options(
        string $route,
        callable|string $handler,
        ?string $name = null,
        array|string|null $middleware = null
    ): void {
        $this->addRoute("OPTIONS", $route, $handler, $name, $middleware);
    }
}