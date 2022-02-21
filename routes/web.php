<?php

/** @var \Laravel\Lumen\Routing\Router $router */
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// years routes
$router->get('getActiveYears', ['as' => 'years.list', 'uses' => 'YearsController@list']);
$router->post('year/create', ['as' => 'years.create', 'uses' => 'YearsController@create']);
$router->post('year/delete', ['as' => 'years.delete', 'uses' => 'YearsController@delete']);
// AuthTable routes
$router->get('auth/list', ['as' => 'years.authList', 'uses' => 'YearsController@authList']);
$router->post('auth/save', ['as' => 'years.authSave', 'uses' => 'YearsController@authSave']);

//  users routes
$router->get('users/list', ['as' => 'users.list', 'uses' => 'UsersController@list']);
$router->post('users/login', ['as' => 'users.login', 'uses' => 'UsersController@login']);
$router->post('users/create', ['as' => 'users.create', 'uses' => 'UsersController@create']);
$router->post('users/update', ['as' => 'users.update', 'uses' => 'UsersController@update']);

// names routes
$router->get('names/list', ['as' => 'names.list', 'uses' => 'NamesController@list']);
$router->post('names/save', ['as' => 'names.save', 'uses' => 'NamesController@save']);

// project routes
$router->post('projects/list', ['as' => 'projects.list', 'uses' => 'ProjectsController@list']);
$router->post('projects/simple_list', ['as' => 'projects.simple_list', 'uses' => 'ProjectsController@simple_list']);
$router->get('projects/projectData/{year}/{projectNumber}', ['as' => 'projects.getProjectData', 'uses' => 'ProjectsController@getProjectData' ]);
$router->post('projects/save', ['as' => 'project.save', 'uses' => 'ProjectsController@save']);
$router->post('projects/monthParts', ['as' => 'project.monthParts', 'uses' => 'ProjectsController@monthParts']);

$router->post('projects/comp_parts', ['as' => 'project.comp_parts', 'uses' => 'ProjectsController@comp_parts']);

//  partidas routes
$router->get('parts/list_all', ['as' => 'parts.list_all', 'uses' => 'PartsController@list_all']);
$router->get('parts/list', ['as' => 'parts.list', 'uses' => 'PartsController@list']);
$router->post('parts/save', ['as' => 'parts.save', 'uses' => 'PartsController@save']);
$router->post('parts/update', ['as' => 'parts.update', 'uses' => 'PartsController@update']);
$router->post('part/delete', ['as' => 'part.delete', 'uses' => 'PartsController@delete']);

$router->post('partList/create', ['as' => 'parts.plCreate', 'uses' => 'PartsController@plCreate']);
$router->post('partList/update', ['as' => 'parts.plUpdate', 'uses' => 'PartsController@plUpdate']);

//  incomes routes
$router->get('incomes/last_id/{year}', ['as' => 'incomes.last_id', 'uses' => 'IncomesController@last_id']);
$router->post('incomes/create', ['as' => 'incomes.create', 'uses' => 'IncomesController@create']);
$router->post('incomes/update', ['as' => 'incomes.update', 'uses' => 'IncomesController@update']);
$router->get('incomes/getSF/{incId}', ['as' => 'incomes.getSF', 'uses' => 'IncomesController@getSF']);
$router->get('incomes/sf_proy_list/{year}', ['as' => 'incomes.sf_proy_list', 'uses' => 'IncomesController@sf_proy_list']);
$router->post('incomes/prevSF', ['as' => 'incomes.prevSF', 'uses' => 'IncomesController@prevSF']);
$router->post('incomes/sfVal_save', ['as' => 'incomes.sfVal_save', 'uses' => 'IncomesController@sfVal_save']);
$router->post('incomes/sfVal_delete', ['as' => 'incomes.sfVal_delete', 'uses' => 'IncomesController@sfVal_delete']);
$router->post('incomes/comp/create', ['as' => 'income.comp_create', 'uses' => 'IncomesController@comp_create']);
$router->post('incomes/comp/update', ['as' => 'income.comp_update', 'uses' => 'IncomesController@comp_update']);
$router->post('incomes/comp/delete', ['as' => 'income.comp_delete', 'uses' => 'IncomesController@comp_delete']);
$router->get('incomes/compLastCover/{sfId}', ['as' => 'income.compLastCover', 'uses' => 'IncomesController@compLastCover']);
$router->get('incomes/checkInfo/{checkId}', ['as' => 'income.checkInfo', 'uses' => 'IncomesController@checkInfo']);
$router->post('incomes/inc_return', ['as' => 'income.inc_return', 'uses' => 'IncomesController@inc_return']);
$router->post('incomes/del_return', ['as' => 'income.del_return', 'uses' => 'IncomesController@del_return']);
$router->post('incomes/full_list', ['as' => 'income.full_list', 'uses' => 'IncomesController@full_list']);
$router->post('incomes/incXProj', ['as' => 'income.incXProj', 'uses' => 'IncomesController@incXProj']);

// outcomes routes
$router->post('outcomes/avalible', ['as' => 'outcomes.avalible', 'uses' => 'OutcomesController@avalible']);
$router->post('outcomes/lastId', ['as' => 'outcomes.lastId', 'uses' => 'OutcomesController@lastId']);
$router->post('outcomes/testCheckNumber', ['as' => 'outcomes.testCheckNumber', 'uses' => 'OutcomesController@testCheckNumber']);
$router->post('outcomes/create', ['as' => 'outcomes.create', 'uses' => 'OutcomesController@create']);
$router->post('outcomes/update', ['as' => 'outcomes.update', 'uses' => 'OutcomesController@update']);
$router->post('outcomes/get_all_outcomes', ['as' => 'outcomes.get_all_outcomes', 'uses' => 'OutcomesController@get_all_outcomes']);
$router->post('outcomes/get_all_outXProy', ['as' => 'outcomes.get_all_outXProy', 'uses' => 'OutcomesController@get_all_outXProy']);
$router->get('outcomes/proy_out/{year}', ['as' => 'outcomes.proy_out', 'uses' => 'OutcomesController@proy_out']);
$router->get('outcomes/get_outcome/{id}', ['as' => 'outcomes.get_outcome', 'uses' => 'OutcomesController@get_outcome']);
$router->post('outcomes/set_status', ['as' => 'outcomes.set_status', 'uses' => 'OutcomesController@set_status']);
$router->post('outcomes/delete', ['as' => 'outcomes.delete', 'uses' => 'OutcomesController@delete']);
$router->post('outcomes/gnc', ['as' => 'outcomes.gnc', 'uses' => 'OutcomesController@gnc']);
$router->post('outcomes/comp/create', ['as' => 'outcomes.comp_create', 'uses' => 'OutcomesController@comp_create']);
$router->post('outcomes/comp/delete', ['as' => 'outcomes.comp_delete', 'uses' => 'OutcomesController@comp_delete']);
$router->post('outcomes/comp/test', ['as' => 'outcomes.comp_test', 'uses' => 'OutcomesController@comp_test']);
$router->post('outcomes/comp/bill_create', ['as' => 'outcomes.bill_create', 'uses' => 'OutcomesController@bill_create']);
$router->post('outcomes/comp/bill_delete', ['as' => 'outcomes.bill_delete', 'uses' => 'OutcomesController@bill_delete']);

// people routes
$router->get('people/list', ['as' => 'people.list', 'uses' => 'PeopleController@list']);

// print routes
$router->get('print/income/{start}[/{end}]', ['as' => 'print.income', 'uses' => 'PrintController@income']);
$router->get('print/income_comp/{incomeId}/{checkingId}', ['as' => 'print.income_comp', 'uses' => 'PrintController@income_comp']);
$router->get('print/poliza/{start}[/{end}]', ['as' => 'print.poliza', 'uses' => 'PrintController@poliza']);
$router->get('print/cheque/{start}[/{end}]', ['as' => 'print.cheque', 'uses' => 'PrintController@cheque']);
$router->get('print/recibo_gnc/{outId}', ['as' => 'print.recibo_gnc', 'uses' => 'PrintController@recibo_gnc']);
$router->get('print/SFgobal_comp/{incId}', ['as' => 'print.SFgobal_comp', 'uses' => 'PrintController@SFgobal_comp']);
