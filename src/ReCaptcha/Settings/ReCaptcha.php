<?php

namespace Nails\Captcha\Driver\ReCaptcha\Settings;

use Nails\Common\Helper\Form;
use Nails\Common\Interfaces;
use Nails\Common\Service\FormValidation;
use Nails\Components\Setting;
use Nails\Factory;

/**
 * Class ReCaptcha
 *
 * @package Nails\Captcha\Driver\ReCaptcha\Settings
 */
class ReCaptcha implements Interfaces\Component\Settings
{
    const KEY_CLIENT = 'site_key_client';
    const KEY_SERVER = 'site_key_server';

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Google ReCaptcha';
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        /** @var Setting $oKeyClient */
        $oKeyClient = Factory::factory('ComponentSetting');
        $oKeyClient
            ->setKey(static::KEY_CLIENT)
            ->setLabel('Site Key')
            ->setInfo('You should get this from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google</a>')
            ->setFieldset('Keys')
            ->setValidation([
                FormValidation::RULE_REQUIRED,
            ]);

        /** @var Setting $oKeyServer */
        $oKeyServer = Factory::factory('ComponentSetting');
        $oKeyServer
            ->setKey(static::KEY_SERVER)
            ->setLabel('Secret Key')
            ->setInfo('You should get this from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google</a>')
            ->setFieldset('Keys')
            ->setValidation([
                FormValidation::RULE_REQUIRED,
            ]);

        return [
            $oKeyClient,
            $oKeyServer,
        ];
    }
}
