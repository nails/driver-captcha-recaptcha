<?php

namespace Nails\Captcha\Driver\ReCaptcha\Admin\Permission\Settings;

use Nails\Admin\Interfaces\Permission;

class ReCaptcha implements Permission
{
    public function label(): string
    {
        return 'Can manage Google ReCaptcha settings';
    }

    public function group(): string
    {
        return 'Settings';
    }
}
