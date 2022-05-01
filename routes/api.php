<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::get('conciliacion/limpiar_mal_hechas', 'ConciliacionController@limpiarConciliacionesMalHechas');

Route::get('cuotas/conciliadas/{from}/{to}/{initial}','ConciliacionController@getCuotasConciliadas');
Route::get('pagos/conciliados/{from}/{to}/{initial}','ConciliacionController@getPagosConciliados');

Route::get('pago','PagoController@index');
Route::post('pago/setP2','PagoController@setP2');
Route::get('cuota','CuotaController@index');
Route::post('cuota/poliza','CuotaController@getAllCuotasPoliza');
Route::post('conciliar','ConciliacionController@conciliar');

Route::post('pago/fileupload', 'PagoController@uploadCSV');
Route::post('listoper/fileupload', 'ListOperController@uploadCSV');
Route::post('cuota/update','CuotaController@actualizarCuotas');
Route::post('cuota/updateFromDate','CuotaController@actualizarCuotasDesdeFecha');


Route::get('conciliar/auto/{tipo?}','ConciliacionController@conciliarAuto');
Route::get('conciliar/debcre','ConciliacionController@conciliarDebitadaAcreditada');
Route::get('conciliar/agroup','ConciliacionController@conciliarAutoAgrupandoMes');
Route::get('conciliar/agroup_nropersona','ConciliacionController@conciliarAutoAgrupandoMesNroPersona');
Route::get('conciliar/pack/auto/{tipo?}','ConciliacionController@conciliarPackAuto');

Route::get('Testing', function(){ return 'Laravel On!'; });

Route::get('pack/setData','PackController@setDataCuotas');

Route::get('anulada/setAnuladas','AnuladaController@setCuotasAnuladas');


Route::get('conciliacion/{conciliacion_id}','ConciliacionController@getConciliacion');
Route::delete('conciliacion/{conciliacion_id}','ConciliacionController@delConciliacion');


Route::post('comentario', 'ComentarioController@save');