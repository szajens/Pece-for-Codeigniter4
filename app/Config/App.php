<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;


class App extends BaseConfig
{

    /**
     * ---------------------------------------------------------------
     * Allowed Domain in Pece Routing
     * ---------------------------------------------------------------
     *
     * Allowed values:
     *
     *  Array: ['example.com', 'sub.example.net', 'example.co.uk']
     *
     * or
     *
     *  Path to Class if you want dynamic return from other, e.g. database
     *
     *  String: '\Namespace\To\Class::returnArray'
     *
     * @var string|array
     */
    public $peceAllowedDomains = ['example.com', 'ci.loc', 'localhost'];

    /**
     * ---------------------------------------------------------------
     * Default limiting to scheme Pece Routing
     * ---------------------------------------------------------------
     *
     * true - only https
     * false - only http
     * null - no limiting
     *
     * @var ?bool
     */
    public $peceDefaultSSL = null;

}
