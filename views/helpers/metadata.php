<?php
/**
 * Metadata Helper
 *
 * @package helpers
 * @subpackage views.helpers
 * @author Joey Trapp <jtrapp07@gmail.com>
 */
class MetadataHelper extends AppHelper {

	/**
	 * Name of the helper.
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'Metadata';

	/**
	 * Additional helpers to be loaded.
	 *
	 * @var array
	 * @access public
	 */
	var $helpers = array('Html');

	/**
	 * If metadata was set from the Metadata component, it will be set
	 * here. To render call MetadataHelper::meta() with no params.
	 *
	 * @var array
	 * @access public
	 */
	var $metadata = array();

	/**
	 * Overwriting the default constructor to merge in the options that
	 * could be passed in from the controllers include
	 *
	 * @param array $options
	 * @access public
	 * @return void
	 */
	function __construct($options = array()) {
		parent::__construct($options);
		$this->metadata = $options;
	}

	/**
	 * Wrapper for HtmlHelper::meta. If at least $key and $value are set, this
	 * will return HtmlHelper::meta. To render all the meta tags that were set
	 * in the controller, call meta() with no params.
	 *
	 * @param mixed $key
	 * @param string $value
	 * @param array $options
	 * @access public
	 * @return mixed
	 */
	function meta($key = null, $value = '', $options = array()) {
		if (is_array($key)) {
			if (
				isset($key['key']) &&
				!empty($key['key']) &&
				isset($key['value']) &&
				!empty($key['value'])
			) {
				$_key = $key['key'];
				unset($key['key']);
				$_value['value'];
				unset($key['value']);
				return $this->Html->meta($_key, $_value, $key);
			}
		} elseif (is_string($key)) {
			if (is_array($value) && isset($value['value']) && !empty($value['value'])) {
				$_value = $value['value'];
				unset($value['value']);
				return $this->Html->meta($key, $_value, $value);
			} elseif (is_string($value)) {
				return $this->Html->meta($key, $value, $options);
			} elseif ($value == null) {
				return '';
			} else {
				return '';
			}
		} elseif (!$key && !$value) {
			$ret = '';
			foreach ($this->metadata as $_key => $_options) {
				$_value = $_options['value'];
				unset($_options['value']);
				$ret .= $this->Html->meta($_key, $_value, $_options)."\n";
			}
			return $ret;
		} else {
			return '';
		}
		return '';
	}
}
?>