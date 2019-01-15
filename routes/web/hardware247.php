<?php
/*
|--------------------------------------------------------------------------
| Asset Routes
|--------------------------------------------------------------------------
|
| Register all the asset routes.
|
*/
Route::group(
    ['prefix' => 'hardware247',
    'middleware' => ['auth']],
    function () {

        Route::get( 'bulkaudit',  [
            'as' => 'assets247.bulkaudit',
            'uses' => 'Assets247\Assets247Controller@quickScan'
        ]);

        # Asset Maintenances
        Route::resource('maintenances', 'AssetMaintenancesController', [
            'parameters' => ['maintenance' => 'maintenance_id', 'asset' => 'asset_id']
        ]);

        Route::get('requested', [ 'as' => 'assets247.requested', 'uses' => 'Assets247\Assets247Controller@getRequestedIndex']);

        Route::get('scan', [
            'as' => 'asset.scan',
            'uses' => 'Assets247\Assets247Controller@scan'
        ]);

        Route::get('audit/{id}', [
            'as' => 'asset.audit.create',
            'uses' => 'Assets247\Assets247Controller@audit'
        ]);

        Route::post('audit/{id}', [
            'as' => 'asset.audit.store',
            'uses' => 'Assets247\Assets247Controller@auditStore'
        ]);


        Route::get('history', [
            'as' => 'asset.import-history',
            'uses' => 'Assets247\Assets247Controller@getImportHistory'
        ]);

        Route::post('history', [
            'as' => 'asset.process-import-history',
            'uses' => 'Assets247\Assets247Controller@postImportHistory'
        ]);

        Route::get('/bytag', [
            'as'   => 'findbytag/hardware247',
            'uses' => 'Assets247\Assets247Controller@getAssetByTag'
        ]);

        Route::get('{assetId}/clone', [
            'as' => 'clone/hardware247',
            'uses' => 'Assets247\Assets247Controller@getClone'
        ]);

        Route::post('{assetId}/clone', 'Assets247\Assets247Controller@postCreate');

        Route::get('{assetId}/checkout', [
            'as' => 'checkout/hardware247',
            'uses' => 'Assets247\AssetCheckoutController@create'
        ]);
        Route::post('{assetId}/checkout', [
            'as' => 'checkout/hardware247',
            'uses' => 'Assets247\AssetCheckoutController@store'
        ]);
        Route::get('{assetId}/checkin/{backto?}', [
            'as' => 'checkin/hardware247',
            'uses' => 'Assets247\AssetCheckinController@create'
        ]);

        Route::post('{assetId}/checkin/{backto?}', [
            'as' => 'checkin/hardware247',
            'uses' => 'Assets247\AssetCheckinController@store'
        ]);
        Route::get('{assetId}/view', [
            'as' => 'hardware247.view',
            'uses' => 'Assets247\Assets247Controller@show'
        ]);
        Route::get('{assetId}/qr_code', [ 'as' => 'qr_code/hardware247', 'uses' => 'Assets247\Assets247Controller@getQrCode' ]);
        Route::get('{assetId}/barcode', [ 'as' => 'barcode/hardware247', 'uses' => 'Assets247\Assets247Controller@getBarCode' ]);
        Route::get('{assetId}/restore', [
            'as' => 'restore/hardware247',
            'uses' => 'Assets247\Assets247Controller@getRestore'
        ]);
        Route::post('{assetId}/upload', [
            'as' => 'upload/asset',
            'uses' => 'Assets247\AssetFilesController@store'
        ]);

        Route::get('{assetId}/showfile/{fileId}/{download?}', [
            'as' => 'show/assetfile',
            'uses' => 'Assets247\AssetFilesController@show'
        ]);

        Route::delete('{assetId}/showfile/{fileId}/delete', [
            'as' => 'delete/assetfile',
            'uses' => 'Assets247\AssetFilesController@destroy'
        ]);


        Route::post(
            'bulkedit',
            [
                'as'   => 'hardware247/bulkedit',
                'uses' => 'Assets247\BulkAssets247Controller@edit'
            ]
        );
        Route::post(
            'bulkdelete',
            [
                'as'   => 'hardware247/bulkdelete',
                'uses' => 'Assets247\BulkAssets247Controller@destroy'
            ]
        );
        Route::post(
            'bulksave',
            [
                'as'   => 'hardware247/bulksave',
                'uses' => 'Assets247\BulkAssets247Controller@update'
            ]
        );

        # Bulk checkout / checkin
         Route::get( 'bulkcheckout',  [
                 'as' => 'hardware247/bulkcheckout',
                 'uses' => 'Assets247\BulkAssets247Controller@showCheckout'
         ]);
        Route::post( 'bulkcheckout',  [
            'as' => 'hardware247/bulkcheckout',
            'uses' => 'Assets247\BulkAssets247Controller@storeCheckout'
        ]);




});


Route::resource('hardware247', 'Assets247\Assets247Controller', [
    'middleware' => ['auth'],
    'parameters' => ['asset' => 'asset_id']
]);

/*--- locationusers API ---*/

Route::group(['prefix' => 'locationusers'], function () {

    Route::get('{location}/users',
        [
            'as'=>'api.locationusers.viewusers',
            'uses'=>'LocationUsersController@getDataViewUsers'
        ]
    );

    Route::get('{location}/assets',
        [
            'as'=>'api.locationusers.viewassets',
            'uses'=>'LocationUsersController@getDataViewAssets'
        ]
    );

    // Do we actually still need this, now that we have an API?
    Route::get('{location}/check',
        [
            'as' => 'api.locationusers.check',
            'uses' => 'LocationUsersController@show'
        ]
    );

    // Route::get( 'selectlist',  [
    //     'as' => 'locationusers.selectlist',
    //     'uses' => 'LocationUsersController@selectlist'
    // ]);

    Route::post('{location}/associate',
        ['uses' => 'LocationUsersController@associate',
        'as' => 'locationusers.associate']
    );
}); // locationusers group



// Route::resource('locationusers', 'LocationUsersController',
//     [
//         'names' =>
//             [
//                 'index' => 'api.locationusers.index',
//                 'show' => 'api.locationusers.show',
//                 'store' => 'api.locationusers.store',
//                 'update' => 'api.locationusers.update',
//                 'destroy' => 'api.locationusers.destroy'
//             ],
//         'except' => ['create', 'edit'],
//         'parameters' => ['location' => 'location_id']
//     ]
// ); // locationusers resource
