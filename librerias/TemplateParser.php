<?php

class TemplateParser {
	// Template file
	protected $file;
	// Values to be set inside the placeholders
	protected $values = array();

	public function __construct($file) {
		$this->file = $file;
	}

	// Set data to values array
	public function set_data($data) {
		$this->values = $data;
	}

	public function output() {
		// Check if file exists
		if (!file_exists($this->file)) {
			return "Error loading file ($this->file).";
		}

		// Read file contents
		$output = file_get_contents($this->file);

		// Start replacing placeholders with actual values
		foreach ($this->values as $key => $value) {
			$tag_to_replace = "{{$key}}";
			$output = str_replace($tag_to_replace, $value, $output);
		}
		// Returns the final string updated with values.
		return $output;
	}
}