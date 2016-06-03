<?php

namespace Language\Generator;

use Language\Log;
use Language\Config;
use Language\ApiCall;

/**
 * Business logic related to generating language files.
 */
class Applications implements GeneratorInterface
{
    /**
     * Logger instance
     *
     * @var \Monolog\Logger | null
     */
    protected $logger;

    const LOGGER_NAME = 'Applications language files';

    public function __construct()
    {
        $this->logger = Log\LoggerFactory::get(self::LOGGER_NAME);
    }

    /**
     * Starts the language file generation.
     *
     * @return void
     */
    public function generate()
    {
        // The applications where we need to translate.
        $applications = Config::get('system.translated_applications');

        $this->logger->addInfo("Generating language files");
        foreach ($applications as $application => $languages) {
            $this->logger->addInfo("[APPLICATION: " . $application . "]");
            foreach ($languages as $language) {
                $languagesInfoString = "\t[LANGUAGE: " . $language . "]";
                if ($this->getLanguageFile($application, $language)) {
                    $languagesInfoString .= " OK";
                    $this->logger->addInfo($languagesInfoString);
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
    protected function getLanguageFile($application, $language)
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
            $this->checkForApiErrorResult($languageResponse);
        } catch (\Exception $e) {
            throw new \Exception('Error during getting language file: (' . $application . '/' . $language . ')');
        }

        // If we got correct data we store it.
        $destination = $this->getLanguageCachePath($application) . $language . '.php';
        // If there is no folder yet, we'll create it.
        $this->logger->addDebug($destination);
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
    protected function getLanguageCachePath($application)
    {
        return Config::get('system.paths.root') . '/cache/' . $application. '/';
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
    protected function checkForApiErrorResult($result)
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
