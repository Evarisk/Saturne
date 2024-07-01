<?php

class SaturneRedirection
{
    private $htaccessPath;
    private $baseUrl;

    public function __construct($htaccessPath, $baseUrl)
    {
        $this->htaccessPath = $htaccessPath;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function addRedirection($fromPath, $toUrl)
    {
        if (strpos($fromPath, '/') === 0) {
            $fromUrl = $this->baseUrl . $fromPath;
        } else {
            $fromUrl = $fromPath;
        }

        $fromUrl = filter_var($fromUrl, FILTER_SANITIZE_URL);
        $toUrl = filter_var($toUrl, FILTER_SANITIZE_URL);

        if (!filter_var($toUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid destination URL format.');
        }

        $fromPath = parse_url($fromUrl, PHP_URL_PATH);

        $redirectionLine = "Redirect 301 $fromPath $toUrl" . PHP_EOL;

        $htaccessContent = file_get_contents($this->htaccessPath);
        if ($htaccessContent === false) {
            throw new Exception('Failed to read .htaccess file.');
        }

        $rewriteEnginePos = strpos($htaccessContent, 'RewriteEngine on');
        if ($rewriteEnginePos === false) {
            throw new Exception('RewriteEngine directive not found.');
        }

        $insertPos = strpos($htaccessContent, PHP_EOL, $rewriteEnginePos) + 1;

        $newHtaccessContent = substr_replace($htaccessContent, $redirectionLine, $insertPos, 0);

        if (file_put_contents($this->htaccessPath, $newHtaccessContent, LOCK_EX) === false) {
            throw new Exception('Failed to write to .htaccess file.');
        }

        return true;
    }

    public function removeRedirection($fromPath)
    {
        if (strpos($fromPath, '/') === 0) {
            $fromUrl = $this->baseUrl . $fromPath;
        } else {
            $fromUrl = $fromPath;
        }

        $fromUrl = filter_var($fromUrl, FILTER_SANITIZE_URL);

        $fromPath = parse_url($fromUrl, PHP_URL_PATH);

        $htaccessContent = file_get_contents($this->htaccessPath);
        if ($htaccessContent === false) {
            throw new Exception('Failed to read .htaccess file.');
        }

        $pattern = "/^Redirect 301 " . preg_quote($fromPath, '/') . " .+$/m";

        $newHtaccessContent = preg_replace($pattern, '', $htaccessContent);

        if (file_put_contents($this->htaccessPath, $newHtaccessContent, LOCK_EX) === false) {
            throw new Exception('Failed to write to .htaccess file.');
        }

        return true;
    }

    public function listRedirections()
    {
        $htaccessContent = file_get_contents($this->htaccessPath);
        if ($htaccessContent === false) {
            throw new Exception('Failed to read .htaccess file.');
        }

        preg_match_all('/^Redirect 301 (.+) (.+)$/m', $htaccessContent, $matches, PREG_SET_ORDER);

        $redirections = [];
        foreach ($matches as $match) {
            $redirections[] = ['from' => $match[1], 'to' => $match[2]];
        }

        return $redirections;
    }
}

?>
