<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;
use App\FileUpload;

class ImportIconImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:import-icons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	    $path = 'app/downloads/icons/';
	           
        $files = Storage::disk('local')->files($path);
        
        $this->info('Importing ' . count($files) . ' icons from: ' . $path);
        
        foreach ($files as $_file) {
	        $filename = str_replace($path, '', $_file);
	        
	        if (stristr($filename, ' ')) {
		        $newFilename = str_replace(' ', '_', $filename);
		        $newFilename = str_replace('_.', '.', $newFilename);
		        Storage::disk('local')->move($path . $filename, $path . $newFilename);
		        $filename = $newFilename;
	        }
	        
	        $file = FileUpload::where('path', '=', $path)->where('filename', '=', $filename)->first();
    	
	    	if (!$file) {
		    	$file = new FileUpload;		
				$file->filename = $filename;
				$file->path = $path;
		    	        
		        $file->filetype = Storage::mimeType($path . $filename);
		        $file->filesize = Storage::size($path . $filename);
		        
		        if ($file->isImage()) {
					$fileObj = \Image::make($file->getFullPath());
					$file->width = $fileObj->width();
					$file->height = $fileObj->height();
				}
				$file->save();
				
				$this->line('Imported icon: ' . $filename);
	    	}
        }
    }
}
