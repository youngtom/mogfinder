<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Config;
use Image;
use Response;
use File;
use Storage;

class FileUpload extends Model
{
    protected $fillable = [];
	private static $guzzleClient = null;
	
	public function getURL() {
		return URL::to($this->path . $this->filename);
	}
	
	public static function boot() {
		parent::boot();
		
		self::saving(function ($file) {
			$file->token = $file->token ?: sha1($file->path . $file->filename . uniqid());
		});
		
		self::deleting(function ($file) {
			Storage::delete($file->getFullPath());
		});
	}
	
	public function resize($width, $height, $method = 'fit') {
		if (!$this->isImage() || (!$width && !$height)) {
			return App::abort(404);
		}
		
		$width = $width ?: null;
		$height = $height ?: null;
		
		$resizedFile = self::where('parent_file_id', '=', $this->id)->where('resize_width', '=', $width)->where('resize_height', '=', $height)->where('resize_method', '=', $method)->first();
		
		if ($resizedFile) {
			return Response::make(File::get($resizedFile->getFullPath()), 200, array('Content-Type' => $resizedFile->filetype, 'Content-Length' => $resizedFile->filesize));
		} else {			
			if ($width && $height) {
				$newDimensions = $width . 'w' . '.' . $height . 'h.' . $method;
			} elseif ($width) {
				$newDimensions = $width . 'w.' . $method;
			} elseif ($height) {
				$newDimensions = $height . 'h.' . $method;
			}
		
			$resizedFile = new FileUpload;
			$resizedFile->path = $this->path . 'thumbnails/';
			$uploadDir = storage_path() . '/' . $resizedFile->path;
			
			if (!file_exists($uploadDir)) {
				\File::makeDirectory($uploadDir, 0777, true);
			}
			
			$resizedFile->filename = str_replace(strrchr($this->filename, '.'), '', $this->filename) . '.' . $newDimensions . strrchr($this->filename, '.');
			
			if ($method == 'fit') {
				$img = Image::make($this->getFullPath())->fit($width, $height);
			} elseif ($method == 'resize') {
				$img = Image::make($this->getFullPath())->resize($width, $height, function ($constraint) {
				    $constraint->aspectRatio();
				});
			}
			
			if ($img && $img->save($resizedFile->getFullPath())) {
				$resizedFile->filesize = $img->filesize();
				$resizedFile->filetype = $img->mime;
				$resizedFile->width = $img->width();
				$resizedFile->height = $img->height();
				$resizedFile->resize_width = $width;
				$resizedFile->resize_height = $height;
				$resizedFile->resize_method = $method;
				$resizedFile->parent_file_id = $this->id;
				$resizedFile->save();
				
				return Response::make(File::get($resizedFile->getFullPath()), 200, array('Content-Type' => $resizedFile->filetype, 'Content-Length' => $resizedFile->filesize));
			}
		}
	}
	
	public function crop($width, $height, $x = 'center', $y = 'center') {
		if (!$this->isImage() || (!$width && !$height)) {
			return App::abort(404);
		}
		
		$width = $width ?: null;
		$height = $height ?: null;
		$xyLoc = $x . 'x.' . $y . 'y';
		
		$croppedFile = self::where('parent_file_id', '=', $this->id)->where('description', '=', $xyLoc)->where('resize_width', '=', $width)->where('resize_height', '=', $height)->where('resize_method', '=', 'cropped')->first();
		
		if ($croppedFile) {
			return Response::make(File::get($croppedFile->getFullPath()), 200, array('Content-Type' => $croppedFile->filetype, 'Content-Length' => $croppedFile->filesize));
		} else {
			if ($width && $height) {
				$newDimensions = $width . 'w' . '.' . $height . 'h.' . $xyLoc;
			} elseif ($width) {
				$newDimensions = $width . 'w.' . $xyLoc;
			} elseif ($height) {
				$newDimensions = $height . 'h.' . $xyLoc;
			}
		
			$croppedFile = new FileUpload;
			$croppedFile->path = $this->path . 'cropped/';
			$uploadDir = storage_path() . '/' . $croppedFile->path;
			
			if (!file_exists($uploadDir)) {
				\File::makeDirectory($uploadDir, 0777, true);
			}
			
			$croppedFile->filename = str_replace(strrchr($this->filename, '.'), '', $this->filename) . '.' . $newDimensions . strrchr($this->filename, '.');
			
			$x = ($x == 'center') ? null : $x;
			$y = ($y == 'center') ? null : $y;
			
			$img = Image::make($this->getFullPath())->crop($width, $height, $x, $y);
			
			if ($img && $img->save($croppedFile->getFullPath())) {
				$croppedFile->filesize = $img->filesize();
				$croppedFile->filetype = $img->mime;
				$croppedFile->width = $img->width();
				$croppedFile->height = $img->height();
				$croppedFile->resize_width = $width;
				$croppedFile->resize_height = $height;
				$croppedFile->resize_method = 'cropped';
				$croppedFile->description = $xyLoc;
				$croppedFile->parent_file_id = $this->id;
				$croppedFile->save();
				
				return Response::make(File::get($croppedFile->getFullPath()), 200, array('Content-Type' => $croppedFile->filetype, 'Content-Length' => $croppedFile->filesize));
			}
		}
	}
	
	public static function saveRemoteFile($url, $path, $filename = null, $overwrite = false) {
		$filename = ($filename) ? $filename : basename($url);
		$path = (substr($path, -1, 1) != '/') ? $path . '/' : $path;
		$fullPath = storage_path() . '/' . $path;
		
		if (!$path || !$filename) {
			return false;
		}
		
		if (!$overwrite && file_exists($fullPath . $filename)) {
			$newFilename = $filename;
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$fileBase = pathinfo($filename, PATHINFO_FILENAME);
			
			$count = 0;
			while (file_exists($fullPath . $newFilename)) {
				$newFilename = $fileBase . '_' . ++$count . '.' . $ext;
			}
			
			$filename = $newFilename;
		}
		
		$file = FileUpload::where('path', '=', $path)->where('filename', '=', $filename)->first();
    	
    	if (!$file) {
	    	$file = new FileUpload;		
			$file->filename = $filename;
			$file->path = $path;
    	}
    	
    	if (!file_exists($fullPath)) {
	    	\File::makeDirectory($fullPath, 0777, true);
    	}
    	
		if (self::$guzzleClient === null) {
			self::$guzzleClient = new \GuzzleHttp\Client();
		}
		
		try {
	        $response = self::$guzzleClient->request('GET', $url, ['sink' => $file->getFullPath()]);
	    } catch (\GuzzleHttp\Exception\RequestException $e) {
		    \File::delete($file->getFullPath());
	        return false;
	    }
	    	        
        $file->filetype = $response->getHeader('Content-Type')[0];
        $file->filesize = $response->getHeader('Content-Length')[0];
        
        if ($file->isImage()) {
			$fileObj = \Image::make($file->getFullPath());
			$file->width = $fileObj->width();
			$file->height = $fileObj->height();
		}
		$file->description = $url;
		$file->save();
        
        return $file;
	}
	
	public function getFullPath() {
		return storage_path() . '/' . $this->path . $this->filename;
	}
	
	public function getWebPath($width = false, $height = false, $method = 'fit', $x = null, $y = null) {
		if ($this->isImage() && ($width || $height)) {
			$width = $width ?: 0;
			$height = $height ?: 0;
			$x = $x ?: 0;
			$y = $y ?: 0;
			if ($method == 'crop') {
				return \URL::to('media/' . $this->token . '/cropped/' . $width . '/' . $height . '/' . $x . '/' . $y . '/' . $this->filename);
			} else {
				return \URL::to('media/' . $this->token . '/thumbnail/' . $width . '/' . $height . '/' . $method . '/' . $this->filename);
			}
		} else {
			return \URL::to('media/' . $this->token . '/' . $this->filename);
		}
	}
	
	public function isImage() {
		return (stristr($this->filetype, 'image'));
	}
}
