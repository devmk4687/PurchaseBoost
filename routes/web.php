<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignAgentController;
use App\Http\Controllers\CustomerSegmentController;
use App\Http\Controllers\LoyaltyMemberController;
use App\Http\Controllers\OrderImportController;
use App\Http\Controllers\MessageTemplateImageController;
use App\Http\Controllers\MessageTemplateController;
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

Auth::routes();

//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard')->middleware('auth');



Route::get('/auth/google', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

Route::middleware('auth')->group(function () {
    Route::resource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/run', [CampaignController::class, 'run'])->name('campaigns.run');
    Route::get('/campaign-agent', [CampaignAgentController::class, 'index'])->name('campaign-agent.index');
    Route::post('/campaign-agent', [CampaignAgentController::class, 'store'])->name('campaign-agent.store');
    Route::get('/customer-segments', [CustomerSegmentController::class, 'index'])->name('customer-segments.index');
    Route::post('/customer-segments', [CustomerSegmentController::class, 'store'])->name('customer-segments.store');
    Route::resource('loyalty-members', LoyaltyMemberController::class)->except(['show']);
    Route::get('/orders/import', [OrderImportController::class, 'index'])->name('orders.import.index');
    Route::post('/orders/import', [OrderImportController::class, 'store'])->name('orders.import.store');
    Route::resource('message-templates', MessageTemplateController::class)->except(['show']);
    Route::post('message-templates/upload-image', [MessageTemplateImageController::class, 'store'])->name('message-templates.upload-image');
    Route::post('loyalty-members/import', [LoyaltyMemberController::class, 'import'])->name('loyalty-members.import');
    
    // status toggle
    Route::post('/campaigns/{campaign}/toggle', [CampaignController::class, 'toggleStatus'])->name('campaign.toggle');
});
