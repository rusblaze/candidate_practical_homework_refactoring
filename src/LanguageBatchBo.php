<?php

namespace Language;

/**
 * Business logic related to generating language files.
 */
class LanguageBatchBo
{
    /**
     * Starts the language file generation.
     *
     * @return void
     */
    public static function generateLanguageFiles()
    {
        self::executeGeneration(new Generator\Applications);
    }

    /**
     * Gets the language files for the applet and puts them into the cache.
     *
     * @throws Exception   If there was an error.
     *
     * @return void
     */
    public static function generateAppletLanguageXmlFiles()
    {
        self::executeGeneration(new Generator\Applets);
    }

    protected static function executeGeneration(Generator\GeneratorInterface $generator)
    {
        $generator->generate();
    }
}
