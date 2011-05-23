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
	 * Name of the config file and var expected to exist in
	 * the config file. Can include extension.
	 *
	 * @var string
	 * @access public
	 */
	var $config = 'metadata.php';

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
		if (count($config)) {
			foreach ($config as $key => $value) {
				if (isset($this->{$key})) {
					$this->{$key} = $value;
				}
			}
		}
	}

	/**
	 * Calls the metaBeforeLoad callback on the controller. Then
	 * checks if a config file exists. If so loads all the meta 
	 * out of that config file for the controller and action being
	 * accessed. If the controller is pages, then the last pass param
	 * is used instead of the action. Then checks for the parent of
	 * the controller and if it is the appcontroller, pass the parent
	 * class through the _load method. Then run the controller through
	 * the _load method.
	 *
	 * @param object $controller
	 * @access public
	 * @return void
	 */
	function startup(&$controller) {
		$this->_callback($controller, 'metaBeforeLoad');
		$_file = $this->config;
		if (strstr($_file, '.php')) {
			$_config = substr($_file, 0, strpos($_file, '.'));
		} else {
			$_config = $_file;
			$_file .= '.php';
		}
		if (file_exists(APP.'config'.DS.$_file)) {
			include(APP.'config'.DS.$_file);
			$_controller = $controller->params['controller'];
			$_action = $controller->params['action'];
			$_page = null;
			if ($_controller == 'pages' && count($controller->params['pass'])) {
				$_page = $controller->params['pass'][0];
			}
			$load = array('all' => array(), 'controller' => array(), 'action' => array());
			if (isset(${$_config}) && !empty(${$_config})) {
				$load['all'] = array_key_exists('_all', ${$_config}) ? ${$_config}['_all'] : array();
				if (array_key_exists($_controller, ${$_config})) {
					if (array_key_exists('_all', ${$_config}[$_controller])) {
						$load['controller'] = ${$_config}[$_controller]['_all'];
					}
					if (array_key_exists($_action, ${$_config}[$_controller])) {
						$load['action'] = ${$_config}[$_controller][$_action];
					} elseif (
						$_controller == 'pages' &&
						$_page != null &&
						array_key_exists($_page, ${$_config}[$_controller])
					) {
						$load['action'] = ${$_config}[$_controller][$_page];
					}
				}
			}
			foreach ($load as $metadata) {
				if (count($metadata)) {
					foreach ($metadata as $name => $content) {
						$this->metadata($name, $content);
					}
				}
			}
		}
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
	function metadata($name = null, $url = null, $attributes = array()) {
		if (is_array($name)) {
			if (
				isset($name['name']) &&
				!empty($name['name']) &&
				(isset($name['content']) &&
				!empty($name['content'])) ||
				(isset($name['url']) &&
				!empty($name['url']))
			) {
				unset($name['name']);
				$this->metadata['name'] = $key;
				return true;
			} elseif (count($name)) {
				foreach ($name as $i => $data) {
					if (is_int($i)) {
						$this->metadata($data);
					}
				}
			}
		} elseif (is_string($name)) {
			if (
				is_array($url) &&
				(isset($url['content']) &&
				!empty($url['content'])) ||
				(isset($url['url']) &&
				!empty($url['url']))
			) {
				$this->metadata[$name] = $url;
			} elseif (is_string($url)) {
				$this->metadata[$name] = array('content' => $url);
			} elseif ($value == null) {
				return array_key_exists($name, $this->metadata) ? $this->metadata[$name] : false;
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
			if (array_key_exists('_all', $data)) {
				foreach ($data['_all'] as $name => $content) {
					if (!is_int($name)) {
						$this->metadata($name, $content);
					} elseif (is_array($content)) {
						$this->metadata($content);
					}
				}
				unset($data['_all']);
			}
			if (
				isset($data[$action]) &&
				is_array($data[$action])
			) {
				foreach ($data[$action] as $name => $content) {
					if (!is_int($name)) {
						$this->metadata($name, $content);
					} elseif (is_array($content)) {
						$this->metadata($content);
					}
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