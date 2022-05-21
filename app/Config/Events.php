<?php

namespace Config;

use CodeIgniter\Events\Events;


Events::on('post_controller_constructor', '\Config\Services::pece'); //or baseController