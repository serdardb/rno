<?php

use App\Http\Controllers\RnoController;
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
Route::group(['middleware' => ['cors'],], function () {
    Route::get('rno', [RnoController::class, 'index']);
    Route::get('history', [RnoController::class, 'history']);
    Route::get('setHistory', [RnoController::class, 'setHistory']);
    Route::get('setErrorVideo', [RnoController::class, 'setErrorVideo']);
    Route::get('lastHistories', [RnoController::class, 'lastHistories']);
});
