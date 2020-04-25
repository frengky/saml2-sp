<?php

namespace Frengky\Saml2\Facades;

use Illuminate\Support\Facades\Facade;

class Saml2Auth extends Facade
{
    protected static function getFacadeAccessor() 
    {
        return \OneLogin\Saml2\Auth::class;
    }
}