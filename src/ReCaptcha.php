<?php

namespace Nails\Captcha\Driver;

use GuzzleHttp\Client;
use Nails\Captcha\Constants;
use Nails\Captcha\Exception\CaptchaDriverException;
use Nails\Captcha\Factory\CaptchaForm;
use Nails\Common\Driver\Base;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Service\Asset;
use Nails\Common\Service\Input;
use Nails\Factory;

/**
 * Class ReCaptcha
 *
 * @package Nails\Captcha\Driver
 */
class ReCaptcha extends Base implements \Nails\Captcha\Interfaces\Driver
{
    const RESPONSE_FIELD_KEY = 'g-recaptcha-response';
    const V3_ACTION          = 'submit';

    // --------------------------------------------------------------------------

    /**
     * Called during system boot, allows the driver to load assets etc
     *
     * @throws FactoryException
     */
    public function boot(): void
    {
        /** @var Asset $oAsset */
        $oAsset     = Factory::service('Asset');
        $sVersion   = appSetting(ReCaptcha\Settings\ReCaptcha::VERSION, ReCaptcha\Constants::MODULE_SLUG);
        $sClientKey = appSetting(ReCaptcha\Settings\ReCaptcha::KEY_CLIENT, ReCaptcha\Constants::MODULE_SLUG);

        $sJsUrl = $sVersion === ReCaptcha\Settings\ReCaptcha::VERSION_3
            ? 'https://www.google.com/recaptcha/api.js?render=' . $sClientKey
            : 'https://www.google.com/recaptcha/api.js';

        $oAsset->load($sJsUrl, null, $oAsset::TYPE_JS_HEADER);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the form markup for the captcha
     *
     * @return CaptchaForm
     * @throws CaptchaDriverException
     * @throws FactoryException
     */
    public function generate(): CaptchaForm
    {
        $sVersion   = appSetting(ReCaptcha\Settings\ReCaptcha::VERSION, ReCaptcha\Constants::MODULE_SLUG);
        $sClientKey = appSetting(ReCaptcha\Settings\ReCaptcha::KEY_CLIENT, ReCaptcha\Constants::MODULE_SLUG);

        if (empty($sClientKey)) {
            throw new CaptchaDriverException('ReCaptcha not configured.');
        }

        /** @var CaptchaForm $oResponse */
        $oResponse = Factory::factory('CaptchaForm', Constants::MODULE_SLUG);

        if ($sVersion === ReCaptcha\Settings\ReCaptcha::VERSION_2) {

            $sHtml = <<<EOT
            <div class="g-recaptcha" data-sitekey="$sClientKey"></div>
            EOT;

        } elseif ($sVersion === ReCaptcha\Settings\ReCaptcha::VERSION_3) {

            $sKey    = static::RESPONSE_FIELD_KEY;
            $sId     = 'recaptcha-field-' . uniqid();
            $sAction = static::V3_ACTION;

            $sHtml = <<<EOT
            <input type="hidden" name="$sKey" id="$sId" />
            <script type="text/javascript">

                window.addEventListener('DOMContentLoaded', function() {

                    var field = document.getElementById('$sId');
                    var form = field.closest('form');

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        grecaptcha
                            .ready(function() {
                                grecaptcha
                                    .execute('$sClientKey', {action: '$sAction'})
                                    .then(function(token) {
                                        field.value = token;
                                        form.submit();
                                    });
                            });
                    });

                });

            </script>
            EOT;

        } else {
            throw new CaptchaDriverException('Unsupported captcha version defined.');
        }

        $oResponse
            ->setHtml($sHtml)
            ->setInvisible($sVersion === ReCaptcha\Settings\ReCaptcha::VERSION_3);

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
        $sVersion   = appSetting(ReCaptcha\Settings\ReCaptcha::VERSION, ReCaptcha\Constants::MODULE_SLUG);
        $sServerKey = appSetting(ReCaptcha\Settings\ReCaptcha::KEY_SERVER, ReCaptcha\Constants::MODULE_SLUG);

        if ($sServerKey) {

            /** @var Client $oHttpClient */
            $oHttpClient = Factory::factory('HttpClient');
            /** @var Input $oInput */
            $oInput = Factory::service('Input');

            if ($sToken === null) {
                $sToken = $oInput->post(static::RESPONSE_FIELD_KEY);
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

                if ($sVersion === ReCaptcha\Settings\ReCaptcha::VERSION_3) {
                    $fThreshold = (float) appSetting(ReCaptcha\Settings\ReCaptcha::V3_THRESHOLD, ReCaptcha\Constants::MODULE_SLUG);
                    if ($oResponse->score < $fThreshold) {
                        throw new CaptchaDriverException(sprintf(
                            'The score of this response (%s) is below the threshold (%s).',
                            $oResponse->score,
                            $fThreshold
                        ));
                    } elseif ($oResponse->action !== static::V3_ACTION) {
                        throw new CaptchaDriverException('Invalid action.');
                    }
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
