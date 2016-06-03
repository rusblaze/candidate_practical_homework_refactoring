<?php

namespace Language;

/**
 * Business logic related to generating language files.
 */
class LanguageBatchBo
{
    /**
     * Contains the applications which ones require translations.
     *
     * @var array
     */
    protected static $applications = [];

    /**
     * Logger instance
     *
     * @var \Monolog\Logger | null
     */
    protected static $logger;

    /**
     * Starts the language file generation.
     *
     * @return void
     */
    public static function generateLanguageFiles()
    {
        self::$logger = Log\LoggerFactory::get('language files');
        // The applications where we need to translate.
        self::$applications = Config::get('system.translated_applications');

        self::$logger->addInfo("Generating language files");
        foreach (self::$applications as $application => $languages) {
            self::$logger->addInfo("[APPLICATION: " . $application . "]");
            foreach ($languages as $language) {
                $languagesInfoString = "\t[LANGUAGE: " . $language . "]";
                if (self::getLanguageFile($application, $language)) {
                    $languagesInfoString .= " OK";
                    self::$logger->addInfo($languagesInfoString);
                }
                else {
                    throw new \Exception('Unable to generate language file!');
                }
            }
        }
    }

    /**
     * Gets the language file for the given language and stores it.
     *
     * @param string $application   The name of the application.
     * @param string $language      The identifier of the language.
     *
     * @throws CurlException   If there was an error during the download of the language file.
     *
     * @return bool   The success of the operation.
     */
    protected static function getLanguageFile($application, $language)
    {
        $result = false;
        $languageResponse = ApiCall::call(
            'system_api',
            'language_api',
            [
                'system' => 'LanguageFiles',
                'action' => 'getLanguageFile',
            ],
            ['language' => $language]
        );

        try {
            self::checkForApiErrorResult($languageResponse);
        } catch (\Exception $e) {
            throw new \Exception('Error during getting language file: (' . $application . '/' . $language . ')');
        }

        // If we got correct data we store it.
        $destination = self::getLanguageCachePath($application) . $language . '.php';
        // If there is no folder yet, we'll create it.
        self::$logger->addDebug($destination);
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }

        $result = file_put_contents($destination, $languageResponse['data']);

        return (bool) $result;
    }

    /**
     * Gets the directory of the cached language files.
     *
     * @param string $application   The application.
     *
     * @return string   The directory of the cached language files.
     */
    protected static function getLanguageCachePath($application)
    {
        return Config::get('system.paths.root') . '/cache/' . $application. '/';
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
        self::$logger = Log\LoggerFactory::get('Applet language files');
        // List of the applets [directory => applet_id].
        $applets = [
            'memberapplet' => 'JSM2_MemberApplet',
        ];

        self::$logger->addInfo("Getting applet language XMLs..");

        foreach ($applets as $appletDirectory => $appletLanguageId) {
            self::$logger->addInfo(" Getting > $appletLanguageId ($appletDirectory) language xmls..");
            $languages = self::getAppletLanguages($appletLanguageId);
            if (empty($languages)) {
                throw new \Exception('There is no available languages for the ' . $appletLanguageId . ' applet.');
            }
            else {
                self::$logger->addInfo(' - Available languages: ' . implode(', ', $languages));
            }
            $path = Config::get('system.paths.root') . '/cache/flash';
            foreach ($languages as $language) {
                $xmlContent = self::getAppletLanguageFile($appletLanguageId, $language);
                $xmlFile    = $path . '/lang_' . $language . '.xml';
                if (strlen($xmlContent) == file_put_contents($xmlFile, $xmlContent)) {
                    self::$logger->addInfo(" OK saving $xmlFile was successful.");
                }
                else {
                    throw new \Exception('Unable to save applet: (' . $appletLanguageId . ') language: (' . $language
                        . ') xml (' . $xmlFile . ')!');
                }
            }
            self::$logger->addInfo(" < $appletLanguageId ($appletDirectory) language xml cached.");
        }

        self::$logger->addInfo("Applet language XMLs generated.");
    }

    /**
     * Gets the available languages for the given applet.
     *
     * @param string $applet   The applet identifier.
     *
     * @return array   The list of the available applet languages.
     */
    protected static function getAppletLanguages($applet)
    {
        $result = ApiCall::call(
            'system_api',
            'language_api',
            [
                'system' => 'LanguageFiles',
                'action' => 'getAppletLanguages',
            ],
            ['applet' => $applet]
        );

        try {
            self::checkForApiErrorResult($result);
        } catch (\Exception $e) {
            throw new \Exception('Getting languages for applet (' . $applet . ') was unsuccessful ' . $e->getMessage());
        }

        return $result['data'];
    }


    /**
     * Gets a language xml for an applet.
     *
     * @param string $applet      The identifier of the applet.
     * @param string $language    The language identifier.
     *
     * @return string|false   The content of the language file or false if weren't able to get it.
     */
    protected static function getAppletLanguageFile($applet, $language)
    {
        $result = ApiCall::call(
            'system_api',
            'language_api',
            [
                'system' => 'LanguageFiles',
                'action' => 'getAppletLanguageFile',
            ],
            [
                'applet' => $applet,
                'language' => $language,
            ]
        );

        try {
            self::checkForApiErrorResult($result);
        } catch (\Exception $e) {
            throw new \Exception('Getting language xml for applet: (' . $applet . ') on language: (' . $language . ') was unsuccessful: '
                . $e->getMessage());
        }

        return $result['data'];
    }

    /**
     * Checks the api call result.
     *
     * @param mixed  $result   The api call result to check.
     *
     * @throws Exception   If the api call was not successful.
     *
     * @return void
     */
    protected static function checkForApiErrorResult($result)
    {
        // Error during the api call.
        if ($result === false || !isset($result['status'])) {
            throw new \Exception('Error during the api call');
        }
        // Wrong response.
        if ($result['status'] != 'OK') {
            throw new \Exception('Wrong response: '
                . (!empty($result['error_type']) ? 'Type(' . $result['error_type'] . ') ' : '')
                . (!empty($result['error_code']) ? 'Code(' . $result['error_code'] . ') ' : '')
                . ((string)$result['data']));
        }
        // Wrong content.
        if ($result['data'] === false) {
            throw new \Exception('Wrong content!');
        }
    }
}
