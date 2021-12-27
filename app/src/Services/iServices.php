<?php
namespace App\Services;

use Sz\Config\Uri;

Interface iServices {
    public function run(Uri $uri);
}