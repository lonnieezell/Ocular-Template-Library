<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $site_name ?></title>    
    <link rel="shortcut icon" type="image/ico" href="images/favicon.ico" />
    
</head>

<body>

<!-- begin: wrapper -->
<div id="wrapper">	
    <!-- begin: Content Wrapper -->
    <div id="content">
    	
    	<?php echo $this->template->message(); ?>
    	
        <!-- begin: Promo Container -->
        <div id="promo-container">
        	<h1><?php echo isset($this->template->view) ? $this->template->view : '' ?></h1>
        <!-- end: Promo Container -->
        </div>
        
        <?php echo $this->template->yield(); ?>
        
		<p class="small clear center">&copy; 2009 IgniteYourCode.com<br />
			Page rendered in {elapsed_time} seconds using {memory_usage}.<br/>        
    <!-- end: Content Wrapper -->
    </div>
    
<!-- end: wrapper -->
</div>

</body>
</html>
