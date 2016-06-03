<?php

namespace Language\Generator;

use Language\Log;
use Language\Config;
use Language\Api\Requester;
use Language\Api\RequestType;

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
        $this->apiRequester = new Requester;
    }

    protected function getApplications()
    {
        // The applications where we need to translate.
        $applications = Config::get('system.translated_applications');

        foreach ($applications as $appId => $languages) {
            $cachePathRoot = Config::get('system.paths.root') . '/cache/' . $appId. '/';
            $paths = [];
            foreach ($languages as $language) {
                // If we got correct data we store it.
                $paths[$language] = $cachePathRoot . $language . '.php';
            }

            $applications[$appId] = [
                'languages' => $languages,
                'cache_paths' => $paths,
            ];
        }

        return $applications;
    }
    /**
     * Starts the language file generation.
     *
     * @return void
     */
    public function generate()
    {
        $applications = $this->getApplications();
        $writer = new \Language\Writer\FileWriter;
        $this->logger->addInfo("Generating applications language files...");
        foreach ($applications as $application => $config) {
            $this->logger->addInfo("[APPLICATION: " . $application . "]");
            $languages = $config['languages'];
            $paths = $config['cache_paths'];
            foreach ($languages as $language) {
                $this->logger->addInfo("\t[LANGUAGE: " . $language . "]");
                try {
                    $content = $this->apiRequester->getLanguageFileContent(RequestType::APPLICATION, $application, $language);
                    $file = $paths[$language];
                    $writer->writeFile($content, $file);
                    $this->logger->addInfo("\t\tOK.");
                } catch (\Language\Api\Exception $ae) {
                    $this->logger->addError($ae->getMessage());
                } catch (\Language\Writer\Exception $we) {
                    $this->logger->addError(
                        "\t\tlanguage file not cached: " . $we->getMessage()
                    );
                }
            }
        }
        $this->logger->addInfo("Applications language files generated.");
    }
}
