<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//---------------------------------------------------------------
// OCULAR TEMPLATE LIBRARY
// by Lonnie Ezell (http://igniteyourcode.com/ocular)
//
// version: 2.0 Beta 2
//---------------------------------------------------------------

/**
 * MY_Loader class modifieds the _ci_load function to return false
 * if Ocular is loaded and it is unable to find the view. 
 *
 * This will not work with with Matchbox or HMVC as it stands. I
 * tried to avoid this but was unable to come up with a solution 
 * that would work out of the box. If you wish to use Ocular with
 * either of those solutions you must modify their Loader classes
 * with the changes in this file. You can find the necessary changes
 * in this file wrapped in ///@ OCULAR CHANGES @/// tags. There is 
 * no guarantee that just dropping the code in their Loader class 
 * will work, and I cannot support these modifications at this time. 
 * So, extend Matchbox, HMVC and similar libraries at your own risk.
 * 
 * @extends CI_Loader
 */
class MY_Loader extends CI_Loader {

	//---------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();
	}

	//---------------------------------------------------------------

	/**
	 * Loader
	 *
	 * This function is used to load views and files.
	 * Variables are prefixed with _ci_ to avoid symbol collision with
	 * variables made available to view files
	 *
	 * @access	private
	 * @param	array
	 * @return	void
	 */
	function _ci_load($_ci_data)
	{
		// Set the default data variables
		foreach (array('_ci_view', '_ci_vars', '_ci_path', '_ci_return') as $_ci_val)
		{
			$$_ci_val = ( ! isset($_ci_data[$_ci_val])) ? FALSE : $_ci_data[$_ci_val];
		}

		// Set the path to the requested file
		if ($_ci_path == '')
		{
			$_ci_ext = pathinfo($_ci_view, PATHINFO_EXTENSION);
			$_ci_file = ($_ci_ext == '') ? $_ci_view.EXT : $_ci_view;
			$_ci_path = $this->_ci_view_path.$_ci_file;
		}
		else
		{
			$_ci_x = explode('/', $_ci_path);
			$_ci_file = end($_ci_x);
		}
		
		if ( ! file_exists($_ci_path))
		{
			///@ OCULAR CHANGES @///
			
			// Is Ocular loaded? 
			if (class_exists('Template'))
			{
				return false;
			}
			///@ END OCULAR CHANGES @///
			
			show_error('Unable to load the requested file: '.$_ci_file);
		}
	
		// This allows anything loaded using $this->load (views, files, etc.)
		// to become accessible from within the Controller and Model functions.
		// Only needed when running PHP 5
		
		if ($this->_ci_is_instance())
		{
			$_ci_CI =& get_instance();
			foreach (get_object_vars($_ci_CI) as $_ci_key => $_ci_var)
			{
				if ( ! isset($this->$_ci_key))
				{
					$this->$_ci_key =& $_ci_CI->$_ci_key;
				}
			}
		}

		/*
		 * Extract and cache variables
		 *
		 * You can either set variables using the dedicated $this->load_vars()
		 * function or via the second parameter of this function. We'll merge
		 * the two types and cache them so that views that are embedded within
		 * other views can have access to these variables.
		 */	
		if (is_array($_ci_vars))
		{
			$this->_ci_cached_vars = array_merge($this->_ci_cached_vars, $_ci_vars);
		}
		extract($this->_ci_cached_vars);
				
		/*
		 * Buffer the output
		 *
		 * We buffer the output for two reasons:
		 * 1. Speed. You get a significant speed boost.
		 * 2. So that the final rendered template can be
		 * post-processed by the output class.  Why do we
		 * need post processing?  For one thing, in order to
		 * show the elapsed page load time.  Unless we
		 * can intercept the content right before it's sent to
		 * the browser and then stop the timer it won't be accurate.
		 */
		ob_start();
				
		// If the PHP installation does not support short tags we'll
		// do a little string replacement, changing the short tags
		// to standard PHP echo statements.
		
		if ((bool) @ini_get('short_open_tag') === FALSE AND config_item('rewrite_short_tags') == TRUE)
		{
			echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($_ci_path))));
		}
		else
		{
			include($_ci_path); // include() vs include_once() allows for multiple views with the same name
		}
		
		log_message('debug', 'File loaded: '.$_ci_path);
		
		// Return the file data if requested
		if ($_ci_return === TRUE)
		{		
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}

		/*
		 * Flush the buffer... or buff the flusher?
		 *
		 * In order to permit views to be nested within
		 * other views, we need to flush the content back out whenever
		 * we are beyond the first level of output buffering so that
		 * it can be seen and included properly by the first included
		 * template and any subsequent ones. Oy!
		 *
		 */	
		if (ob_get_level() > $this->_ci_ob_level + 1)
		{
			ob_end_flush();
		}
		else
		{
			// PHP 4 requires that we use a global
			global $OUT;
			$OUT->append_output(ob_get_contents());
			@ob_end_clean();
		}
	}


	//---------------------------------------------------------------
}

// END MY_Loader class

/* End of file MY_Loader.php */
/* Location: ./application/libraries/MY_Loader.php */