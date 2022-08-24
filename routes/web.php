<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//$mainRoutes = function() {
    Route::middleware(['cors'])->prefix('webhooks')->group(function () {
        Route::post('/tilda/school', [\App\Http\Controllers\Webhooks\Requests\RequestParserWebhookController::class, 'prepareDataFromTildaSchool']);
        Route::post('/tilda/monro', [\App\Http\Controllers\Webhooks\Requests\RequestParserWebhookController::class, 'prepareDataFromTildaMonro']);
    });
//};

//Route::group(array('domain' => 'receiver-leads.easy-mo.ru'), $mainRoutes);
