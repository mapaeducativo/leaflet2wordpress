<?php
/*
Plugin Name: Leaflet2Wordpress
Plugin URI: 
Description: Sube un fichero comprimido que contiene los archivos de un Mapa Leaflet generado por QGis. Genera un link para mostrar dicho mapa.
Version: 1.0.0
Author:
Author URI: 
License: GPLv2
*/

add_action('admin_menu', 'my_plugin_menu');

function my_plugin_menu() {
    add_menu_page('Mapas Leaflet', 'Subir Mapa Leaflet', 'manage_options', 'leaflet2wordpress_uploads', 'l2w_upload_page');
    add_submenu_page( 'leaflet2wordpress_uploads', 'Mapas Subidos', 'Mapas Subidos', 'manage_options', 'leaflet2wordpress_lists', 'l2w_list_page' );
}


function l2w_upload_page(){
?>
<?php if (current_user_can("manage_options")) : ?>
    <div class="wrap">
    <h2>Mapas Leaflet</h2>
    <br class="clear">
    
<?php  // Verifica el origen de los datos con el campo 'nonce'. ?>
<?php if ( ! empty( $_POST ) && check_admin_referer( 'uploadingzip' ) ) : ?>
    <?php 
        $uploadfiles = $_FILES['uploadfiles'];
        $archivo_error = false;
        global $msjs;
        
        if (is_array($uploadfiles)) {

          // look only for uploded files
          if ($uploadfiles['error'] == 0) {

            $filetmp = $uploadfiles['tmp_name'];

            //clean filename and extract extension
            $filename = $uploadfiles['name'];

            // get file info
            // @fixme: wp checks the file extension....
            $filetype = wp_check_filetype( basename( $filename ), null );
            $filetitle = preg_replace('/\.[^.]+$/', '', basename( $filename ) );
            $filename = $filetitle . '.' . $filetype['ext'];
            
            // Obtengo el directorio para las descargas /uploads/leaflet/...
            add_filter('upload_dir', 'l2w_my_upload_dir');
            $upload_dir = wp_upload_dir();
            remove_filter('upload_dir', 'l2w_my_upload_dir');

            /**
             * Verifica si el archivo ya existe en el servidor y lo renombra si e necesario
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
              $msjs['error'][] = 'No se puede escribir en el directorio %s. Verifique los permisos del directorio.';
              $archivo_error = true;
            }

            /**
             * Save temporary file to uploads dir
             */
            if ( !@move_uploaded_file($filetmp, $filedest) ){
              $msjs['error'][] = "Error, El archivo $filetmp no puede moverse a: $filedest .";
              $archivo_error = true;
            }
            
            // Tipos de archivos permitidos
            $tipos_permitidos = array("html","js","css","html~","js~","css~","jpg","jpeg","png","gif");
            
            $zip = zip_open($filedest);
            
            // Si el Zip se abre sin errores
            if ( $zip && false == $archivo_error ){
                // recorro los archivos que contiene
                while ($zip_entry = zip_read($zip)){
                    // obtengo la extension del archivo actual
                    $ext = pathinfo(zip_entry_name($zip_entry), PATHINFO_EXTENSION);
                    // Si es un directorio O la extension de archivo es permitida continuo.
                    if ( !zip_entry_read($zip_entry) || in_array($ext, $tipos_permitidos) ){
                        continue;
                    }
                    else{ 
                        $archivo_error = true;
                        break;
                    }
                }
                zip_close($zip);
            }
            
            if ( false == $archivo_error ){
                $descomprimir = true;
                $msjs['updated'][] = "Los archivos comprimidos son válidos. ✔";
            }
            else{
                $msjs['error'][] = "El zip contiene archivos no válidos.";
            }
           
            /* debug
            if ($zip)
            {
              while ($zip_entry = zip_read($zip))
                {
                echo "<p>----------";
                $ext = pathinfo(zip_entry_name($zip_entry), PATHINFO_EXTENSION);
                // zip_entry_open($zip, $zip_entry)
                if ( !zip_entry_read($zip_entry) || in_array($ext, $tipos_permitidos) ) echo "is OK!";
                echo $ext;
                echo "------------</p>";
                echo "<p>";
                echo "Name: " . zip_entry_name($zip_entry) . "<br />";

                if (zip_entry_open($zip, $zip_entry))
                  {
                  echo "File Contents:<br/>";
                  $contents = zip_entry_read($zip_entry);
                  // echo "$contents<br />";
                  zip_entry_close($zip_entry);
                  }
                echo "</p>";
              }
            zip_close($zip);
            }
            /* */
            
            // Si no habia errores, descomprimo
            if ( false == $archivo_error && true == $descomprimir ){
                WP_Filesystem();
                $dirname = sanitize_title_with_dashes( $_POST["dirname"] );
                // $destination = wp_upload_dir();
                // $destination_path = $destination['path'];
                $unzipfile = unzip_file( $filedest, $upload_dir['path'].$dirname);
                   if ( $unzipfile ) {
                      $msjs['updated'][] = 'Archivos extraidos correctamente!';
                      // Descomentando esta funcion se eliminan los zips una vez extraido el mapa. 
                      // unlink($filedest);
                      $msjs['updated'][] = 'Mapa subido.<br>'
                              . 'Puede ver el Mapa en el siguiente enlace: <a target="_blank" href="'.$upload_dir['url'].$dirname.'">'.$upload_dir['url'].$dirname.'</a>';
                   } else {
                      $msjs['error'][] = 'Error al descomprimir el archivo';  
                   }
            } // if descomprimir
          } // $uploaded_files sin errores
        } // is_array $uploaded files
    ?> 
<?php endif; // check_admin_referer uploadingzip ok! ?>
    
    <?php l2w_show_messages(); ?>
    <?php // print_r($msjs); ?>
        <h4>Suba el .zip que contiene los archivos del Mapa Leaflet</h4>
        <form name="uploadfile" id="uploadfile_form" method="POST" enctype="multipart/form-data"  accept-charset="utf-8" >
            <?php // genero un campo "nonce" para seguridad ?>
            <?php wp_nonce_field( 'uploadingzip' ); ?>
            <input type="file" name="uploadfiles" id="uploadfiles" size="35" class="uploadfiles" />
            <input type="text" name="dirname" id="dirname" size="35" class="" placeholder="nombre_del_mapa" required/>
            <input class="button-primary" type="submit" name="uploadfile" id="uploadfile_btn" value="Upload"  />
            <p>Escriba un nombre para el mapa, solo se permiten letras minusculas y numeros. Sin espacios.</p>
        </form>
    </div>
<?php endif; // current_user_can manage_options ?>
<?php
}

function l2w_list_page() {	
    global $msjs; ?>
    <?php if (current_user_can("manage_options")) : ?>
    <div class="wrap"><div id="icon-tools" class="icon32"></div>
    <h2>Mapas Leaflet</h2>
    <br class="clear">
<?php 
        // Obtengo el directorio para las descargas /uploads/leaflet/...
        add_filter('upload_dir', 'l2w_my_upload_dir');
        $upload_dir = wp_upload_dir();
        remove_filter('upload_dir', 'l2w_my_upload_dir');
        
        // Eliminar mapa si existe action=delete y existe un name
        if (wp_verify_nonce( $_GET['_wpnonce'],'borramapa' ) && 'delete' == $_GET['action'] && '' != $_GET['name']) {

            $aeliminar = $upload_dir['path'].sanitize_key($_GET['name']);
            if ( rrmdir($aeliminar) ){
                $msjs['updated'][] = "El mapa se ha eliminado correctamente.";
            }
            else{
                $msjs['error'][] = "Se ha producido un error al intentar eliminar el mapa.";
            }
        } // verifica nonce y accion 
        
        l2w_show_messages();
        
        $carpetas = scandir($upload_dir['path']);
        // echo $upload_dir['path'];
        // print_r($carpetas);
        // print_r($upload_dir);
        ?>
        <table class="wp-list-table widefat fixed posts">
            <thead><tr><th class="manage-column" scope="col">Nombre</th><th class="manage-column" scope="col">URL</th><th>Codigo Embebido</th></tr></thead>
        <tbody id="the-list">
        <?php 
        $i = 0;
        foreach ($carpetas as $c){
            if ( '.' != $c && '..' != $c){
                $alt = ( $i % 2 != 0 ) ? "alt" : "";
                if (is_dir($upload_dir['path'].$c)){
                    echo '<tr class="'.$alt.' '.$i.'"><td><strong>'.$c.'</strong>'
                            . '<div class="row-actions">'
                                . '<span class="edit"><a title="Editar" href="#action=edit">Editar</a> | </span>'
                            . '<span class="trash"><a href="'.wp_nonce_url(admin_url('admin.php?page=leaflet2wordpress_lists&action=delete&name='.$c), 'borramapa').'" title="Borrar" class="submitdelete">Borrar</a> </span>'
                            . '</div></td>'
                            . '<td><a target="_blank" href="'.$upload_dir['url'].$c.'">'.$upload_dir['url'].$c.'</a></td>'
                            . '<td><textarea cols="50">[embed]'.$upload_dir['url'].$c.'[/embed]</textarea></td></tr>';
                    $i++;
                }
            }
        }
        ?>
            </tbody>
        </table>
    </div>
<?php
    endif; // if can manage options
}

/*
 * Oembed Leaflets
 * Funcion para agregar el embebido automatico de direcciones que contengan /uploads/leaflet/ en su URL
 */

add_action("init","l2w_embed_handlers");

function l2w_embed_handlers(){
    wp_embed_register_handler( 'l2w_leaflet', '#(http://[a-z./_-]*uploads\/leaflet\/[\d,\w,\s,-]+)($|&)#i', 'wp_embed_handler_l2w_wordpress' );
}


function wp_embed_handler_l2w_wordpress( $matches, $attr, $url, $rawattr ) {

	$embed = sprintf(
			'<iframe class="media media-object map" src="%1$s" width="900" style="max-width:100%;height:400px;" height="500" frameborder="0" scrolling="no" marginwidth="0" marginheight="0"></iframe><div class="map_link"><a href="%1$s/" target="_blank" title="Ver mapa completo">Ver mapa completo</a></div>',
			esc_attr($matches[1])
			);

	return apply_filters( 'embed_l2w_leaflet', $embed, $matches, $attr, $url, $rawattr );
}

/*
 * Helpers
 */

/*
 * Funcion para agregar el /leaflet/ a la direccion de uploads.
 */
function l2w_my_upload_dir($upload) {
	$upload['subdir']	= '/leaflet/';
	$upload['path']		= $upload['basedir'] . $upload['subdir'];
	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
	return $upload;
}
/*
 * Funcion para mostrar los msjs del array $msjs
 * 
 */
function l2w_show_messages(){
    global $msjs;
    /*
    echo "<br>";
    print_r($msjs);
    echo "</br>";
    */
    foreach ( $msjs as $key => $val){
        foreach ( $msjs[$key] as $m){
        ?>
        <div class="<?php echo $key; ?>"><p><?php echo $m; ?></p></div>
        <?php
        }
    }
}
/*
 * Funcion para borrar recursivamente un directorio
 * Return true|false
 */
if ( !function_exists("rrmdir") ){
    function rrmdir($dir) { 
       if (is_dir($dir)) { 
         $objects = scandir($dir); 
         foreach ($objects as $object) { 
           if ($object != "." && $object != "..") { 
             if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
           } 
         } 
         reset($objects); 
         return rmdir($dir); 
       } 
     }
}