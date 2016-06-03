<?php
namespace Test;

use Language\Config;
use PHPUnit_Framework_TestCase;

class LanguageBatchBoTest extends PHPUnit_Framework_TestCase
{
    public function testGenerateLanguageFiles()
    {
        $languageBatchBo = new \Language\LanguageBatchBo();
        $languageBatchBo->generateLanguageFiles();

        // Assert
        $applications = Config::get('system.translated_applications');
        foreach ($applications as $application => $languages) {
            $destinationPath = Config::get('system.paths.root') . '/cache/' . $application. '/';
            foreach ($languages as $language) {
                $this->assertFileExists($destinationPath . $language . '.php');
            }
        }
    }

    public function testGenerateAppletLanguageXmlFiles()
    {
        $languageBatchBo = new \Language\LanguageBatchBo();
        $languageBatchBo->generateAppletLanguageXmlFiles();

        // Assert
        $applications = Config::get('system.translated_applications');
        foreach ($applications as $application => $languages) {
            $destinationPath = Config::get('system.paths.root') . '/cache/' . $application. '/';
            foreach ($languages as $language) {
                $this->assertFileExists($destinationPath . $language . '.php');
            }
        }
    }
}
