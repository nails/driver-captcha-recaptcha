<?php

namespace Nails\Captcha\Driver\ReCaptcha\Settings;

use Nails\Common\Exception\ValidationException;
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
    const VERSION      = 'version';
    const KEY_CLIENT   = 'site_key_client';
    const KEY_SERVER   = 'site_key_server';
    const V3_THRESHOLD = 'v3_threshold';

    const VERSION_2 = 'V2';
    const VERSION_3 = 'V3';

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
    public function getPermissions(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        /** @var Setting $oVersion */
        $oVersion = Factory::factory('ComponentSetting');
        $oVersion
            ->setKey(static::VERSION)
            ->setType(Form::FIELD_DROPDOWN)
            ->setLabel('Version')
            ->setOptions([
                static::VERSION_2 => 'V2',
                static::VERSION_3 => 'V3 (invisible)',
            ])
            ->setData([
                'revealer' => 'recaptcha-v3',
            ])
            ->setDefault(static::VERSION_2)
            ->setClass('select2')
            ->setValidation([
                FormValidation::RULE_REQUIRED,
            ]);

        /** @var Setting $oV3Threshold */
        $oV3Threshold = Factory::factory('ComponentSetting');
        $oV3Threshold
            ->setKey(static::V3_THRESHOLD)
            ->setType(Form::FIELD_NUMBER)
            ->setLabel('Threshold')
            ->setInfo('A value between 0.0 and 1.0 (a challenge score of 1.0 is very likely a good interaction, 0.0 is very likely a bot).')
            ->setInfoClass('alert alert-info')
            ->setDefault(0.5)
            ->setData([
                'revealer'  => 'recaptcha-v3',
                'reveal-on' => static::VERSION_3,
            ])
            ->setValidation([
                FormValidation::RULE_NUMERIC,
                FormValidation::rule(FormValidation::RULE_GREATER_THAN_EQUAL_TO, 0),
                FormValidation::rule(FormValidation::RULE_LESS_THAN_EQUAL_TO, 1),
            ]);

        /** @var Setting $oKeyClient */
        $oKeyClient = Factory::factory('ComponentSetting');
        $oKeyClient
            ->setKey(static::KEY_CLIENT)
            ->setLabel('Site Key')
            ->setInfo('You should get this from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google</a>')
            ->setValidation([
                FormValidation::RULE_REQUIRED,
            ]);

        /** @var Setting $oKeyServer */
        $oKeyServer = Factory::factory('ComponentSetting');
        $oKeyServer
            ->setKey(static::KEY_SERVER)
            ->setLabel('Secret Key')
            ->setInfo('You should get this from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google</a>')
            ->setValidation([
                FormValidation::RULE_REQUIRED,
            ]);

        return [
            $oVersion,
            $oV3Threshold,
            $oKeyClient,
            $oKeyServer,
        ];
    }
}
