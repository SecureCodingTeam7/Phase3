<?php 
/**
 * Copyright 2012 Armand Niculescu - media-division.com
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
// get the file request, throw error if nothing supplied
 
// hide notices
@ini_set('error_reporting', E_ALL & ~ E_NOTICE);
 
//- turn off compression on the server
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 'Off');
 
if(!isset($_REQUEST['file']) || empty($_REQUEST['file'])) 
{
	header("HTTP/1.0 400 Bad Request");
	exit;
}
 
// sanitize the file request, keep just the name and extension
// also, replaces the file location with a preset one ('./myfiles/' in this example)
$file_path  = $_REQUEST['file'];
$path_parts = pathinfo($file_path);
$file_name  = $path_parts['basename'];
$file_ext   = $path_parts['extension'];
$file_path  = sys_get_temp_dir()."/". $file_name;

if(!file_exists($file_path)){
	exit("file doesn't exists");
}
			$type = "application/x-zip-compressed";
			
			header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public", false);
            header("Content-Description: File Transfer");
            header("Content-Type: " . $type);
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=\"" . $file_name . "\";");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: " . filesize($file_path));
            // Send file for download
            if ($stream = fopen($file_path, 'rb')){
                while(!feof($stream) && connection_status() == 0){
                    //reset time limit for big files
                    set_time_limit(0);
                    print(fread($stream,1024*8));
                    flush();
                }
                fclose($stream);
                unlink($file_path);
            }
			else{
            // Requested file does not exist (File not found)
            echo("Requested file does not exist");
            die();
        }
    
?>
