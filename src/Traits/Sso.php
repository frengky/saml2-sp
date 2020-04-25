<?php

namespace Frengky\Saml2\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;

use Frengky\Saml2\Facades\Saml2Auth;

trait Sso
{
    protected $retrieveParametersFromServer = true;

    /**
     * Initiate login.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $url = Saml2Auth::login(null, [], false, false, true);
        
        // If AuthNRequest ID need to be saved in order to later validate it, do instead
        // $request->session()->put('ssoAuthNRequestID', Saml2Auth::getLastRequestID());
        return redirect($url);
    }

    /**
     * Metadata endpoint (for IdP).
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function metadata(Request $request)
    {
        $settings = Saml2Auth::getSettings();
        
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (! empty($errors)) 
        {
            abort(500, implode(', ', $errors));
        }

        return response($metadata, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Assertion Consumer Service (ACS) endpoint.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function acs(Request $request)
    {
        $requestId = $request->session()->get('ssoAuthNRequestID', null);

        Saml2Auth::processResponse($requestId);
        $errors = Saml2Auth::getErrors();

        if (! empty($errors)) 
        {
            abort(500, implode(', ', $errors));
        }

        if (Saml2Auth::isAuthenticated()) 
        {
            $nameId = Saml2Auth::getNameId();
            $attributes = Saml2Auth::getAttributes();

            $user = $this->provideUser($nameId, $attributes);
            Auth::login($user);

            $request->session()->put('ssoAttributes', $attributes);
            $request->session()->put('ssoNameId', $nameId);
            $request->session()->put('ssoNameIdFormat', Saml2Auth::getNameIdFormat());
            $request->session()->put('ssoNameIdNameQualifier', Saml2Auth::getNameIdNameQualifier());
            $request->session()->put('ssoNameIdSPNameQualifier', Saml2Auth::getNameIdSPNameQualifier());
            $request->session()->put('ssoSessionIndex', Saml2Auth::getSessionIndex());

            $request->session()->forget('ssoAuthNRequestID');

            $relayState = $request->input('RelayState');

            // Redirect to relayState if it was not a login url (loop)
            // $loginUrl = action('\\'.get_class($this).'@login');
            $loginUrl = route('saml2sp.login');
            if (! empty($relayState) && $relayState != $loginUrl) {
                return redirect($relayState); // relayState url was set from Saml2Auth::login($url)
            }

            return $this->loggedIn($request);
        }

        abort(401, 'Unauthorized');
    }

    /**
     * Single Logout Service (SLS) endpoint.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function sls(Request $request)
    {
        $requestId = $request->session()->get('ssoLogoutRequestID', null);

        Saml2Auth::processSLO(false, $requestId, $this->retrieveParametersFromServer);
        $errors = Saml2Auth::getErrors();

        if (! empty($errors)) {
            abort(500, implode(', ', $errors));
        }
        
        Auth::logout();
        $request->session()->forget([
            'ssoAttributes', 'ssoNameId', 'ssoNameIdFormat', 'ssoNameIdNameQualifier', 'ssoNameIdSPNameQualifier', 'ssoSessionIndex'
        ]);

        return $this->loggedOut($request);
    }

    /**
     * Initiate Logout.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function logout(Request $request)
    {
        $returnTo = null;
        $parameters = [];

        $nameId = $request->session()->get('ssoNameId', null);
        $sessionIndex = $request->session()->get('ssoSessionIndex', null);
        $nameIdFormat = $request->session()->get('ssoNameIdFormat', null);
        $nameIdNameQualifier = $request->session()->get('ssoNameIdNameQualifier', null);
        $nameIdSPNameQualifier = $request->session()->get('ssoNameIdSPNameQualifier', null);

        $url = Saml2Auth::logout($returnTo, $parameters, $nameId, 
            $sessionIndex, true, $nameIdFormat, $nameIdNameQualifier, $nameIdSPNameQualifier);

        return redirect($url);
    }

    /**
     * Return the Authenticable (User model) to login into the application.
     *
     * @param  string $nameId
     * @param  array $attributes
     * @return Authenticatable
     */
    protected function provideUser($nameId, array $attributes) : Authenticatable
    {
        //
    }

    /**
     * The user has logged into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedIn(Request $request)
    {
        return redirect('/');
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        return redirect('/');
    }
}