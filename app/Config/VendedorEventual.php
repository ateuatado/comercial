<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class VendedorEventual extends BaseConfig
{
    public bool $enabled;
    public string $identityProvider;
    public bool $demoLoginEnabled;
    public string $demoPassword;

    public function __construct()
    {
        parent::__construct();

        $this->enabled          = filter_var(env('VENDOR_EVENTUAL_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        $this->identityProvider = strtolower(trim((string) env('SPIV_IDENTITY_PROVIDER', 'ldap')));
        $this->demoLoginEnabled = filter_var(env('SPIV_DEMO_LOGIN_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        $this->demoPassword     = (string) env('SPIV_DEMO_PASSWORD', '');
    }

    public function allowsDemoLogin(): bool
    {
        return $this->identityProvider === 'demo'
            && $this->demoLoginEnabled
            && $this->demoPassword !== '';
    }
}
