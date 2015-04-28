<?php
/*
Plugin Name: Mapa Educativo - Leaflet Uploader
Plugin URI: 
Description: 
Version: 1.0.0
Author:
Author URI: 
License: GPLv2
*/

add_action('admin_menu', 'my_plugin_menu');

function my_plugin_menu() {
	add_menu_page('Mapas Leaflet', 'Subir Mapa Leaflet', 'manage_options', 'my-plugin.php', 'my_plugin_page');
}

function my_plugin_page(){
?>
<div class="wrap">
<h2>Mapas Leaflet</h2>

<br class="clear">
	<h4>Suba el .zip que contiene los archivo del Mapa Leaflet</h4>
<?php /*	
	<p class="install-help">If you have a plugin in a .zip format, you may install it by uploading it here.</p>
        <form action="http://localhost/dev/wp2014/wp-admin/update.php?action=upload-plugin" class="wp-upload-form" enctype="multipart/form-data" method="post">
		<input type="hidden" value="5874ec4046" name="_wpnonce" id="_wpnonce"><input type="hidden" value="/dev/wp2014/wp-admin/plugin-install.php?tab=upload" name="_wp_http_referer">		<label for="pluginzip" class="screen-reader-text">Plugin zip file</label>
		<input type="file" name="pluginzip" id="pluginzip">
		<input type="submit" value="Install Now" class="button" id="install-plugin-submit" name="install-plugin-submit" disabled="">	</form>
  */ ?>
        <?php 
    $uploadfiles = $_FILES['uploadfiles'];

    if (is_array($uploadfiles)) {

    foreach ($uploadfiles['name'] as $key => $value) {

      // look only for uploded files
      if ($uploadfiles['error'][$key] == 0) {

        $filetmp = $uploadfiles['tmp_name'][$key];

        //clean filename and extract extension
        $filename = $uploadfiles['name'][$key];

        // get file info
        // @fixme: wp checks the file extension....
        $filetype = wp_check_filetype( basename( $filename ), null );
        $filetitle = preg_replace('/\.[^.]+$/', '', basename( $filename ) );
        $filename = $filetitle . '.' . $filetype['ext'];
        
        add_filter('upload_dir', 'my_upload_dir');
        $upload_dir = wp_upload_dir();
        remove_filter('upload_dir', 'my_upload_dir');

        /**
         * Check if the filename already exist in the directory and rename the
         * file if necessary
         */
        $i = 0;
        while ( file_exists( $upload_dir['path'] .'/' . $filename ) ) {
          $filename = $filetitle . '_' . $i . '.' . $filetype['ext'];
          $i++;
        }
        $filedest = $upload_dir['path'] . '/' . $filename;

        /**
         * Check write permissions
         */
        if ( !is_writeable( $upload_dir['path'] ) ) {
          $this->msg_e('Unable to write to directory %s. Is this directory writable by the server?');
          return;
        }

        /**
         * Save temporary file to uploads dir
         */
        if ( !@move_uploaded_file($filetmp, $filedest) ){
          $this->msg_e("Error, the file $filetmp could not moved to : $filedest ");
          continue;
        }
        
        WP_Filesystem();
        // $destination = wp_upload_dir();
        // $destination_path = $destination['path'];
        $unzipfile = unzip_file( $filedest, $upload_dir['path']);

           if ( $unzipfile ) {
              echo 'Successfully unzipped the file!';       
           } else {
              echo 'There was an error unzipping the file.';       
           }
      }
    }
  }
?>    
    <form name="uploadfile" id="uploadfile_form" method="POST" enctype="multipart/form-data"  accept-charset="utf-8" >
        <input type="file" name="uploadfiles[]" id="uploadfiles" size="35" class="uploadfiles" />
        <input class="button-primary" type="submit" name="uploadfile" id="uploadfile_btn" value="Upload"  />
    </form>
</div>
<?php
}



function my_upload_dir($upload) {
	$upload['subdir']	= '/leaflet/';
	$upload['path']		= $upload['basedir'] . $upload['subdir'];
	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
	return $upload;
}