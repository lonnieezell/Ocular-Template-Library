<?php

class Template_hook
{
	/** @var CodeIgniter */
	protected $ci;
	
	// -------------------------------------------------------------
	
	public function __construct()
	{
		$this->ci = get_instance();
	    log_message('info',__CLASS__.' initialized.');
	}
	
	// -------------------------------------------------------------
	
	/**
	 * Pretend this is CakePHP and render the view automatically
	 * Based on the controller & method (Ocular's MY_Loader takes care of that part)
	 * This should be called via the post_controller hook.
	 * This is easily suppressed from within a controller by setting $this->template->autorender = false;
	 * @return void
	 */
	public function autorender()
	{
		$tpl = $this->ci->template;
		if($tpl->autorender)
		{
			$tpl->render();
		}
	}
		
}

/* End of file template_hook.php */
/* Location: ./application/hooks/template_hook.php */