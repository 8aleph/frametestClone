<?php

declare(strict_types = 1);

namespace Mini\File;

use Exception;

/**
 * File handler.
 */
class File
{
    /**
     * Get the file contents.
     * 
     * @param string $path file path
     * 
     * @return string file contents
     */
    public function get(string $path): string
    {
        return file_get_contents($path);
    }

    /**
     * Set the file contents.
     * 
     * @param string $path     file path
     * @param string $contents file contents
     * 
     * @return void
     */
    public function set(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
    }

    /**
     * Creates a temporary file.
     * 
     * @param string $content   optional content for the temporary file
     * @param string $extension optional extension for the filename
     * 
     * @return string the full path of the file
     */
    public function create(string $content = '', string $extension = 'txt'): string
    {
        $path = getenv('DATA_PATH') . DIRECTORY_SEPARATOR . 'tmp_' . generate_random_id(-12) . '.' . $extension;

        $this->removeIfExists($path);
        $this->set($path, $content);

        return $path;
    }

    /**
     * Check if a file exists
     * 
     * @param string $path file path
     * 
     * @return bool file exists status
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Remove a file.
     * 
     * @param string $path file path
     * 
     * @return bool file delete status
     */
    public function remove(string $path): bool
    {
        return unlink($path);
    }

    /**
     * If the file exists, remove it.
     * 
     * @param string $path file path
     * 
     * @return void
     */
    public function removeIfExists(string $path): void
    {
        if ($this->exists($path)) {
            $this->remove($path);
        }
    }

    /**
     * Get a list of files from a directory.
     * 
     * Note: this sorts by date modified with the most recent files coming first
     * 
     * @param string $path directory path
     * 
     * @return array list of files
     *
     * @throws Exception if the file directory path is invalid
     */
    public function getFiles(string $path): array
    {
        if (!is_dir($path)) {
            throw new Exception('Unknown directory path: ' . $path);
        }

        // Ignore ./.. and hidden files
        $list = preg_grep('/^([^.])/', scandir($path));

        if (!$list) {
            return [];
        }

        $files = [];
        foreach ($list as $file) {
            $files[$file] = filemtime($path . $file); 
        }

        // Sort by date
        arsort($files);

        return array_keys($files);
    }

    /**
     * Get information about a file.
     * 
     * @param string $path file location
     * 
     * @return array $data file information
     */
    public function getFileInfo(string $path): array
    {
        // Since we change files and need to get info on them, we need to clear the cache
        clearstatcache();

        $fInfo = new \finfo(FILEINFO_MIME);
        $fRes  = $fInfo->file($path);
        $fStat = stat($path);

        // File info
        $data['mime'] = is_string($fRes) && !empty($fRes) ? $fRes : 'application/octet-stream';
        $data['size'] = $fStat['size'];

        // Hash it
        $data['fingerprint'] = $this->getFingerprintHash($path);

        return $data;
    }

    /**
     * Get a fingerprint of the file.
     * 
     * @param string $path file location
     * 
     * @return string $result file hash
     */
    public function getFingerprintHash(string $path): string
    {
        $result = false;
        $handle = fopen($path, 'r');

        if ($handle) {
            $context = hash_init('sha256');

            while (!feof($handle)) {
                $buffer = fgets($handle, 65535);
                hash_update($context, $buffer);
            }

            $result = hash_final($context);
            fclose($handle);
        }

        return $result;
    }
}
