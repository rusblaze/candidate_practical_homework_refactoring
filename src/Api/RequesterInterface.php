<?php

namespace Language\Api;

use Language\ApiCall;

interface RequesterInterface
{
    /**
     * Gets the language file for the given language and stores it.
     *
     * @param integer $requestType  One of RequestType consts
     * @param string $application   The name of the application.
     * @param string $language      The identifier of the language.
     *
     * @throws CurlException   If there was an error during the download of the language file.
     *
     * @return bool   The success of the operation.
     */
    public function getLanguageFileContent($requestType, $appId, $language);

    /**
     * Gets the available languages for the given applet.
     *
     * @param string $applet   The applet identifier.
     *
     * @return array   The list of the available applet languages.
     */
    public function getAppletLanguages($applet);
}

