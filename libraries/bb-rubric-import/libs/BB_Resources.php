<?php

class BB_Resources {

	private $path;
	private $zip_file_name;

	private $data;
	private $fp;

	public $rubric;
	public $resource_links;
	public $context;

    private $valid = FALSE;

	public function __construct($path) {

		if(isset($path)) {
			$this->path = $path;
		}

		$ims_manifest = $this->path.DIRECTORY_SEPARATOR."imsmanifest.xml";

		if(!file_exists($ims_manifest)) {

			$this->valid = FALSE; // no manifest file
        } else {

		    $this->valid = $this->parse();
        }
	}

    public function isValid() {
        return $this->valid;
    }

	private function start($parser,$element_name,$element_attrs)
	{
		switch($element_name) {
			case "RESOURCE":
				foreach ($element_attrs as $key => $attr) {
					if($key === "TYPE") {
						$_obj = array();
						$_obj['bbTitle'] = $attr;
						$_obj['bbFile'] = $element_attrs['BB:FILE'];
						$_obj['identifier'] = $element_attrs['IDENTIFIER'];
						$_obj['type'] = $element_attrs['TYPE'];
						$_obj['xmlBase'] = $element_attrs['XML:BASE'];

						if($attr === "course/x-bb-rubrics") {
							$this->rubric = (object)$_obj;
						}
						if($attr === "course/x-bb-csresourcelinks") {
							$this->resource_links = (object)$_obj;
						}
						if($attr === "resource/x-mhhe-course-cx") {
							$this->context = (object)$_obj;
						}

						unset($_obj);
					}

				}
			break;
		}
	}

	private function stop($parser,$element_name)
    {
    	echo "\n";
    }

    private function char($parser,$data)
    {
    	echo $data;
    }

    private function entity($parser, $entity) {
    	echo $entity;
    }

	private function parse() {
		try {
			$parser = xml_parser_create();

			xml_set_element_handler($parser, array($this, "start"), array($this, "stop"));
			xml_set_character_data_handler($parser, array($this, "char"));
			xml_set_unparsed_entity_decl_handler($parser, array($this, "entity"));

			$this->fp = fopen($this->path.DIRECTORY_SEPARATOR."imsmanifest.xml","r");

			while ($data = fread($this->fp,4096))
			{
				xml_parse($parser,$data,feof($this->fp)) or
				die (sprintf("XML Error: %s at line %d",
						xml_error_string(xml_get_error_code($parser)),
						xml_get_current_line_number($parser)));
			}
			fclose($this->fp);

			xml_parser_free($parser);
			unset($parser);
            unlink($this->path.DIRECTORY_SEPARATOR."imsmanifest.xml");
		} catch (Exception $e) {
			return FALSE;
		}

		return TRUE;
	}
}
