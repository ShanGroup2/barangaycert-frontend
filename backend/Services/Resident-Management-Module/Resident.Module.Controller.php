<?php
require_once __DIR__ . '/Resident.Module.Model.php';
require_once __DIR__ . '/Resident.Module.View.php';

class Resident_Module_Controller
{
    public static function dispatch($request)
    {
        // T05: Add New Resident
        Route::post('api/resident/add', function ($request) {
            $data = Resident_Module_Model::add($request['body']);
            return Resident_Module_View::formatResponse($data);
        });

        // T06: Edit Resident
        Route::post('api/resident/edit', function ($request) {
            $data = Resident_Module_Model::edit($request['body']);
            return Resident_Module_View::formatResponse($data);
        });

        // T07: Delete Resident
        Route::post('api/resident/delete', function ($request) {
            $data = Resident_Module_Model::delete($request['body']);
            return Resident_Module_View::formatResponse($data);
        });

        // T08: Export Residents List
        Route::get('api/resident/export', function ($request) {
            $data = Resident_Module_Model::export();
            return Resident_Module_View::formatResponse($data);
        });

        // Load residents list
        Route::get('api/resident/list', function ($request) {
            $data = Resident_Module_Model::list();
            return Resident_Module_View::formatResponse($data);
        });

        // Search residents
        Route::get('api/resident/search', function ($request) {
            $queryParams = $request['query'] ?? $_GET ?? array();
            $data = Resident_Module_Model::search($queryParams);
            return Resident_Module_View::formatResponse($data);
        });
    }
}
