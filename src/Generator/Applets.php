<?php

namespace Language\Generator;

use Language\Log;
use Language\Config;
use Language\Api\Requester;
use Language\Api\RequestType;

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
        $this->apiRequester = new Requester;
    }

    protected function getApplets()
    {
        // List of the applets [directory => applet_id].
        $applets = [
            'memberapplet' => 'JSM2_MemberApplet',
        ];
        $appletsConfig = [];
        $cachePathRoot = Config::get('system.paths.root') . '/cache/flash';

        foreach ($applets as $applet => $appletLanguageId) {
            try {
                $languages = $this->apiRequester->getAppletLanguages($appletLanguageId);
            } catch (\Language\Api\Exception $ae) {
                $this->logger->addError($ae->getMessage());
            }
            if (empty($languages)) {
                $this->logger->addError('There is no available languages for the ' . $appletLanguageId . ' applet.');
                break;
            }

            $paths = [];
            foreach ($languages as $language) {
                $paths[$language] = $cachePathRoot . '/lang_' . $language . '.xml';
            }
            $appletsConfig[$applet] = [
                'id' => $appletLanguageId,
                'languages' => $languages,
                'cache_paths' => $paths,
            ];
        }

        return $appletsConfig;
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
        $this->logger->addInfo("Generating applet language XMLs...");
        $writer = new \Language\Writer\FileWriter;

        $applets = $this->getApplets();
        foreach ($applets as $applet => $config) {
            $this->logger->addInfo("[APPLET: " . $applet . "]");
            $languages = $config['languages'];
            $paths = $config['cache_paths'];
            $appletLanguageId = $config['id'];

            foreach ($languages as $language) {
                $this->logger->addInfo("\t[LANGUAGE: " . $language . "]");
                try {
                    $content = $this->apiRequester->getLanguageFileContent(RequestType::APPLET, $appletLanguageId, $language);
                    $file = $paths[$language];
                    $writer->writeFile($content, $file);
                    $this->logger->addInfo("\t\tOK.");
                } catch (\Language\Api\Exception $ae) {
                    $this->logger->addError($ae->getMessage());
                } catch (\Language\Writer\Exception $we) {
                    $this->logger->addError($we->getMessage());
                }
            }
        }

        $this->logger->addInfo("Applet language XMLs generated.");
    }
}
