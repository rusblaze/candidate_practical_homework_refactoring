<?php

namespace Language\Generator;

use Language\Log;
use Language\Config;
use Language\ApiCall;

/**
 * Business logic related to generating language files.
 */
class Applets implements GeneratorInterface
{
    /**
     * Logger instance
     *
     * @var \Monolog\Logger | null
     */
    protected $logger;

    const LOGGER_NAME = 'Applets language files';

    public function __construct()
    {
        $this->logger = Log\LoggerFactory::get(self::LOGGER_NAME);
    }
    /**
     * Gets the language files for the applet and puts them into the cache.
     *
     * @throws Exception   If there was an error.
     *
     * @return void
     */
    public function generate()
    {
        // List of the applets [directory => applet_id].
        $applets = [
            'memberapplet' => 'JSM2_MemberApplet',
        ];

        $this->logger->addInfo("Getting applet language XMLs..");

        foreach ($applets as $appletDirectory => $appletLanguageId) {
            $this->logger->addInfo(" Getting > $appletLanguageId ($appletDirectory) language xmls..");
            $languages = $this->getAppletLanguages($appletLanguageId);
            if (empty($languages)) {
                throw new \Exception('There is no available languages for the ' . $appletLanguageId . ' applet.');
            }
            else {
                $this->logger->addInfo(' - Available languages: ' . implode(', ', $languages));
            }
            $path = Config::get('system.paths.root') . '/cache/flash';
            foreach ($languages as $language) {
                $xmlContent = $this->getAppletLanguageFile($appletLanguageId, $language);
                $xmlFile    = $path . '/lang_' . $language . '.xml';
                if (strlen($xmlContent) == file_put_contents($xmlFile, $xmlContent)) {
                    $this->logger->addInfo(" OK saving $xmlFile was successful.");
                }
                else {
                    throw new \Exception('Unable to save applet: (' . $appletLanguageId . ') language: (' . $language
                        . ') xml (' . $xmlFile . ')!');
                }
            }
            $this->logger->addInfo(" < $appletLanguageId ($appletDirectory) language xml cached.");
        }

        $this->logger->addInfo("Applet language XMLs generated.");
    }

    /**
     * Gets the available languages for the given applet.
     *
     * @param string $applet   The applet identifier.
     *
     * @return array   The list of the available applet languages.
     */
    protected function getAppletLanguages($applet)
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
            $this->checkForApiErrorResult($result);
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
    protected function getAppletLanguageFile($applet, $language)
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
            $this->checkForApiErrorResult($result);
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
