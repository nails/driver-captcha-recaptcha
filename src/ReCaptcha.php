<?php

namespace Nails\Captcha\Driver;

use GuzzleHttp\Client;
use Nails\Captcha\Exception\CaptchaDriverException;
use Nails\Captcha\Factory\CaptchaForm;
use Nails\Common\Driver\Base;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Service\Asset;
use Nails\Common\Service\Input;
use Nails\Factory;

class ReCaptcha extends Base implements \Nails\Captcha\Interfaces\Driver
{
    /**
     * Returns the form markup for the captcha
     *
     * @return CaptchaForm
     * @throws CaptchaDriverException
     * @throws FactoryException
     */
    public function generate(): CaptchaForm
    {
        $sClientKey = appSetting('site_key_client', 'nails/driver-captcha-recaptcha');

        if (empty($sClientKey)) {
            throw new CaptchaDriverException('ReCaptcha not configured.');
        }

        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        $oAsset->load('https://www.google.com/recaptcha/api.js');

        /** @var CaptchaForm $oResponse */
        $oResponse = Factory::factory('CaptchaForm', 'nails/module-captcha');
        $oResponse->setHtml('<div class="g-recaptcha" data-sitekey="' . $sClientKey . '"></div>');

        return $oResponse;
    }

    // --------------------------------------------------------------------------

    /**
     * Verifies a user's captcha entry from POST Data
     *
     * @param string|null $sToken The token to verify
     *
     * @return bool
     * @throws FactoryException
     */
    public function verify(string $sToken = null): bool
    {
        $sServerKey = appSetting('site_key_server', 'nails/driver-captcha-recaptcha');

        if ($sServerKey) {

            /** @var Client $oHttpClient */
            $oHttpClient = Factory::factory('HttpClient');
            /** @var Input $oInput */
            $oInput = Factory::service('Input');

            if ($sToken === null) {
                $sToken = $oInput->post('g-recaptcha-response');
            }

            try {

                $oResponse = $oHttpClient->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    [
                        'form_params' => [
                            'secret'   => $sServerKey,
                            'response' => $sToken,
                            'remoteip' => $oInput->ipAddress(),
                        ],
                    ]
                );

                if ($oResponse->getStatusCode() !== 200) {
                    throw new CaptchaDriverException('Google returned a non 200 response.');
                }

                $oResponse = json_decode((string) $oResponse->getBody());

                if (empty($oResponse->success)) {
                    throw new CaptchaDriverException('Google returned an unsuccessful response.');
                }

                return true;

            } catch (CaptchaDriverException $e) {
                return false;
            }

        } else {
            return false;
        }
    }
}
