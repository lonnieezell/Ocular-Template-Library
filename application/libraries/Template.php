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
	CHANGES: 
		- Modified get() method to also return class methods, if they exist.
		- Added a set_view method since setting it as a public class member was 
			a) wrong and b) not working with the static class.
*/
class Template {

	public static $debug = false;

	/**
	 * Stores the name of the active theme (folder)
	 * with a trailing slash. 
	 * 
	 * (default value: '')
	 * 
	 * @var string
	 * @access protected
	 */
	protected static $active_theme = '';
	
	/**
	 * Stores the default theme from the config file
	 * for a slight performance increase.
	 *
	 * @var string
	 * @access protected
	 */
	 protected static $default_theme = '';

	/**
	 * The view to load. Normally not set unless
	 * you need to bypass the automagic.
	 * 
	 * @var mixed
	 * @access public
	 */
	protected static $current_view;
	
	/**
	 * The default template to render if none is
	 * specified through the view's extend feature.
	 *
	 * @var	string
	 * @access protected
	 */
	protected static $default_layout;
	
	/**
	 * The layout to render the views into.
	 * 
	 * @var mixed
	 * @access public
	 */
	public static $layout = 'index';
	
	/**
	 * parse_views
	 * 
	 * If true, CodeIgniter's Template Parser will be used to 
	 * parse the view. If false, the view is displayed with
	 * no parsing. Used by the yield() and block() 
	 * 
	 * @var mixed
	 * @access public
	 */
	public static $parse_views = false;
	
	/**
	 * The data to be passed into the views.
	 * The keys are the names of the variables
	 * and the values are the values.
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected static $data = array();
	
	/**
	 * An array of blocks. The key is the name
	 * to reference it by, and the value is the file.
	 * The class will loop through these, parse them,
	 * and push them into the layout.
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected static $blocks = array();
	
	/**
	 * An array/stack of block names. Used by the 
	 * rendering engine to allow nested blocks to be
	 * rendered by Ocular.
	 *
	 * @var	array
	 * @access protected
	 */
	protected static $current_blocks = array();
	
	/**
	 * Holds a simple array to store the status Message
	 * that gets displayed using the message() function.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $message;

	/**
	 * An array of paths to look for themes.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $theme_paths	= array();	
	
	/**
	 * The full server path to the site root.
	 */
	public static $site_path;
	
	/**
	 * Stores CI's default view path.
	 */
	protected static $orig_view_path;
	
	/**
	 * An instance of the CI super object.
	 * 
	 * @var mixed
	 * @access private
	 */
	private static $ci;
	
	/**
	 * An array of hook points and the functions
	 * to call when they're executed.
	 *
	 * Currently only supports post_render.
	 */
	private static $callbacks	= array(
		'post_render'	=> array()
	);

	//--------------------------------------------------------------------

	/**
	 * __construct function.
	 *
	 * This is purely here for CI's benefit. 
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() 
	{	
		self::$ci =& get_instance();
		
		self::init();
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * init function
	 * 
	 * Grabs an instance of the CI superobject, loads the Ocular config
	 * file, and sets our default layout.
	 *
	 * @access 	public
	 * @return	void
	 */
	public static function init() 
	{
		// If the application config file hasn't been loaded, do it now
		if (!self::$ci->config->item('template.theme_paths'))
		{ 
			self::$ci->config->load('template');
		}
		
		// Store our settings
		self::$site_path 		= self::$ci->config->item('template.site_path');
		self::$theme_paths 		= self::$ci->config->item('template.theme_paths');
		self::$default_layout	= self::$ci->config->item('template.default_layout');
		self::$default_theme 	= self::$ci->config->item('template.default_theme');
		self::$parse_views		= self::$ci->config->item('template.parse_views');
		
		// Store our orig view path, so we can reset it
		self::$orig_view_path = self::$ci->load->_ci_view_path;
		
		log_message('debug', 'Template library loaded');
	}
	
	//--------------------------------------------------------------------
	
	
	/**
	 * render function.
	 *
	 * Builds the page from the correct view and layouts. It starts by checking for
	 * the correct view in the current theme, then the default theme, and then in the
	 * views folder under a folder/file structure matching current controller/method names.
	 *
	 * Once the view is found and rendered, it determines the correct layout to used. This
	 * is determined in the following order: 
	 *		- If extend() is called from the view, that layout is used, otherwise...
	 * 		- A layout with the name of the current controller is used, else...
	 *		- The default layout is used.
	 *		- Note that layouts in the current theme override layouts in the default theme,
	 *		  which means that minimal overridden themes are needed to modify a parent theme.
	 * 
	 * @access public
	 * @return void
	 */
	public static function render() 
	{	
		// Start output buffering
		ob_start();
		
		/*
			Displays our current view. While the view is rendering, it can set the layout
			to use through the extend() method, and can use begin() and end() methods to 
			setup/override blocks.
		*/
		self::yield();
		
		// Capture our view's output
		$output = ob_get_clean();

		/*
			It's now time to render the layout. If the view extended a specific layout, 
			then self::$layout will already be set. Otherwise, the load_view() method will
			search for a view matching the current controller in the current and default
			themes. If that file doesn't exist, then the default layout will be used.
		*/
		if (self::$layout)
		{
			$layout = self::$layout;
			//self::$layout = '';

			$output = self::load_view($layout, self::$data, self::$ci->router->class, true);
			
			unset($layout);
		}
		
		if (!empty(self::$callbacks['post_render']))
		{
			list($class, $method) = self::$callbacks['post_render'];
			
			self::$ci->$class->$method($output);
		}
		
		global $OUT;
		$OUT->set_output($output); 
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Renders the current page. 
	 *
	 * Uses a view based on the controller/function being run. (See __constructor).
	 * 
	 * @access public
	 * @return void
	 */
	public static function yield() 
	{ 			
		// Grab our current view name, based on controller/method
		// which routes to views/controller/method.
		if (empty(self::$current_view))
		{		
			self::$current_view = self::$ci->router->class . '/' . self::$ci->router->method;
		}
	
		if (self::$debug) { echo '[Yield] Current View = '. self::$current_view; }
 		
		echo self::load_view(self::$current_view, self::$data, self::$ci->router->class .'/'. self::$ci->router->method, false);
	}
	
	//--------------------------------------------------------------------
	
	//--------------------------------------------------------------------
	// !TEMPLATE INHERITANCE
	//--------------------------------------------------------------------
	
	/**
	 * extend() method
	 *
	 * Sets the layout that is used to render the content into. 
	 *
	 * @param	string	$name	The name of the layout file (NO extension!)
	 * @return	void
	 */
	public function extend($name=null) 
	{
		if (empty($name))
		{
			self::$layout = self::$default_layout;
		}
		else 
		{
			self::$layout = $name;
		}
	}
	
	//--------------------------------------------------------------------
	
	public function begin($block_name=null) 
	{
		if (is_null($block_name))
		{
			return;
		}
		
		// Start buffering for this block only.
		ob_start();
		
		// Store the current_blocks name so that the end() method will
		// know where we are.
		array_unshift(self::$current_blocks, $block_name);
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * end() method
	 *
	 * Signals the end of rendering of the current block's contents. 
	 * The information is stored in the self::$blocks array by name, 
	 * and inserted into the layout furing the final phase of rendering.
	 * If this is the master layout, then the default content is rendered.
	 *
	 */
	public function end() 
	{
		$current_block = array_shift(self::$current_blocks);
		
	
		/*
			If the self::$layout var has a value, then this is a sub-template
			(or standard view) that is overriding the default block contents.
		*/
		if (!empty(self::$layout))
		{ 
			// Grab the buffer contents and clean up our $current_blocks array;
			self::$blocks[$current_block] = ob_get_clean();
		}	
		/*
			Since the self::$layout value is empty, we know this is a master
			layout and we just need to render the value of the block, not save it.
		*/
		else 
		{ 
			// Use the stored block, if it exists...
			if (isset(self::$blocks[$current_block]))
			{
				// Drop the current content
				ob_end_clean();
				// Display our stored block instead.
				echo self::$blocks[$current_block];
			}
			// Otherwise, use the default block content
			else 
			{
				echo ob_get_clean();
			}
		}

		unset($current_block);
	}
	
	//--------------------------------------------------------------------
	
	
	//--------------------------------------------------------------------
	// !THEME SPECIFIC METHODS
	//--------------------------------------------------------------------
	
	/**
	 * add_theme_path method
	 * 
	 * Theme paths allow you to have multiple locations for themes to be
	 * stored. This might be used for separating themes for different sub-
	 * applications, or a core theme and user-submitted themes.
	 *
	 * @param	string	$path	A new path where themes can be found.
	 */
	public static function add_theme_path($path=null) 
	{
		if (empty($path) || !is_string($path))
		{
			return false;
		}
		
		// Make sure the path has a '/' at the end.
		if (substr($path, -1) != '/')
		{
			$path .= '/';
		}
		
		// If the path already exists, we're done here.
		if (isset(self::$theme_paths[$path]))
		{
			return true;
		}
		
		// Make sure the folder actually exists
		if (is_dir(FCPATH . $path))
		{
			array_unshift(self::$theme_paths, $path);
			return false;
		} else 
		{
			log_message('debug', "[Template] Cannot add theme folder: $path does not exist");
			return false;
		}
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * remove_theme_path method
	 *
	 * @param	string	$path	The path to remove from the theme paths.
	 * @return	void
	 */
	public static function remove_theme_path($path=null) 
	{
		if (empty($path) || !is_string($path))
		{
			return;
		}
		
		if (isset(self::$theme_paths[$path]))
		{
			unset(self::$theme_paths[$path]);
		}
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * set_theme method
	 *
	 * Stores the name of the active theme to use. This theme should be
	 * relative to one of the 'template.theme_paths' folders.
	 *
	 * @access	public
	 * @param	string	$theme	The name of the active theme
	 * @return	void
	 */
	public static function set_theme($theme=null) 
	{
		if (empty($theme) || !is_string($theme))
		{
			return;
		}

		// Make sure a trailing slash is there
		if (substr($theme, -1) !== '/')
		{
			$theme .= '/';
		}

		self::$active_theme = $theme;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Returns the active theme.
	 * 
	 * @return	string	The name of the active theme.
	 */
	public static function theme() 
	{
		return self::$active_theme;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Returns the url to the active theme.
	 *
	 * @return	string	The full url to the active theme folder.
	 */
	public function theme_url($file='') 
	{
		$folder = '';
		
		foreach (self::$theme_paths as $path)
		{
			if (is_dir($path .'/'. self::$active_theme))
			{
				$folder = $path;
				break;
			}
		}
		
		return site_url($folder .'/'. self::$active_theme . $file);
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Set the current view to render.
	 * 
	 * @param	string	$view	The name of the view file to render as content.
	 * @return	void
	 */
	public function set_view($view=null) 
	{
		if (empty($view) || !is_string($view))
		{
			return;
		}
		
		self::$current_view = $view;
	}
	
	//--------------------------------------------------------------------
	
	
	/**
	 * Makes it easy to save information to be rendered within the views.
	 * 
	 * @access public
	 * @param string $name. (default: '')
	 * @param string $value. (default: '')
	 * @return void
	 */
	public static function set($var_name='', $value='') 
	{		
		// Added by dkenzik
	    // 20101001
	    // Easier migration when $data is scaterred all over your project
	    //
	    if(is_array($var_name) && $value=='')
	    {
	        foreach($var_name as $key => $value)
	        {
	        	self::$data[$key] = $value;
	        }           
	    }
	    else
	    {
	        self::$data[$var_name] = $value;
	    }
	}
	
	//--------------------------------------------------------------------
	
	/**
	 *	Returns a variable that has been previously set, or false if not exists.
	 *
	 * @param	string	$var_name	The name of the data item to return.
	 * @return	string/bool
	 */
	public static function get($var_name=null) 
	{
		if (empty($var_name))
		{
			return false;
		}
		
		// First, is it a class property? 
		if (isset(self::$$var_name))
		{
			return self::$$var_name;
		}
		else if (isset(self::$data[$var_name]))
		{
			return self::$data[$var_name];
		}
		
		return false;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * parse_views method
	 *
	 * Set whether or not the views will be passed through CI's parser.
	 *
	 * @param	bool	$parse	Should we parse views?
	 * @return	void
	 */
	public function parse_views($parse) 
	{
		self::parse_views($parse);
	}
	
	//--------------------------------------------------------------------
	
	
	/**
	 * Sets a status message (for displaying small success/error messages).
	 * This function is used in place of the session->flashdata function,
	 * because you don't always want to have to refresh the page to get the
	 * message to show up. 
	 * 
	 * @access public
	 * @param string $message. (default: '')
	 * @param string $type. (default: 'info')
	 * @return void
	 */
	public static function set_message($message='', $type='info') 
	{
		if (!empty($message))
		{
			if (class_exists('CI_Session'))
			{
				self::$ci->session->set_flashdata('message', $type.'::'.$message);
			}
			
			self::$message = array('type'=>$type, 'message'=>$message);
		}
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Displays a status message (small success/error messages).
	 * If data exists in 'message' session flashdata, that will 
	 * override any other messages. The renders the message based
	 * on the template provided in the config file ('OCU_message_template').
	 * 
	 * @access public
	 * @return void
	 */
	public static function message() 
	{
		$message = '';		// The message body.
		$type	 = '';		// The message type (used for class)
	
		// Does session data exist? 
		if (class_exists('CI_Session'))
		{
			$message = self::$ci->session->flashdata('message');
			
			if (!empty($message))
			{
				// Split out our message parts
				$temp_message = explode('::', $message);
				$type = $temp_message[0];
				$message = $temp_message[1];
				
				unset($temp_message);
			} 
		}
		
		// If message is empty, we need to check our own storage.
		if (empty($message))
		{
			if (empty(self::$message['message']))
			{
				return '';
			}
			
			$message = self::$message['message'];
			$type = self::$message['type'];
		}
		
		// Grab out message template and replace the placeholders
		$template = str_replace('{type}', $type, self::$ci->config->item('template.message_template'));
		$template = str_replace('{message}', $message, $template);
		
		// Clear our session data so we don't get extra messages. 
		// (This was a very rare occurence, but clearing should resolve the problem.
		if (class_exists('CI_Session'))
		{
			self::$ci->session->flashdata('message', '');
		}
		
		return $template;
	}
	
	//---------------------------------------------------------------
	
	/**
	 *	Loads a view based on the current themes.
	 *
	 * @param	string	$view		The view to load.
	 * @param	array	$data		An array of data elements to be made available to the views
	 * @param	string	$override	The name of a view to check for first (used for controller-based layouts)
	 * @param	bool	$is_themed	Whether it should check in the theme folder first.
	 * @return	string	$output		The results of loading the view
	 */
	public static function load_view($view=null, $data=null, $override='', $is_themed=true) 
	{ 
		$output = '';
	
		if (empty($view))	return '';
		
		// If no active theme is present, use the default theme.
		$theme = empty(self::$active_theme) ? self::$default_theme : self::$active_theme;
	
		if ($is_themed)
		{	
			// First check for the overriden file...
			$output = self::find_file($override, $data, $theme);
			
			// If we didn't find it, try the standard view
			if (empty($output))
			{
				$output = self::find_file($view, $data, $theme);
			}
		} 
		
		// Just a normal view (possibly from a module, though.)
		else 
		{
			// First check within our themes...
			$output = self::find_file($view, $data, $theme);
			
			// if $output is empty, no view was overriden, so go for the default
			if (empty($output))
			{
				self::$ci->load->_ci_view_path = self::$orig_view_path;
		
				if (self::$parse_views === true)
				{
					$output = self::$ci->parser->parse($view, $data, true);
				}
				else 
				{
					$output = self::$ci->load->view($view, $data, true);
				}
			}
		}
		
		// Put our ci view path back to normal
		self::$ci->load->_ci_view_path = self::$orig_view_path;
		unset($theme, $orig_view_path);
		
		// return our output
		return $output;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * set_callback() method
	 *
	 * Registers a callback function to be run at certain points of the 
	 * scripts execution. Currently, the only one implemented is post_render();
	 *
	 * @param	string	$callback_name		The name of the callback to hook in to.
	 * @param	string	$callback_method	The method to call during that hook's execution.
	 * @return	void
	 */
	public function set_callback($callback_name='post_render', $class='', $method='') 
	{	
		self::$callbacks[$callback_name] = array($class, $method);
	}
	
	//--------------------------------------------------------------------
	
	
	//--------------------------------------------------------------------
	// !PRIVATE METHODS
	//--------------------------------------------------------------------
	
	/** 
	 * find_file method
	 *
	 * Searches through the themes to attempt to find a file location.
	 *
	 * @param	string	$view		The name of the view to find.
	 * @param	array	$data		An array of key/value pairs to pass to the views.
	 * @param	string	$theme		The name of the active theme.
	 * @return	string				The content of the file, if found, else empty.
	 */
	private function find_file($view=null, $data=null, $theme=null) 
	{
		if (empty($view))
		{
			return false;
		}
		
		$output = '';
		
		foreach (self::$theme_paths as $path)
		{				
			$full_path = self::$site_path . $path .'/'. $theme;
		
			if (self::$debug) { echo "Looking for view: <b>{$full_path}{$view}.php.</b><br/>"; }
		
			// Does the file exist
			if (is_file($full_path . $view .'.php'))
			{	
				// Set CI's view path to see the theme folder
				self::$ci->load->_ci_view_path = $full_path;
			
				// Grab the output of the view.
				if (self::$parse_views === true)
				{
					$output = self::$ci->parser->parse($view, $data, true);
				} else 
				{
					$output = self::$ci->load->_ci_load(array('_ci_view' => $view, '_ci_vars' => self::$ci->load->_ci_object_to_array($data), '_ci_return' => true));
					break;
				}
			} 
		}
		
		return $output;
	}
	
	//--------------------------------------------------------------------
	
}

// End of Template Class

//--------------------------------------------------------------------

function themed_view($view=null, $data=null)
{ 
	if (empty($view)) return '';
	
	$output = Template::load_view($view, $data, null, true);
	return $output;
}

//--------------------------------------------------------------------

function check_class($item='')
{
	$ci =& get_instance();

	if (strtolower($ci->router->fetch_class()) == strtolower($item))
	{
		return 'class="current"';
	}
	
	return '';
}

//--------------------------------------------------------------------

function check_method($item='')
{
	$ci =& get_instance();

	if (strtolower($ci->router->fetch_method()) == strtolower($item))
	{
		return 'class="current"';
	}
	
	return '';
}

//--------------------------------------------------------------------

/**
 * Will create a breadcrumb from either the uri->segments or
 * from a key/value paired array passed into it. 
 *
 * @since 2.12
 */
function breadcrumb($my_segments=null) 
{
	$ci =& get_instance();
	
	if (!class_exists('CI_URI'))
	{
		$ci->load->library('uri');
	}
	
	if (empty($my_segments) || !is_array($my_segments))
	{
		$segments = $ci->uri->segment_array();
		$total = $ci->uri->total_segments();
	} else 
	{
		$total = count($my_segments);
	}
	
	echo '<a href="/">home</a> ' . $ci->config->item('OCU_breadcrumb_symbol');
	
	$url = '';
	$count = 0;
	
	// URI BASED BREADCRUMB
	if (is_null($my_segments))
	{
		foreach ($segments as $segment)
		{
			$url .= '/'. $segment;
			$count += 1;
		
			if ($count == $total)
			{
				echo str_replace('_', ' ', $segment);
			} else 
			{
				echo '<a href="'. $url .'">'. str_replace('_', ' ', strtolower($segment)) .'</a>'. $ci->config->item('template.breadcrumb_symbol');
			}
		}
	} else
	{
		// USER-SUPPLIED BREADCRUMB
		foreach ($my_segments as $title => $uri)
		{
			$url .= '/'. $uri;
			$count += 1;
		
			if ($count == $total)
			{
				echo str_replace('_', ' ', $title);
			} else 
			{
				echo '<a href="'. $url .'">'. str_replace('_', ' ', strtolower($title)) .'</a>'. $ci->config->item('template.breadcrumb_symbol');
			}
		}
	}
}

//---------------------------------------------------------------

/* End of file template.php */
/* Location: ./application/libraries/template.php */