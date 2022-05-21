<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Libraries\Pece;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Router\Router;

class Services extends BaseService
{

    /*
     * ---------------------------------------------------------------
     * Pece routing
     * ---------------------------------------------------------------
     */
    public static function pece(?Router $router = null, ?IncomingRequest $request = null, $getShared = true){

        if ($getShared) {
            return static::getSharedInstance('pece', $router, $request);
        }

        $router ??= Services::router();
        $request ??= Services::request();

        return new Pece($router, $request);
    }


}
