<?php
/**
 * Plugin Name: Book cover via Google Cover
 * Plugin URI: https://github.com/drajathasan/slims-cover-googlebooks
 * Description: Plugin untuk mengambil cover buku dari google book
 * Version: 1.0.0
 * Author: Drajat Hasan
 * Author URI: https://t.me/drajathasan
 */

use SLiMS\DB;
use SLiMS\Plugins;
use GuzzleHttp\Client; // we need guzzle as image scrapper

// set autoload
include_once __DIR__ . DS . 'vendor' . DS . 'autoload.php';

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

/**
 * Register hook for register downloading book cover
 */
$plugin->register('advance_custom_field_data', function(&$custom_data){
    // make sure url is not empty
    if (!empty($_POST['googleCover']))
    {
        try {
            $Client = new Client;
            // sink image from URL
            $Client->request('GET', $_POST['googleCover'], ['sink' => SB . 'images' . DS . 'docs' . DS . md5(date('YmdH:i:s')) . '.jpg']);
            $_SESSION['googleCover'] = md5(date('YmdH:i:s')) . '.jpg';
        } catch (\Exception $e) {
            // Throw error
            \utility::jsToastr('Galat Unduh Sampul', $e->getMessage(), 'error');
        }
    }
});

/**
 * Register hook for processing data after updated
 */
$plugin->register('bibliography_after_update', function($data){
    if (isset($_SESSION['googleCover']) && !empty($_SESSION['googleCover'])) 
    {
        $statement = DB::getInstance()->prepare('update `biblio` set image = ? where `biblio_id` = ?');
        $statement->execute([$_SESSION['googleCover'], $data['biblio_id']]);
        if ($statement->rowCount() < 1)
        {
            \utility::jsToastr('Galat', 'Gagal mengupdate gambar sampul', 'error');
        }
        else
        {
            \utility::jsToastr('Sukses', 'Berhasil mengupdate gambar sampul', 'success');
        }
        unset($_SESSION['googleCover']);
    }
});

/**
 * Register hook for processing data after saved
 */
$plugin->register('bibliography_after_save', function($data){
    if (isset($_SESSION['googleCover']) && !empty($_SESSION['googleCover'])) 
    {
        $statement = DB::getInstance()->prepare('update `biblio` set image = ? where `biblio_id` = ?');
        $statement->execute([$_SESSION['googleCover'], $data['biblio_id']]);
        if ($statement->rowCount() < 1)
        {
            \utility::jsToastr('Galat', 'Gagal mengupdate gambar sampul', 'error');
        }
        else
        {
            \utility::jsToastr('Sukses', 'Berhasil mengupdate gambar sampul', 'success');
        }
        unset($_SESSION['googleCover']);
    }
});

/**
 * Register other form components
 */
$plugin->register('advance_custom_field_form', function($form, $itemID, $rec_d, &$js = ''){
    global $sysconf,$in_pop_up;

    // biblio cover image
    $str_input = '<div class="row">';
    $str_input .= '<div class="col-2">';
    $str_input .= '<div id="imageFilename" class="s-margin__bottom-1">';
    $upper_dir = '';
    if ($in_pop_up) {
        $upper_dir = '../../';
    }

    if (isset($rec_d['image']) && file_exists(SB . 'images/docs/' . $rec_d['image'])) {
        $str_input .= '<a href="' . SWB . 'images/docs/' . ($rec_d['image'] ?? '') . '" class="openPopUp notAJAX" title="' . __('Click to enlarge preview') . '">';
        $str_input .= '<img src="' . $upper_dir . '../images/docs/' . urlencode($rec_d['image'] ?? '') . '" class="img-fluid rounded" alt="Image cover">';
        $str_input .= '</a>';
        $str_input .= '<a href="' . MWB . 'bibliography/index.php" postdata="removeImage=true&bimg=' . $itemID . '&img=' . ($rec_d['image'] ?? '') . '" loadcontainer="imageFilename" class="s-margin__bottom-1 mt-1 s-btn btn btn-danger btn-block makeHidden removeImage">' . __('Remove Image') . '</a>';
    } else {
        $str_input .= '<img src="' . $upper_dir . '../lib/minigalnano/createthumb.php?filename=../../images/default/image.png&width=130" class="img-fluid rounded" alt="Image cover">';
    }
    $str_input .= '</div>';
    $str_input .= '</div>';
    $str_input .= '<div class="custom-file col-7">';
    $str_input .= \simbio_form_element::textField('file', 'image', '', 'class="custom-file-input" id="customFile"');
    $str_input .= '<label class="custom-file-label" for="customFile">' . __('Choose file') . '</label>';
    $str_input .= '<div style="padding: 10px;margin-left: -25px;">';
    $str_input .= '<div>atau Unduh Cover via Google Books (dengan <strong>ISBN</strong>)</div>';
    $str_input .= '<div class="form-inline">
                  <div class="input-group-append">
                  <input type="hidden" name="googleCover" value=""/>
                  <a href="' . $_SERVER['PHP_SELF'] . '" class="notAJAX getCoverByISBN my-1 btn btn-outline-primary">
                  <svg xmlns="http://www.w3.org/2000/svg" class="d-inline-block mr-1" height="20" width="20" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    <path fill="none" d="M0 0h48v48H0z"/>
                  </svg>
                  Dapatkan Cover
                  </a>
                  </div>
                  </div>';
    $str_input .= '</div>';
    $str_input .= '</div>';
    $str_input .= ' <div class="mt-2 ml-2">Maximum ' . $sysconf['max_image_upload'] . ' KB</div>';
    $str_input .= '</div>';
    $str_input .= '<textarea id="base64picstring" name="base64picstring" style="display: none;"></textarea>';
    $str_input .= '</div></div></div>';

    $form->addAnything(__('Image'), $str_input);
    
    // We need JS for consume Google Books api
    $js = <<<HTML
    $('.getCoverByISBN').click(async function(e){
        e.preventDefault();
        let isbn = $('#isbn_issn').val().replace(/[^0-9]+/g, '');

        $(this).html($(this).html() + ' tunggu sebentar');
        $(this).addClass('disabled');

        if (isbn.length < 1)
        {
            top.window.toastr.warning('Ruas ISBN/ISSN tidak boleh kosong!', 'Peringatan');
            return;
        }

        try {
            let request = await (await fetch(`https://www.googleapis.com/books/v1/volumes?q=isbn:\${isbn}`)).json()
            
            if (request.totalItems > 0)
            {
                let volume = request.items[0].volumeInfo;
                $('input[name="googleCover"]').val(volume.imageLinks.thumbnail);
                $('#imageFilename > img').attr('src', volume.imageLinks.thumbnail);
                $(this).html($(this).html().replace(' tunggu sebentar', ''));
                $(this).removeClass('disabled');
            }
            
        } catch(error) {
            console.log(error);
        }
    });
    HTML;
});

// registering menus or hook
// Force default page of Bibliography module
$plugin->register("bibliography_init", function(){
    global $sysconf,$dbs;
    // check if we are inside pop-up window
    if (!isset($_GET['inPopUp'])) {
        include_once __DIR__ . DS . 'index.php';
        exit;
    }
});