<?php

/**
 * Please define named route with prefix saml2sp.*
 * for these actions: login, metadata, acs, sls, logout
 */

Route::group(['prefix' => 'saml2'], function($router) {
    $router->get('login', 'SsoController@login')->name('saml2sp.login');
    $router->get('metadata', 'SsoController@metadata')->name('saml2sp.metadata');
    $router->match(['get','post'], 'acs', 'SsoController@acs')->name('saml2sp.acs');
    $router->get('sls', 'SsoController@sls')->name('saml2sp.sls');
    $router->get('logout', 'SsoController@logout')->name('saml2sp.logout');
});