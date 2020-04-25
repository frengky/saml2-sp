<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;

use App\User;

class SsoController extends Controller
{
    use \Frengky\Saml2\Traits\Sso;

    protected function provideUser($nameId, array $attributes) : Authenticatable
    {
        $email = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/upn'][0];
        $uid = $attributes['uid'][0];

        $user = User::where('email', $email)->first();
        if (! is_null($user)) {
            return $user;
        }
        return User::create([
            'name' => $uid,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => ''
        ]);
    }
}