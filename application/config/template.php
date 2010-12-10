<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Template Class
 *
 * The Template class makes the creation of consistently themed web pages across your
 * entire site simple and as automatic as possible.
 * 
 * @author Lonnie Ezell
 * @license http://creativecommons.org/licenses/by-sa/3.0/
 * @package Ocular Layout Library
 * @version 3.0a
 */
 
/*
|--------------------------------------------------------------------
| SITE PATH
|--------------------------------------------------------------------
| The path to the root folder that holds the application. This does
| not have to be the site root folder, or even the folder defined in
| FCPATH. 
|
*/
$config['template.site_path']	= FCPATH;

/*
|---------------------------------------------------------------------
| THEME PATHS
|---------------------------------------------------------------------
| An array of folders to look in for themes. There must be at least
| one folder path at all times, to serve as the fall-back for when
| a theme isn't found. Paths are relative to the FCPATH.
*/
$config['template.theme_paths'] = array( 'themes' );

/*
|--------------------------------------------------------------------
| DEFAULT LAYOUT
|--------------------------------------------------------------------
| This is the name of the default layout used if no others are
| specified.
|
| NOTE: do not include an ending ".php" extension.
|
*/
$config['template.default_layout'] = "application";

/*
|--------------------------------------------------------------------
| DEFAULT AJAX LAYOUT
|--------------------------------------------------------------------
| This is the name of the default layout used when the page is 
| displayed via an AJAX call.
|
| NOTE: do not include an ending ".php" extension.
|
*/
$config['template.ajax_layout'] = 'ajax';


/*
|--------------------------------------------------------------------
| DEFAULT THEME
|--------------------------------------------------------------------
| This is the folder name that contains the default theme to use
| when 'template.use_mobile_themes' is set to TRUE.
|
*/
$config['template.default_theme'] = 'default/';

/*
|--------------------------------------------------------------------
| PARSE VIEWS?
|--------------------------------------------------------------------
| Whether or not views should be run through CodeIgniter's parser.
|
*/
$config['template.parse_views'] = false;

/*
|--------------------------------------------------------------------
| MESSAGE TEMPLATE
|--------------------------------------------------------------------
| This is the template that Ocular will use when displaying messages
| through the message() function. 
|
| To set the class for the type of message (error, success, etc),
| the {type} placeholder will be replaced. The message will replace 
| the {message} placeholder.
|
*/
$config['template.message_template'] =<<<EOD
	<div class="notification {type}">
		<div>{message}</div>
	</div>
EOD;

/*
|--------------------------------------------------------------------
| BREADCRUMB Separator
|--------------------------------------------------------------------
| The symbol displayed between elements of the breadcrumb.
|
*/
$config['template.breadcrumb_symbol']	= ' : ';