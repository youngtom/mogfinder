<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use Response;
use App\Libraries\LuaParser;
use Config;
use App\UserDatafile;
use App\FileUpload;
use Storage;
use App\Jobs\ImportUserData;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function getDashboard() {
	    return view('user.dashboard')->with('pageTitle', 'Dashboard');
    }
    
	public function dataUpload() {
	    return view('user.data-uploader')->with('pageTitle', 'Data Uploader');
	}
	
	public function dataUploadHandler(Request $request) {
		$user = Auth::user();
		
		if ($request->hasFile('file') && $request->file('file')->isValid()) {
			$mimeType = $request->file('file')->getMimeType();
			
			if ($mimeType != 'text/plain' || $request->file('file')->getClientOriginalExtension() != 'lua') {
				return Response::json(['success' => false, 'errormsg' => 'Invalid file type uploaded.']);
			}
			
			$path = Config::get('settings.upload_file_dir') . '/users/' . $user->id . '/';
			$fullPath = storage_path() . '/' . $path;
			$filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.]/', '_', $request->file('file')->getClientOriginalName());

	    	if (!file_exists($fullPath)) {
		    	\File::makeDirectory($fullPath, 0777, true);
	    	}
			
			if (!$request->file('file')->move($fullPath, $filename)) {
				return Response::json(['success' => false, 'errormsg' => 'An unknown error occurred']);
			}
			
			$parser = new LuaParser(file_get_contents($fullPath . $filename));
			$data = ($parser) ? $parser->parse() : false;
			
			if ($parser && $data && @$data['MCCSaved']) {
				var_dump($data['MCCSaved']); die;
				$fileMD5 = md5_file($fullPath . $filename);
				$userFile = UserDatafile::where('md5', '=', $fileMD5)->where('user_id', '=', $user->id)->first();
				$ver = explode('.', $data['MCCSaved']['ver']);
				
				if ($userFile) {
					Storage::delete($path . $filename);
					
					return Response::json(['success' => false, 'errormsg' => 'This file has already been processed.']);
				} elseif ($ver[0] < 3) {
					return Response::json(['success' => false, 'errormsg' => 'This file is from an outdated version of the mod. Please download the latest version.']);
				} else {
					$file = new FileUpload;
					$file->filename = $filename;
					$file->path = $path;
					$file->filetype = $mimeType;
					$file->filesize = Storage::size($path . $filename);
					$file->save();
					
					$userFile = new UserDatafile;
					$userFile->user_id = $user->id;
					$userFile->file_id = $file->id;
					$userFile->md5 = $fileMD5;
					$userFile->import_data = json_encode($data['MCCSaved']);
					$userFile->token = substr($file->token, 0, 8) . substr(uniqid(), 0, 8);
					$itemCount = $userFile->getItemCount();
					
					if ($itemCount == 0) {
						return Response::json(['success' => false, 'errormsg' => 'No items were found in the uploaded file. Please re-import your data from the game and try again.']);
					}
					
					$userFile->progress_total = $itemCount;
					$userFile->progress_current = 0;
					$userFile->save();
					
					$user->importUserData($userFile->id);
					//$job = (new ImportUserData($user->id, $userFile->id))->onQueue('high');
				    //$this->dispatch($job);
					
					return Response::json(['success' => true, 'token' => $userFile->token, 'total' => $itemCount, 'reportURL' => url('upload-data/report/' . $userFile->token), 'msg' => 'This process may take a few minutes. You may leave this page at any time (progress will continue in the background)']);
				}
			} else {
				Storage::delete($path . $filename);
				return Response::json(['success' => false, 'errormsg' => 'The format of the uploaded file was invalid. Please be sure you have the latest version of the mod installed.']);
			}
		} else {
			return Response::json(['success' => false, 'errormsg' => 'There was an error uploading the file. Please try again.']);
		}
	}
	
	public function dataResponse($token) {
		$userFile = UserDatafile::where('user_id', '=', Auth::user()->id)->where('token', '=', $token)->first();
		
		if ($userFile) {
			return Response::json($userFile->getResponseDataArray());
		} else {
			return \App::abort();
		}
	}
}
