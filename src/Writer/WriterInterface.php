<?php

namespace Language\Writer;

interface WriterInterface
{
    /**
     * Stores content onto file.
     *
     * @param  string $content
     * @param  string $file    Fully qualified name
     *
     * @return void
     * @throws Exception
     */
    public function writeFile($content, $file);
}
