<?php
namespace App\Http\Traits;

use App\FileUpload;

trait FileHandler {
	public function getFile($label) {
		$field = (stristr($label, '_id')) ? $label  : $label . '_id';		
		
		if ($this->$field) {
			$file = FileUpload::find($this->$field);
			
			if ($file) {
				return $file;
			}
		}
		
		return false;
	}
	
	public function buildFileInput($field, $label = '', $width = false, $height = false, $type = 'image') {
		$label = $label ?: ucwords(str_replace('_', ' ', str_replace('_id', '', $field)));
		$file = $this->getFile($field);
		
		$hidden = ($file) ? 'hidden' : '';
		$notHidden = ($file) ? '' : 'hidden';
		
		$out = '<div class="file-upload-wrapper control-group" data-file-type="' . $type . '">';
		$out .= '<label class="control-label" for="thumbnail">' . $label . ':</label>';
		$out .= '<input type="hidden" class="file-field" id="' . $field . '" name="' . $field . '" value="' . $this->$field . '" />';
		$out .= '<div class="controls"><div class="thumbnail-container">';
		
		if ($file) {
			if ($file->isImage()) {
				$out .= '<img src="' . $file->getWebPath($width, $height, 'resize') . '" />';
			} else {
				$out .= '<a href="' . $file->getWebPath() . '">' . $file->filename . '</a>';
			}
		}
		
		$out .= '</div><div class="thumbnail-buttons"><span class="btn btn-info fileinput-button">';
		$out .= '<span class="add-btn-text ' . $hidden . '"><i class="icon-plus icon-white"></i> <span>Add ' . ucwords($type) . '</span></span><span class="change-btn-text ' . $notHidden . '"><span>Change ' . ucwords($type) . '</span></span>';
		$out .= '<input class="fileupload-input" type="file" multiple data-url="' . URL::to('admin/files/add') . '" /></span>';
		$out .= '<span class="remove-btn btn btn-danger ' . $notHidden . '">Remove ' . ucwords($type) . '</span></div></div></div>';
		return $out;
	}
}