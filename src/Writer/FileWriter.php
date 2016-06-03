<?php

namespace Language\Writer;

use Monolog\Logger;
use Language\Config;
use Language\ApiCall;

class FileWriter implements WriterInterface
{
    protected function lockDestination($fileName)
    {
        $path = dirname($fileName);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        } elseif (!is_writable($path)) {
            throw new Exception("Folder $path is not writable");
        }

        $fp = fopen($fileName, "c+");

        if (flock($fp, LOCK_EX)) {
            return $fp;
        } else {
            throw new Exception("File $fileName couldn't be locked");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeFile($content, $file)
    {
        $resource = $this->lockDestination($file);
        ftruncate($resource, 0);
        $writed = fwrite($resource, $content);
        fflush($resource);
        flock($resource, LOCK_UN);
        fclose($resource);

        if (strlen($content) === $writed) {
            return true;
        } else {
            throw new Exception("\t\t: Unable to write data to file $file");
        }
    }
}
