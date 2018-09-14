<?php

namespace Nails\Captcha\Driver;

use Nails\Factory;
use Nails\Common\Driver\Base;
use Nails\Captcha\Exception\CaptchaDriverException;

class ReCaptcha extends Base implements \Nails\Captcha\Interfaces\Driver
{
    /**
     * Returns the form markup for the captcha
     * @return string
     */
    public function generate()
    {
        $sClientKey = appSetting('site_key_client', 'nails/driver-captcha-recaptcha');

        if (empty($sClientKey)) {
            throw new CaptchaDriverException('ReCaptcha not configured.');
        }

        $oAsset = Factory::service('Asset');
        $oAsset->load('https://www.google.com/recaptcha/api.js');

        $oResponse = Factory::factory('CaptchaForm', 'nails/module-captcha');
        $oResponse->setHtml('<div class="g-recaptcha" data-sitekey="' . $sClientKey . '"></div>');

        return $oResponse;
    }

    // --------------------------------------------------------------------------

    /**
     * Verifies a user's captcha entry from POST Data
     * @return boolean
     */
    public function verify()
    {
        $sServerKey = appSetting('site_key_server', 'nails/driver-captcha-recaptcha');

        if ($sServerKey) {

            $oHttpClient = Factory::factory('HttpClient');
            $oInput      = Factory::service('Input');

            try {

                $oResponse = $oHttpClient->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    [
                        'form_params' => [
                            'secret'   => $sServerKey,
                            'response' => $oInput->post('g-recaptcha-response'),
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

            } catch (\Exception $e) {
                return false;
            }

        } else {
            return false;
        }
    }
}
