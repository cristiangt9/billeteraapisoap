<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('v1/{key}/server', [
    'as' => 'zoap.server.wsdl',
    'uses' => '\Viewflex\Zoap\ZoapController@server'
]);

Route::post('v1/{key}/server', [
    'as' => 'zoap.server',
    'uses' => '\Viewflex\Zoap\ZoapController@server'
]);


