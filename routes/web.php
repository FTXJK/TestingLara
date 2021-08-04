<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\ContactController;

use App\Http\Middleware\CheckJSON;
use App\Http\Middleware\ValidateForm;

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

/*
Route::get('/', function () {
    return view('welcome');
});*/
Route::get('/', [TokenController::class, 'processToken'])->middleware('check');

//Route::get('/profile',  'ProfileController@showProfile' );
Route::get('/profile',  [ProfileController::class, 'showProfile'])->name('profile');
 
Route::post('/token', [TokenController::class, 'processToken'])->middleware(['json', 'validator']);
Route::get('/contact', [ContactController::class, 'addContact'])->name('contact');