<?php
/**
 * Metadata component
 *
 * @package components
 * @subpackage controllers.components
 * @author Joey Trapp <jtrapp07@gmail.com>
 */
class MetadataComponent extends Object {

	/**
	 * The name of the component.
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'Metadata';

	/**
	 * The name of the plugin this component is in.
	 *
	 * @var string
	 * @access public
	 */
	var $plugin = 'Metadata';

	/**
	 * Settings for the component keyed by controller name.
	 *
	 * @var array
	 * @access public
	 */
	var $settings = array();

	/**
	 * Default settings to get merged with passed in settings.
	 *
	 * @var array
	 * @access public
	 */
	var $defaults = array();

	/**
	 * The storage for the metadata items before being added
	 * to the helper include.
	 *
	 * @var array
	 * @access public
	 */
	var $metadata = array();

	/**
	 * Merges settings with defaults and adds the results to the
	 * settings array in the controllers name key.
	 *
	 * @param object $controller
	 * @param array $config
	 * @access public
	 * @return void
	 */
	function initialize(&$controller, $config = array()) {
		$settings = Set::merge($this->defaults, $config);
		$this->settings[$controller->name] = $settings;
	}

	/**
	 * Checks for the parent of the controller and if it is the
	 * appcontroller, pass the parent class through the _load
	 * method. Then run the controller through the _load method
	 *
	 * @param object $controller
	 * @access public
	 * @return void
	 */
	function startup(&$controller) {
		$this->_callback($controller, 'metaBeforeLoad');
		$parent = get_parent_class($controller);
		if (strtolower($parent) === 'appcontroller') {
			$this->_load($parent, $controller->params['action'], true);
		}
		$this->_load($controller, $controller->params['action']);
		$this->_callback($controller, 'metaAfterLoad', $this->metadata);
	}

	/**
	 * Checks if the controller has the metadataBeforeRender method
	 * and calls it. Then checks if and how the Metadata.Metadata
	 * helper is loaded, removes it if it was loaded manually, then
	 * adds it again but with all the metadata that was added during
	 * this component.
	 *
	 * @param object $controller
	 * @access public
	 * @return bool
	 */
	function beforeRender(&$controller) {
		$metadata = array();
		$this->_callback($controller, 'metaBeforeMerge', $this->metadata);
		$metadata = array();
		if (is_array($controller->helpers) && array_key_exists($this->name.'.'.$this->plugin, $controller->helpers)) {
			$metadata = $controller->helpers[$this->name.'.'.$this->plugin];
			unset($controller->helpers[$this->name.'.'.$this->plugin]);
		}
		if (is_int(array_search($this->name.'.'.$this->plugin, $controller->helpers))) {
			unset($controller->helpers[array_search($this->name.'.'.$this->plugin, $controller->helpers)]);
		}
		$this->_callback($controller, 'metaAfterMerge', $this->metadata);
		$controller->helpers[$this->name.'.'.$this->plugin] = Set::merge($this->metadata, $metadata);
	}

	/**
	 * Creates or overwrites items in $this->metadata as long as $key and $value
	 * are set. If just $key is set, the value for that key in $this->metadata
	 * is returned if it is set.
	 *
	 * @param mixed $key
	 * @param string $value
	 * @param array $options
	 * @access public
	 * @return mixed
	 */
	function metadata($key = null, $value = null, $options = array()) {
		if (is_array($key)) {
			if (
				isset($key['key']) &&
				!empty($key['key']) &&
				isset($key['value']) &&
				!empty($key['value'])
			) {
				unset($key['key']);
				$this->metadata['key'] = $key;
				return true;
			}
		} elseif (is_string($key)) {
			if (is_array($value) && isset($value['value']) && !empty($value['value'])) {
				$this->metadata[$key] = $value;
			} elseif (is_string($value)) {
				$this->metadata[$key] = array('value' => $value);
			} elseif ($value == null) {
				return isset($this->metadata[$key]) ? $this->metadata[$key] : false;
			} else {
				return false;
			}
		} else {
			return false;
		}
		return true;
	}

	/**
	 * Method gets the properties and methods from the passed in class, and checks
	 * if the class has a property called metadata. If so load metadata items from
	 * that class. Pass true in the third parameter to check if the class has a 
	 * property called helpers, and that the Metadata.Metadata helper is loaded in
	 * that class. This would be used because global metadata shouldn't be loaded
	 * from the AppController unless the component is loaded in the AppController.
	 *
	 * @param mixed $class
	 * @param string $action
	 * @access private
	 * @return void
	 */
	function _load(&$class = null, $action = null, $check = false) {
		$methods = get_class_methods($class);
		if (is_object($class)) {
			$vars = get_class_vars(get_class($class));
		} else {
			$vars = get_class_vars($class);
		}
		$pass = true;
		if ($check) {
			if (
				array_key_exists('components', $vars) &&
				(
					isset($vars['components'][$this->name.'.'.$this->plugin]) &&
					!empty($vars['components'][$this->name.'.'.$this->plugin])
				) ||
				(
					array_search($this->name.'.'.$this->plugin, $vars['components'])
				)
			) {
				$pass = true;
			} else {
				$pass = false;
			}
		}
		if (array_key_exists('metadata', $vars) && $pass) {
			$data = $vars['metadata'];
			if (
				isset($data[$action]) &&
				is_array($data[$action])
			) {
				foreach ($data[$action] as $key => $value) {
					if (!is_int($key)) {
						$this->metadata($key, $value);
					} elseif (is_array($value)) {
						$this->metadata($value);
					}
				}
			}
			foreach ($methods as $m) {
				if (isset($data[$m])) {
					unset($data[$m]);
				}
			}
			foreach ($data as $key => $value) {
				if (!is_int($key)) {
					$this->metadata($key, $value);
				} elseif (is_array($value)) {
					$this->metadata($value);
				}
			}
		}
	}

	/**
	 * Generic method for handling the callbacks.
	 *
	 * @param object $class
	 * @param string $callback
	 * @param array $arg
	 */
	function _callback(&$class, $callback, $arg = array()) {
		$args = func_get_args();
		if (count($args) === 3) {
			if (method_exists($class, $callback)) {
				$tmp = $class->{$callback}($arg);
				if (is_array($tmp)) {
					$this->callback = $tmp;
				}
			}
		}
		return;
	}
}
?>