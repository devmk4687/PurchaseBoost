<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CommunicationTemplateController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\CsvImportController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/




Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::middleware('auth:sanctum')->group(function() {
        Route::apiResource('campaigns', CampaignController::class)->names('api.campaigns');
        Route::get('campaign-customers', [CampaignController::class, 'customers']);
        Route::get('campaigns/{campaign}/logs', [CampaignController::class, 'logs']);
        Route::post('campaign/{campaign}/publish',[CampaignController::class,'publish']);
        Route::apiResource('message-templates', CommunicationTemplateController::class)->names('api.message-templates');

    });

    Route::apiResource('orders', OrderController::class);

    Route::patch('orders/{order}/return', [OrderController::class, 'returnOrder']);

    Route::post('agent/run',[AgentController::class,'run']);

    Route::post('csv/import',[CsvImportController::class,'import']);

});
