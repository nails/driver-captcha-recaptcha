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
        $sClientKey = appSetting('site_key_client', 'nailsapp/driver-captcha-recaptcha');

        if ($sClientKey) {

            $oAsset = Factory::service('Asset');
            $oAsset->load('https://www.google.com/recaptcha/api.js');

            $oOut        = new \stdClass();
            $oOut->label = '';
            $oOut->html  = '<div class="g-recaptcha" data-sitekey="' . $sClientKey . '"></div>';

        } else {

            $oOut        = new \stdClass();
            $oOut->label = '';
            $oOut->html  = '<p class="alert alert-danger">ReCaptcha not configured.</p>';
        }

        return $oOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Verifies a user's captcha entry from POST Data
     * @return boolean
     */
    public function verify()
    {
        $sServerKey = appSetting('site_key_server', 'nailsapp/driver-captcha-recaptcha');

        if ($sServerKey) {

            $oHttpClient = Factory::factory('HttpClient');
            $oInput      = Factory::service('Input');

            try {

                $oResponse = $oHttpClient->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    array(
                        'form_params' => array(
                            'secret'   => $sServerKey,
                            'response' => $oInput->post('g-recaptcha-response'),
                            'remoteip' => $oInput->ipAddress()
                        )
                    )
                );

                if ($oResponse->getStatusCode() !== 200) {
                    throw new CaptchaDriverException('Google returned a non 200 response.', 1);
                }

                $oResponse = json_decode((string) $oResponse->getBody());

                if (empty($oResponse->success)) {
                    throw new CaptchaDriverException('Google returned an unsuccessful response.', 1);
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
