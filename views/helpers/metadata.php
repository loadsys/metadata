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
	 * Wrapper for HtmlHelper::meta. If at least $name and $content are set, this
	 * will return HtmlHelper::meta. To render all the meta tags that were set
	 * in the controller, call meta() with no params.
	 *
	 * @param mixed $name
	 * @param string $content
	 * @param array $options
	 * @access public
	 * @return mixed
	 */
	function meta($name = null, $url = null, $attributes = array()) {
		if (!$name && !$url) {
			$output = '';
			foreach ($this->metadata as $_name => $_attributes) {
				$_url = null;
				if (is_array($_attributes) && array_key_exists('content', $_attributes)) {
					$_url = $_attributes['content'];
					unset($_attributes['content']);
				} elseif (is_array($_attributes) && array_key_exists('url', $_attributes)) {
					$_url = $_attributes['url'];
					unset($_attributes['url']);
				} elseif (is_string($_attributes)) {
					$_url = $_attributes;
					$_attributes = array();
				}
				if (
					in_array(strtolower($_name), array('keywords', 'description')) ||
					preg_match('/.*\/.*/', $_url)
				) {
					$output .= $this->Html->meta($_name, $_url, $_attributes);
				} else {
					if (strtolower($_name) !== 'title') {
						$attr = array_merge($_attributes, array('name' => $_name, 'content' => $_url));
						$output .= $this->Html->meta($attr);
					}
				}
				
			}
			return $output;
		}
		return $this->Html->meta($name, $url, $attributes);
	}

	/**
	 * Takes the view property $title_for_layout and optionally a key and merge
	 * option. If no data exists in the metadata array for the supplied key (defaults
	 * to 'title') then the $title_for_layout is returned. If a values does exist
	 * and merge is set to true, then the metadata title and the $title_for_layout
	 * are merged together. Otherwise just the metadata title is returned.
	 *
	 */
	function title($default = '', $key = 'title', $merge = false) {
		$ret = $default;
		if (array_key_exists($key, $this->metadata)) {
			$ret = $this->metadata[$key];
			if ($merge) {
				$ret .= ' '.$default;
			}
		}
		return $ret;
	}
}
?>