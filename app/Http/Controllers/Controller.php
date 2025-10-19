<?php

namespace App\Http\Controllers;

use App\Http\Traits\ArchiveTrait;
use App\Http\Traits\ResponsesTrait;
use App\Http\Traits\HasDigitalOceanSpaces;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

use App\Http\Traits\MediaTrait;

abstract class Controller
{
    use AuthorizesRequests, ResponsesTrait, ValidatesRequests, ArchiveTrait,MediaTrait, HasDigitalOceanSpaces;
}
