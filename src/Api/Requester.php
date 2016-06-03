<?php

namespace Language\Api;

use Language\ApiCall;

class Requester implements RequesterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLanguageFileContent($requestType, $appId, $language)
    {
        switch ($requestType) {
            case RequestType::APPLICATION:
                $action = 'getLanguageFile';
                break;
            case RequestType::APPLET:
                $action = 'getAppletLanguageFile';
                break;
            default:
                throw new \Exception('Error during getting language file: Unknown app type');
        }

        $result = false;
        $result = ApiCall::call(
            'system_api',
            'language_api',
            [
                'system' => 'LanguageFiles',
                'action' => $action,
            ],
            [
                'application' => $appId,
                'language' => $language,
            ]
        );

        try {
            $this->checkForApiErrorResult($result);
        } catch (\Exception $e) {
            throw new \Exception('Error during getting language file: (' . $appId . '/' . $language . ')');
        }

        return $result['data'];
    }

    /**
     * {@inheritDoc}
     */
    public function getAppletLanguages($applet)
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
