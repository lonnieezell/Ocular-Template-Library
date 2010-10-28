<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Template Class
 *
 * The Template class makes the creation of consistently themed web pages across your
 * entire site simple and as automatic as possible.
 * 
 * @author Lonnie Ezell
 * @license http://creativecommons.org/licenses/by-sa/3.0/
 * @package Ocular Layout Library
 * @version 2.13
 */
class Template {

	/**
	 * An instance of the CI super object.
	 * 
	 * @var mixed
	 * @access private
	 */
	public $ci;
	
	/**
	 * The view to load. Normally not set unless
	 * you need to bypass the automagic.
	 * 
	 * @var mixed
	 * @access public
	 */
	public $current_view;
	
	/**
	 * The layout to render the views into.
	 * 
	 * @var mixed
	 * @access protected
	 */
	public $layout;
	
	/**
	 * use_ci_parser
	 * 
	 * If true, CodeIgniter's Template Parser will be used to 
	 * parse the view. If false, the view is displayed with
	 * no parsing. Used by the yield() and block() 
	 * 
	 * @var mixed
	 * @access public
	 */
	public $use_ci_parser = false;
	
	/**
	 * The data to be passed into the views.
	 * The keys are the names of the variables
	 * and the values are the values.
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $data;
	
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
	protected $blocks = array();
	
	
	/**
	 * If themes are turned on in the config file, this
	 * value stores the name of the active theme (folder)
	 * with a trailing slash. 
	 *
	 * If 'OCU_use_themes' is FALSE, you can still use
	 * this value to create your own themeing system
	 * through the set_theme() and theme() functions.
	 * 
	 * (default value: '')
	 * 
	 * @var string
	 * @access protected
	 */
	protected $active_theme = '';
	
	/**
	 * Stores the default theme from the config file
	 * for a slight performance increase.
	 */
	 protected  $default_theme = '';
	
	/**
	 * Holds a simple array to store the status Message
	 * that gets displayed using the message() function.
	 *
	 * @var array
	 * @access protected
	 */
	public $message;
	
	/**
	 * Do we cache views?
	 *
	 * @access public
	 * @var boolean
	 */
	public $cache_view;
	
	/**
	 * Do we cache the layout?
	 *
	 * @var boolean
	 * @access public
	 */
	public $cache_layout;
	
	/**
	 * Default time to cache the view for.
	 *
	 * @var int
	 * @access public
	 */
	public $cache_view_expires;
	
	/**
	 * Default time to cache the layout for.
	 *
	 * @var int
	 * @access public
	 */
	public $cache_layout_expires;
	
	/**
	 * Where to store cache files.
	 * This uses the global CI setting.
	 *
	 * @var string
	 * @access protected
	 */
	protected $cache_path;
	
	/**
	 * An array of cache_id's and true/false
	 * about whether a view has been cached or not.
	 */
	 protected $cached = array();
	
	
	
	//---------------------------------------------------------------
	
	/**
	 * __construct function.
	 *
	 * Grabs an instance of the CI superobject, loads the Ocular config
	 * file, and sets our default layout.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct() 
	{	
		$this->ci =& get_instance();
		
		$this->_mark('Template_constructor_start');
		
		$this->ci->config->load('template');
		
		// Store some of our defaults
		$this->layout = $this->ci->config->item('OCU_layout_folder') . $this->ci->config->item('OCU_default_layout');
		$this->default_theme = $this->ci->config->item('OCU_default_theme');
		
		$this->cache_path = $this->ci->config->item('cache_path');
		$this->cache_view = $this->ci->config->item('OCU_cache_view');
		$this->cache_layout = $this->ci->config->item('OCU_cache_layout');
		$this->cache_view_expires = $this->ci->config->item('OCU_cache_view_expires');
		$this->cache_layout_expires = $this->ci->config->item('OCU_cache_layout_expires');
				
		// Show the profiler?
		if ($this->ci->config->item('OCU_profile')) $this->ci->output->enable_profiler(true);
		
		log_message('debug', 'Template library loaded');
		
		$this->_mark('Template_constructor_end');
	}
	
	//---------------------------------------------------------------
	
	/**
	 * render function.
	 *
	 * Renders out the specified layout, which starts the process
	 * of rendering the page content. Also determines the correct
	 * view to use based on the current controller/method.
	 * 
	 * @access public
	 * @param 	string 	$layout. (default: '')
	 * @param 	boolean $cache_me	Whether or not to cache the layout
	 * @return void
	 */
	public function render($layout='', $cache_me=false) 
	{	
		$this->_mark('Template_Render_start');
		
		$this->set('site_name', $this->ci->config->item('OCU_site_name'));

		$this->set('active_controller', $this->ci->router->class);
		$this->set('active_method', $this->ci->router->method);
		$this->set('active_view', $this->current_view);
		
		// Make sure we're using the correct layout.
		// If none is specified, use the default. 
		// Set in constructor.
		$layout = empty($layout) ? $this->layout : $this->ci->config->item('OCU_layout_folder') . $layout;
		
		$this->_set_theme('');
		
		// Is it in an AJAX call? If so, override the layout
		if ($this->is_ajax())
		{
			$layout = $this->ci->config->item('OCU_layout_folder') . $this->ci->config->item('OCU_ajax_layout');
			$this->ci->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
			$this->ci->output->set_header("Cache-Control: post-check=0, pre-check=0");
			$this->ci->output->set_header("Pragma: no-cache"); 
		}
		
		
		// Grab our current view name, based on controller/method
		// which routes to views/controller/method.
		if (empty($this->current_view))
		{
			$this->current_view = $this->ci->router->directory . $this->ci->router->class . '/' . $this->ci->router->method;
		}
				
		//
		// Time to render the layout
		//
		
		// The cache_id is based on the layout name + the current url so that 
		// variances between pages will be taken into affect.
		$this->cache_id = md5($layout . $this->ci->uri->uri_string);
		
		$output = '';
		
		if ($this->cache_layout && $this->is_cached('layout'))
		{ 
			// Show the cache
			$output = $this->get_cache();
		} else 
		{	
			// Start by checking if there's a theme available
			if (!empty($this->active_theme))
			{ 
				// A theme has been specified. First try to locate the file under
				// the active theme. If that doesn't work, fall back to the default theme.
				$output = $this->ci->load->view($this->_check_layout($layout), $this->data, true);
				if (empty($output))
				{ 
					// Oops. Not found in active theme. Try the default.
					$output = $this->ci->load->view($this->_check_layout($layout, true), $this->data, true);
					if (empty($output))
					{
						// Layout not found, so spit out an error.
						show_error('Unable to load the requested file: ' . $layout);
					} 
				}
				
			} else 
			{	
				// We're not using themes, so default to the 'views' folder
				$output = $this->ci->load->view($layout, $this->data, true);
				if (empty($output))
				{
					// Show an error here, since we're overriding CI's loader.
					show_error('Unable to load the requested file: '. $layout);
				}
				
			}
			
			// Cache the output buffer if required.
			if ($this->cache_layout && !$this->is_cached())
			{
				$this->write_cache($output);
			} 
			
		}
		
		$output = str_replace('{yield}', $this->yield(true), $output);	
		
		global $OUT;
		$OUT->set_output($output);
				
		$this->_mark('Template_Render_end');
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Renders the current page. 
	 *
	 * Uses a view based on the controller/function being run. (See __constructor).
	 * 
	 * @access public
	 * @return void
	 */
	public function yield($bypass=false) 
	{ 
		$this->_mark('Template_Yield_start');
		
		// If we've cached the layout, we don't return anything except the 
		// yield function itself.
		if ($bypass === true)
		{
			return '{yield}';
		} else 
		{
			return $this->_render_view($this->current_view, $this->cache_view);
		}
		
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Renders a "block" to the view.
	 *
	 * A block is a partial view contained in a view file in the 
	 * application/views folder. It can be used for sidebars,
	 * headers, footers, or any other recurring element within
	 * a site. It is recommended to set a default when calling
	 * this function within a layout. The default will be rendered
	 * if no methods override the view (using the set_block() method).
	 * 
	 * @access public
	 * @param string $name. (default: '')
	 * @param string $default_view. (default: '')
	 * @return void
	 */
	public function block($block_name='', $default_view='', $data=array(), $cache_me = false, $cache_expires=900) 
	{
		$this->_mark('Template_Block_start');
		
		if (empty($block_name)) 
		{
			log_message('debug', '[Ocular] No block name provided.');
			return;
		}

		if (array_key_exists($block_name, $this->blocks))
		{
			$block_name = $this->blocks[$block_name];
		} else {
			$block_name = $default_view;
		}

		if (empty($block_name)) 
		{
			log_message('debug', 'Ocular was unable to find the default block: ' . $default_view);
			return;
		}

		return $this->_render_view($block_name, $cache_me, $data);
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Stores the block named $name in the blocks array for later rendering.
	 * The $current_view variable is the name of an existing view. If it is empty,
	 * your script should still function as normal.
	 * 
	 * @access public
	 * @param string $name. (default: '')
	 * @param string $view. (default: '')
	 * @return void
	 */
	public function set_block($block_name='', $view_name='') 
	{
		$this->_mark('Template_Set_Block_start');
		
		if (!empty($block_name))
		{
			$this->blocks[$block_name] = $view_name;
		} 
		
		$this->_mark('Template_Set_Block_end');
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Makes it easy to save information to be rendered within the views.
	 * 
	 * @access public
	 * @param string $name. (default: '')
	 * @param string $value. (default: '')
	 * @return void
	 */
	public function set($var_name='', $value='') 
	{
		$this->_mark('Template_Set_start');
		
		// Added by dkenzik
	    // 20101001
	    // Easier migration when $data is scaterred all over your project
	    //
	    if(is_array($var_name) && $value=='')
	    {
	        foreach($var_name as $key => $value)
	        {
	        	$this->data[$key] = $value;
	        }           
	    }
	    else
	    {
	        $this->data[$var_name] = $value;
	    }
		
		$this->_mark('Template_Set_end');
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Sets the active_theme property and adds a trailing slash.
	 * 
	 * @access public
	 * @param string $name. (default: '')
	 * @return void
	 */
	public function set_theme($theme_name='') 
	{
		$this->active_theme = $theme_name . '/';
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Returns the name of the active theme.
	 * 
	 * @access public
	 * @return void
	 */
	public function theme() 
	{
		return $this->active_theme();
	}
	
	//---------------------------------------------------------------
	
	/**
	 * is_ajax function.
	 *
	 * Checks if a request has been made through AJAX or not.
	 * Thanks to Jamie Rumbelow (http://jamierumbelow.net) for this one.
	 * 
	 * @access public
	 * @return void
	 */
	public function is_ajax() 
	{
		return ($this->ci->input->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') ? TRUE : FALSE;
	}
	
	//---------------------------------------------------------------
	
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
	public function set_message($message='', $type='info') 
	{
		if (!empty($message))
		{
			if (class_exists('CI_Session'))
			{
				$this->ci->session->set_flashdata('message', $type.'::'.$message);
			}
			
			$this->message = array('type'=>$type, 'message'=>$message);
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
	public function message() 
	{
		$message = '';		// The message body.
		$type	 = '';		// The message type (used for class)
	
		// Does session data exist? 
		if (class_exists('CI_Session'))
		{
			$message = $this->ci->session->flashdata('message');
		}

		if (!empty($message))
		{
			// Split out our message parts
			$temp_message = explode('::', $message);
			$type = $temp_message[0];
			$message = $temp_message[1];
			
			unset($temp_message);
		} 
		
		// If message is empty, we need to check our own storage.
		if (empty($message))
		{
			if (empty($this->message['message']))
			{
				return '';
			}
			
			$message = $this->message['message'];
			$type = $this->message['type'];
		}
		
		// Grab out message template and replace the placeholders
		$template = str_replace('{type}', $type, $this->ci->config->item('OCU_message_template'));
		$template = str_replace('{message}', $message, $template);
		
		// Clear our session data so we don't get extra messages. 
		// (This was a very rare occurence, but clearing should resolve the problem.
		$this->ci->session->flashdata('message', '');
		
		return $template;
	}
	
	//---------------------------------------------------------------
	
	//---------------------------------------------------------------
	// !PRIVATE FUNCTIONS
	//---------------------------------------------------------------
	
	/**
	 * Sets a benchmark mark if 'TPL_profile' is set to true
	 * in the config file, or if TRUE was passed as a second
	 * parameter (allowing you to benchmark just one function.)
	 * 
	 * @access private
	 * @param string $name. (default: '')
	 * @param mixed $override. (default: FALSE)
	 * @return void
	 */
	private function _mark($name='', $override=FALSE) 
	{
		// Is Template supposed to provide benchmarks? 
		if ($this->ci->config->item('TPL_profile') === TRUE || ($override === TRUE))
		{
			if (!empty($name))
			{
				$this->ci->benchmark->mark($name);
			}
		} 
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Sets the current theme, based on user_agents.
	 * 
	 * @access private
	 * @return void
	 */
	private function _set_theme() 
	{
		if ($this->ci->config->item('OCU_use_mobile_themes') === TRUE)
		{
			// Load our user_agent library
			$this->ci->load->library('user_agent');
			
			$agent ='';
			
			// Grab our agent
			if ($this->ci->agent->is_mobile())
			{
			    $agent = $this->ci->agent->mobile();
			}
			else if ($this->ci->agent->is_browser())
			{
			    $agent = $this->ci->agent->browser().' '.$this->ci->agent->version();
			}
			else if ($this->ci->agent->is_robot())
			{
			    $agent = $this->ci->agent->robot();
			}
			
			// Check our themes array to see if we can find a match.
			if (!empty($agent))
			{
				$themes = $this->ci->config->item('OCU_themes');
				
				foreach ($themes as $theme => $values)
				{
					// If the agent is found anywhere inside the values,
					// Then we've found our theme to use.
					if (in_array($agent, $values) === TRUE)
					{
						$this->active_theme = $theme . '/';						
						// Get out of here.
						break;
					}
				}
			}
			
		}
		
		// If we still don't have a theme, set it to the default.
		if (empty($this->active_theme) && $this->ci->config->item('OCU_use_mobile_themes'))
		{
			$this->active_theme = $this->default_theme . '/';
		}		
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Adjusts the name passed based on the current theme (if any)
	 * 
	 * @access private
	 * @return void
	 */
	private function _check_view($name='', $use_default=FALSE) 
	{
		if (!empty($name))
		{
			// Is there a theme assigned? If we're using themes, 
			// it should already be set by the time we get here.				
			return ($use_default === TRUE) ? $this->default_theme . '/' . $name : $this->active_theme . $name;
		}
		
		return $name;
	}
	
	//---------------------------------------------------------------
	
	/**
	 * Handles the actual rendering of a view, by checking
	 * for theme features and parsing methods.
	 *
	 * Used by the yield and block methods.
	 *
	 * @access	private
	 * @return	boolean
	 */
	private function _render_view($view_name='', $cache_me=false, $data=null)
	{
	
		if (empty($view_name))
		{
			show_error('[Ocular] No view to render.');
			return false;
		}
				
		$content = '';
		
		if (!is_array($data))
		{
			$data = $this->data;
		}
		
		if ($cache_me && !$this->is_ajax())
		{
			// To discourage conflicts, use the view_name and the current uri
			// As the cache_id.
			$this->cache_id = md5($view_name . $this->ci->uri->uri_string);
			
			if ($this->is_cached())
			{
				$content = $this->get_cache();
			}
		}
	
		// Start by checking if there's a theme available
		if (empty($content) && !empty($this->active_theme))
		{
			// A theme has been specified. First, try to locate the file under
			// the active_theme. If that doesn't work, fall back to the default.
			if ($this->use_ci_parser === TRUE) 
			{
				$this->ci->load->library('parser');
				$content = $this->ci->parser->parse($this->_check_view($view_name), $data, true);
			} else 
			{
				$content = $this->ci->load->view($this->_check_view($view_name), $data, true);
			}


			if (empty($content))
			{
				// Oops. Not found in the active_theme. Try the default.
				$content = $this->ci->load->view($this->_check_view($view_name, true), $data, true);
			}
		} else if (empty($content) && empty($this->active_theme))
		{
			if ($this->use_ci_parser === TRUE)
			{
				$this->ci->load->library('parser');
				$content = $this->ci->parser->parse($view_name, $data, true);
			} else 
			{
				$content = $this->ci->load->view($view_name, $data, true);
			}
		}
		
		
		if (empty($content))
		{
			//ob_clean();
			show_404($view_name);
			return false;
		}
		
		// Should we cache it? 
		if ($cache_me && !$this->is_ajax() && !$this->is_cached())
		{
			$this->write_cache($content);
		} 
		
		return $content;
	}
		
	//---------------------------------------------------------------
	
	private function _check_layout($name='', $use_default=FALSE) 
	{	
		if (!empty($name))
		{
			// see if it includes the 'layout' folder
			if (strpos($name, 'layouts') === FALSE)
			{
				$name = $this->ci->config->item('OCU_layout_folder') . $name;
			}
		
			// Is there a theme assigned? If we're using themes,
			// if should already be set by the time we're here.
			if (!empty($this->active_theme))
			{
				return ($use_default === TRUE) ? $this->default_theme . '/' . $name : $this->active_theme . $name;
			}
		}
	}
	
	//---------------------------------------------------------------
	
	//---------------------------------------------------------------
	// CACHE FUNCTIONS
	//---------------------------------------------------------------
	
	/**
	 *	Checks to see if a file is cached, and stores the result
	 * to save on processing later.
	 */
	private function is_cached($type='view') 
	{ 
		// If it's already cached, get out of here...
		if (array_key_exists($this->cache_id, $this->cached) &&
			$this->cached[$this->cache_id] == true) 
		{
			return true;
		}
		
		// Nothing to do if there's no cache_id to work with. 
		if (!$this->cache_id) 
		{ 
			return false;
		} 
	
		$this->cached[$this->cache_id] = false;
		
		$cache_file = $this->cache_path . $this->cache_id .'.html';
		
		// Cache file exists?
		if (!is_file($cache_file)) return false;
		
		// Can we get the time of the file?
		if (!($mtime = filemtime($cache_file))) return false;
		
		// Has the cache expired? 
		$newtime = $type=='view' ? $this->cache_view_expires : $this->cache_layout_expires;
		
		if (($mtime + $newtime) < time())
		{	
			@unlink($cache_file);
			return false;
		}
		else 
		{	
			// Cache the results of this is_cached() call. Why? so
			// we don't have to double the overhead for each view.
			// If we didn't cache, it would be hitting the file system
			// twice as much (file_exists() && filemtime() [twice each])
			$this->cached[$this->cache_id] = true;
			return true;
		}
	}
	
	//---------------------------------------------------------------
	
	private function get_cache() 
	{
		$cache_file = $this->cache_path . $this->cache_id .'.html';
		
		if (!function_exists('read_file'))
		{
			$this->ci->load->helper('file');
		}
		
		return read_file($cache_file);
	}
	
	//---------------------------------------------------------------
	
	private function write_cache($content=null) 
	{
		if (empty($content)) return;
		
		if (!function_exists('write_file'))
		{
			$this->ci->load->helper('file');
		}
		
		write_file($this->cache_path . $this->cache_id .'.html', $content);
	}
	
	//---------------------------------------------------------------

}

// END of Template class


function check_menu($item='')
{
	$ci =& get_instance();

	if (strtolower($ci->router->fetch_class()) == strtolower($item))
	{
		return 'class="current"';
	}
	
	return '';
}

//---------------------------------------------------------------

function check_sub_menu($item='')
{
	$ci =& get_instance();

	if (strtolower($ci->router->fetch_method()) == strtolower($item))
	{
		return 'class="current"';
	}
	
	return '';
}

//---------------------------------------------------------------

/**
 * Renders a view based on current theme.
 *
 * @since 2.13
 */
function themed_view($view=null, $data=array()) 
{
	if (empty($view) || !is_string($view))
	{
		return;
	}
	
	$ci =& get_instance();
	
	// Load the data so it's available...
	$ci->load->vars($data);
	
	return $ci->template->render_view($view);
}

//---------------------------------------------------------------

/**
 * Will create a breadcrumb from either the uri->segments or
 * from a key/value paired array passed into it. 
 *
 * @since 2.12
 */
function breadcrumb($my_segments=null) 
{
	$ci =& get_instance();
	
	if (!class_exists($CI_URI))
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
				echo '<a href="'. $url .'">'. str_replace('_', ' ', strtolower($segment)) .'</a>'. $ci->config->item('OCU_breadcrumb_symbol');
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
				echo '<a href="'. $url .'">'. str_replace('_', ' ', strtolower($title)) .'</a>'. $ci->config->item('OCU_breadcrumb_symbol');
			}
		}
	}
}

//---------------------------------------------------------------

/* End of file Template.php */
/* Location: ./application/libraries/Template.php */