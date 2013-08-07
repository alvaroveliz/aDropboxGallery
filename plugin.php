<?php
/*
Plugin Name: aDropboxGallery
Plugin URI:  https://github.com/alvaroveliz/aDropboxGallery
Description: Alvaro's Dropbox Gallery
Version:     1.0 
Author:      Alvaro Véliz
Author URI:  http://alvaroveliz.cl
License:     MIT
*/
require 'includes/aDropboxGallery.php';

$adg = new aDropboxGallery();

/** DO THE ADMIN **/
add_action('admin_menu', array($adg, 'getAdminOptions'));

/** DO THE SHORTCODE **/
add_shortcode( 'adropboxgallery', array($adg, 'getShortCode') );

/** TO-DO: THE WIDGET **/