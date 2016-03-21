<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


use App\User;
use App\Item;
use App\Character;
use App\Realm;
use App\Race;
use App\Libraries\LuaParser;
use App\BnetWowApi;
use App\UserItem;
use App\ItemLocation;
use App\ItemSource;
use App\ItemSourceType;
use App\Mogslot;
use App\FileUpload;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::get('/', 'HomeController@index');

/*
Route::get('/test', function () {
	$character = \App\Character::find(162);
	
	$character->importBnetData();
});
*/

Route::group(['middleware' => 'web'], function () {
    Route::auth();
    
    //Route::get('/', 'HomeController@index');
	Route::get('/home', 'HomeController@index');
    
    //Admin helpers routes
    Route::get('/items/set-mogslot-icons/{mogslotID?}/{iconID?}', 'ItemsController@setMogslotIcons');
    
    //Items routes
    Route::get('/items', 'ItemsController@index');
    Route::get('/items/duplicates', 'ItemsController@duplicates');
    Route::get('/items/duplicates/{selectedCharacterURL}', 'ItemsController@duplicates');
    Route::get('/items/{group}/{category}/{mogslotURL}', 'ItemsController@showSlot')->where('group', '(armor|weapons)');
    
    //user routes
    Route::get('/dashboard', 'UserController@getDashboard');
    Route::get('/user/upload-data', 'UserController@dataUpload');
    Route::post('/user/upload-data', 'UserController@dataUploadHandler');
    Route::get('/user/upload-data/report/{token}', 'UserController@dataResponse');
	
	/* uploaded file routes */
	
	Route::get('/media/{token}/cropped/{width}/{height}/{x}/{y}/{filename}', function($token, $width, $height, $x, $y, $filename) {
		$file = FileUpload::where('token', '=', $token)->where('filename', '=', $filename)->first();
		
		if (!$file) {
			App::abort(404);
		}
		
		return $file->crop($width, $height, $x, $y);
	})->where([
	    'width' => '[0-9]+',
	    'height' => '[0-9]+',
	    'x' => '[0-9]+',
	    'y' => '[0-9]+'
	]);

	Route::get('/media/{token}/thumbnail/{width}/{height}/{method}/{filename}', function($token, $width, $height, $method, $filename) {
		$file = FileUpload::where('token', '=', $token)->where('filename', '=', $filename)->first();
		
		if (!$file) {
			App::abort(404);
		}
		
		return $file->resize($width, $height, $method);
	})->where([
	    'width' => '[0-9]+',
	    'height' => '[0-9]+',
	    'method' => '(fit|resize)'
	]);
	
	Route::get('/media/{token}/thumbnail/{width}/{height}/{filename}', function($token, $width, $height, $filename) {
		$file = FileUpload::where('token', '=', $token)->where('filename', '=', $filename)->first();
		
		if (!$file) {
			App::abort(404);
		}
		
		return $file->resize($width, $height);
	})->where([
	    'width' => '[0-9]+',
	    'height' => '[0-9]+'
	]);
	
	Route::get('/media/{token}/{filename}', function($token, $filename) {
		$file = FileUpload::where('token', '=', $token)->where('filename', '=', $filename)->first();
		
		if (!$file) {
			App::abort(404);
		}
		
		return Response::make(File::get($file->getFullPath()), 200, array('Content-Type' => $file->filetype, 'Content-Length' => $file->filesize));
	});
});