#Metadata Plugin

##Description

CakePHP plugin to maintain meta tags for an application. These meta tags can be applied at a global level and be applied to every request, and overwrites can be applied per controller or controller action.

##Installation

Install the plugin

	cd app/plugins
	git clone git://github.com/joeytrapp/metadata.git
	
Include the component

	// app/app_controller.php
	var $components = array('Metadata.Metadata', /* All other components */);
	
The Metadata helper will get automatically included.

##Usage

To apply all of your metadata in a single location, create a metadata.php file in app/config:

	// app/config/metadata.php
	$metadata = array(
		'_all' => array( // Global meta tags for all controllers
			'description' => 'This is the site wide global description tag'
		),
		'controller_name' => array(
			'_all' => array( // Global meta tags for all actions in controller
				'description' => 'This is the description used for all actions in controller_name. Overwrites the site global description'
			),
			'action' => array( // Specific meta tags for this action
			)
		),
		'pages' => array( // Pages controller meta tags
			'_all' => array( // Meta tags for all static pages
			
			),
			'home' => array( // If controller is pages, the last pass param is used (file name)
				'description' => array(
					'content' => 'Home page description',
					'charset' => 'UTF-8'
				)
			)
		)
	);

To apply metadata from within the controller, you can set a controller property called metadata

	// app/app_controller.php
	var $metadata = array(
		'description' => 'Global description for this controller',
		'action' => array(
			'description' => 'Description for action only'
		)
	);

Metadata can also be added from within a controller action with a component method

	function index() {
		// other controller stuff
	
		$this->Metadata->metadata('description', 'This is the description set from within the controller action');
	
		// other controller stuff
	}

Then to load all of the meta tags that were set use the Metadata.Metadata helper

	// app/views/layouts/default.ctp
	<title>
		<?php echo $this->Metadata->meta(); ?>
	</title>
	
Can also add additional meta tags from the view with the same helper method

	<?php echo $this->Metadata->meta('description', 'Description meta tag applied from view'); ?>