<?php
namespace LTI\ExtensionHooks;

class BB_Rubrics {

	private $path;
	private $filename;

	private $vals;
	private $keys;

	private $document;
	private $data;
	private $default;

	private $pointer; // pointer to data array

	public function __construct($filename, $path = ".") {
		if(isset($path)) {
			$this->path = $path;
		}

		if(isset($filename)) {
			$this->filename = $filename;
		} else {
			die("Please provide a filename\n");
		}

		if(!file_exists($this->path.DIRECTORY_SEPARATOR.$this->filename)) {
			die($this->filename." not found\n");
		}
		$this->data = array();

		$this->default = array( "rows" => 0,
													"max_value" => 0.0,
													"type" => "",
													"description" => "",
													"row_headers" => array(),
													"col_headers" => array(),
													"row_weights" => array(),
													"col_scores" => array()
										);

		$this->simpleParse();
		$this->parseHTMLRows($this->document);

		$data_dir = $this->path.DIRECTORY_SEPARATOR.'data';
		if(!file_exists($data_dir)) {
				mkdir($data_dir);
		}

		$sanitized = $this->data;

		foreach($this->data as $key => $val) {
				// sanitize data
				foreach($val as $subkey => $field) {
							if($subkey == 'row_headers') {
								foreach($field as $i => $header) {
											if(is_string($header) && strlen($header) > 45) {
													$trunced = substr($header, 0, 42);
													$sanitized[$key][$subkey][$i] = "$trunced...";
											}
								}
							}
				}

				file_put_contents($data_dir.DIRECTORY_SEPARATOR.$key.".data", serialize($sanitized[$key]));
		}

    unlink($this->path.DIRECTORY_SEPARATOR.$this->filename);
	}

	public function getTitle() {
		return $this->getNodeAttributeValue("TITLE");
	}

	public function getDescription() {
		return $this->getNodeAttributeValue("DESCRIPTION");
	}

	public function getTableHeader() {

	}

	public function getRubrics() {
		$rubrics = array();

		foreach($this->doms as $key => $item) {
			$rubrics[$key] = array("title" => $this->titles[$key], "grid_html" => $item["grid"]->saveHTML($item["grid_index"]["table"]),
								"list_html" => $item["list"]->saveHTML($item["list_index"]["div_list"]),
                                  "total_score" => $item["total_score"]
                                );
		}

		return $rubrics;
	}

	private $rubric_count = 0;

	private $grid_dom;
	private $grid_dom_index;

	private $list_dom;
	private $list_dom_index;
	private $init = array();

	public function parseHTMLRows($doc, $depth = 0) {

		++$depth;

		for( $doc->rewind(); $doc->valid(); $doc->next() ) {
			$atts = array();

			foreach($doc->current()->attributes() as $n => $att) {
				$atts[$n] = $att;
			}

			$this->generateRubricElements($doc->current()->getName(), $atts, $depth);

			foreach($doc->getChildren() as $name => $data) {
				$atts = array();

				foreach($data->attributes() as $n => $att) {
					$atts[$n] = $att;
				}

				$this->generateRubricElements($name, $atts, $depth);
				$this->parseHTMLRows($data, $depth);
			}
		}
	}

	// rubric DOMs
	private $doms = array();

	//rubric titles
	private $titles = array();

	private $current_row_id = "";
	private $row_count = 0;
	private $irow_count = 0;
	private $col_count = 0;
	private $col_headers = 0;
	private $total_header_tags = 0;
	private $current_cell_id = 0;
	private $last_cell_header;
	private $row_percentage = 0;
	private $last_row_header;
	private $last_div_header;

	private $list_div;

    private $default_headers = array("grid.column1.label" => "Novice",
                                        "grid.column2.label" => "Competent",
                                            "grid.column3.label" => "Proficient",
                                                    "grid.row1.label" => "Formatting",
                                                        "grid.row2.label" => "Organisation",
                                                            "grid.row3.label" => "Grammar",
                                    );

    private function map_default_header($key) {

        if(array_key_exists($key, $this->default_headers)) {
            return $this->default_headers[$key];
        }

        return FALSE;
    }

	private function generateRubricElements($name, $params, &$depth = 0) {

		switch(strtolower($name)) {
			case "rubric":
				if($this->rubric_count > 0) {

					unset($this->grid_dom);
					unset($this->list_dom);
					unset($this->grid_dom_index);
					unset($this->list_dom_index);

					$this->grid_dom_index = NULL;
					$this->list_dom_index = NULL;

					$depth = 0;
				}

				$this->init["rubricid"] = $params["id"];

				if(!isset($this->grid_dom)) {
					$this->data[(string)$this->init["rubricid"]] = $this->default;
					$this->doms[(string)$this->init["rubricid"]] = array("grid" => new \DOMDocument('1.0'), "grid_index" => array(), "list" =>  new \DOMDocument('1.0'), "list_index" => array(), "total_score" => 0);

					$this->pointer = & $this->data[(string)$this->init["rubricid"]];

					$this->grid_dom_index = & $this->doms[(string)$this->init["rubricid"]]["grid_index"];

					$this->grid_dom = & $this->doms[(string)$this->init["rubricid"]]["grid"];
					$this->grid_dom_index['html'] = & $this->grid_dom->appendChild($this->grid_dom->createElement('html'));
					$this->grid_dom_index['html']->appendChild($this->grid_dom->createElement('head'));
					$this->grid_dom_index['body'] = & $this->grid_dom_index['html']->appendChild($this->grid_dom->createElement('body'));

					$this->list_dom_index = & $this->doms[(string)$this->init["rubricid"]]["list_index"];

					$this->list_dom = & $this->doms[(string)$this->init["rubricid"]]["list"];
					$this->list_dom_index['html'] = & $this->list_dom->appendChild($this->list_dom->createElement('html'));
					$this->list_dom_index['html']->appendChild($this->list_dom->createElement('head'));
					$this->list_dom_index['body'] = & $this->list_dom_index['html']->appendChild($this->list_dom->createElement('body'));
				}

				$this->total_header_tags = 0;
				$this->rubric_count++;

			break;
			case "type":
				$this->init["type"] = (string)$params["value"];
				$this->pointer["type"] = (string)$params["value"];
			break;
			case "description":
				$this->pointer["description"] = $this->description = (string)$params['value'];
			break;
			case "title":
				$this->title = (string)$params['value'];
				$this->titles[(string)$this->init["rubricid"]] = $this->title;
			break;
			case "maxvalue":
				$this->init["maxvalue"] = $params["value"];
				$this->pointer['maxvalue'] = (float) $params["value"];
				// set at top level for ease of access
        $this->doms[(string)$this->init["rubricid"]]["total_score"] = (int) $params['value'];

				$this->grid_dom_index['table'] = & $this->grid_dom_index['body']->appendChild($this->grid_dom->createElement("table"));
				$this->grid_dom_index['thead'] = & $this->grid_dom_index['table']->appendChild($this->grid_dom->createElement("thead"));
				$this->grid_dom_index['thead_tr1'] = & $this->grid_dom_index['thead']->appendChild($this->grid_dom->createElement("tr"));
				$this->grid_dom_index['thead_tr1']->appendChild($this->grid_dom->createElement("th"));

				$this->grid_dom_index['tbody'] = & $this->grid_dom_index['table']->appendChild($this->grid_dom->createElement("tbody"));

				$this->grid_dom_index['table']->setAttribute("id", "__rubricGradingTable");
				$this->grid_dom_index['table']->setAttribute("rubricid", $this->init["rubricid"]);
				$this->grid_dom_index['table']->setAttribute("maxvalue", $this->init["maxvalue"]);
				$this->grid_dom_index['table']->setAttribute("rubrictype", $this->init["type"]);
				$this->grid_dom_index['table']->setAttribute("class", "rubricGradingTable rubricTable");
				$this->grid_dom_index['table']->setAttribute("prefix", "__");

				$this->list_dom_index['div_list'] = & $this->list_dom_index['body']->appendChild($this->list_dom->createElement("div"));
				$this->list_dom_index['div_list']->setAttribute("id", "__rubricGradingList");
				$this->list_dom_index['div_list']->setAttribute("rubricid", $this->init["rubricid"]);
				$this->list_dom_index['div_list']->setAttribute("maxvalue", $this->init["maxvalue"]);
				$this->list_dom_index['div_list']->setAttribute("rubrictype", $this->init["type"]);
				$this->list_dom_index['div_list']->setAttribute("class", "rubricGradingList");
				$this->list_dom_index['div_list']->setAttribute("prefix", "__");

				$controls = & $this->list_dom_index['div_list']->appendChild($this->list_dom->createElement("div"));
				$controls->setAttribute("class", "u_controlsWrapper");
				$input1 = & $controls->appendChild($this->list_dom->createElement("input"));
				$input1->setAttribute("type", "checkbox");
				$input1->setAttribute("id", "rubricToggleDesc");
        $input1->setAttribute("checked", "1"); // default is checked

				$label1 = & $controls->appendChild($this->list_dom->createElement("label", "Show Descriptions"));
				$label1->setAttribute("for", "rubricToggleDesc");

				//$input2 = & $controls->appendChild($this->list_dom->createElement("input"));
				//$input2->setAttribute("type", "checkbox");
				//$input2->setAttribute("id", "rubricToggleFeedback");


				//$label2 = & $controls->appendChild($this->list_dom->createElement("label", "Show Feedback"));
				//$label2->setAttribute("for", "rubricToggleFeedback");
			break;
			case "row":
				$this->pointer['rows'] += 1;
				// grid row
				$tr = & $this->grid_dom_index['tbody']->appendChild($this->grid_dom->createElement("tr"));
				$tr->setAttribute("class", "rubricGradingRow");
				$this->current_row_id = (string)$params["id"];
				$tr->setAttribute("id", $this->current_row_id);
				if(!isset($this->grid_dom_index['rows'])) {
					$this->grid_dom_index['rows'] = array();
				}
				$this->grid_dom_index['rows'][$this->current_row_id] = & $tr;

				//list row
				$div = & $this->list_dom_index['div_list']->appendChild($this->list_dom->createElement("div"));
				$div->setAttribute("class", "rubricGradingRow columnPalette");
				$div->setAttribute("rubricrowid", $this->current_row_id);

				if(!isset($this->list_dom_index['div_rows'])) {
					$this->list_dom_index['div_rows'] = array();
				}

				$this->list_dom_index['div_rows'][$this->current_row_id] = & $div;

				$this->col_headers = 0;

			break;
			case "header":

                $value = $this->map_default_header($params['value']->__toString());
                //$params['value'] = $value;
                $params['value'] = $value === FALSE ? $params['value'] : $value;

				if($depth == 2) {
					$this->pointer['row_headers'][] = (string)$params['value'];
					$th_t = & $this->grid_dom_index['rows'][$this->current_row_id]->appendChild($this->grid_dom->createElement("th"));

          $th_t->appendChild($this->grid_dom->createTextNode($params['value']));
					$this->last_row_header = & $th_t;

					$h4 = & $this->list_dom_index['div_rows'][$this->current_row_id]->appendChild($this->list_dom->createElement("h4"));
					$this->last_div_header = & $h4;

          $h4->appendChild($this->list_dom->createTextNode($params['value']));

				} else if ($depth == 3) {

					if($this->col_headers >= $this->total_header_tags++) {
						$this->pointer['col_headers'][] = (string)$params['value'];
						$th = $this->grid_dom_index['thead_tr1']->appendChild($this->grid_dom->createElement("th"));

                        $th->appendChild($this->grid_dom->createTextNode($params['value']));

						$th->setAttribute("id", "__".$this->col_headers++);
					}

					$this->last_cell_header = $params['value'];
				}
			break;
			case "cell":
				$this->current_cell_id = (string)$params['id'];

				// grid items
				$td = $this->grid_dom_index['rows'][$this->current_row_id]->appendChild($this->grid_dom->createElement("td"));
				$td->setAttribute("id", $params['id']);

				if(!isset($this->grid_dom_index['cells'])) {
					$this->grid_dom_index['cells'] = array();
				}
				$this->grid_dom_index['cells'][$this->current_cell_id] = & $td;

				// list items
				$div = $this->list_dom_index['div_rows'][$this->current_row_id]->appendChild($this->list_dom->createElement("div"));
				$div->setAttribute("rubriccellid", $params['id']);
				$div->setAttribute("class", "rubricGradingCell");

				if(!isset($this->list_dom_index['div_cells'])) {
					$this->list_dom_index['div_cells'] = array();
				}
				$cell_id = "cell_".$this->current_cell_id;

			/*	$input = & $div->appendChild($this->list_dom->createElement("input"));

				$cell_id = "cell_".$this->current_cell_id;
				$input->setAttribute("class", "rubricCellRadio");
				$input->setAttribute("id", $cell_id);
				$input->setAttribute("type", "radio");
				$input->setAttribute("name", "radio_".$this->current_row_id);

				if($this->init["type"] == "NUMERIC_RANGE") {
					$input->setAttribute("disabled", "disabled");
				}*/

				$label = & $div->appendChild($this->list_dom->createElement("label", $this->last_cell_header));
				$label->setAttribute("class", "radioLabel");
				$label->setAttribute("for", $cell_id);

				$this->list_dom_index['div_cells'][$this->current_cell_id] = & $div;
				$this->list_div = &$div;
			break;
			case "celldescription":

				//grid cell header
				$div = & $this->grid_dom_index['cells'][$this->current_cell_id]->appendChild($this->grid_dom->createElement("div"));
				$div->setAttribute("class", "rubricCellHeader");

				// grid cell header -> set points range
				$rangeValue = & $div->appendChild($this->grid_dom->createElement("div"));
				$rangeValue->setAttribute("class", "rangeValue");

				$this->grid_dom_index['range_values'][$this->current_cell_id] = & $rangeValue;

				// grid cell description
				$td = & $this->grid_dom_index['cells'][$this->current_cell_id]->appendChild($this->grid_dom->createElement("div", $params['value']));
				$td->setAttribute("class", "rubricCellDescription");

				// list cell description
				$div = & $this->list_dom_index['div_rows'][$this->current_row_id]->appendChild($this->list_dom->createElement("div", $params['value']));
				$div->setAttribute("class", "u_controlsWrapper u_indent description");

			break;
      case "numericpoints":
				if($this->init["type"] !== "NUMERIC") break;

				$this->pointer['col_scores'][] = (float)$params['value'];
				// grid start value
				$text_node = & $this->grid_dom->createTextNode($params["value"]);
				$this->grid_dom_index['numpointsval'][$this->current_cell_id] = & $params["value"];

                $input_t = & $this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($this->grid_dom->createElement("input"));
                $input_t->setAttribute("type", "radio");
                $input_t->setAttribute("value", $params["value"]);
                $input_t->setAttribute("class", "grade_input");
                $input_t->setAttribute("name", "_radio_".$this->current_row_id);

				$num_points = & $this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($text_node);

				if(!isset($this->grid_dom_index['numpoints'])) {
					$this->grid_dom_index['numpoints'] = array();
				}

				$this->grid_dom_index['numpoints'][$this->current_cell_id] = & $num_points;

				// list start value
				$text_node = & $this->list_dom->createTextNode($params["value"]);
				$this->list_dom_index['numpointsval'][$this->current_cell_id] = & $params["value"];

				$num_points = & $this->list_dom_index['div_cells'][$this->current_cell_id]->appendChild($text_node);

				// moved from line 295
				$input = &$this->list_div->appendChild($this->list_dom->createElement("input"));

					$cell_id = "cell_".$this->current_cell_id;
					$input->setAttribute("class", "rubricCellRadio");
					$input->setAttribute("id", $cell_id);
					$input->setAttribute("type", "radio");
					$input->setAttribute("value", $params["value"]);
					$input->setAttribute("name", "radio_".$this->current_row_id);

				if(!isset($this->list_dom_index['numpoints'])) {
					$this->list_dom_index['numpoints'] = array();
				}

				$this->list_dom_index['numpoints'][$this->current_cell_id] = & $num_points;
			break;
			case "numericstartpointrange":
					if($this->init["type"] !== "NUMERIC_RANGE") break;
                if(isset($this->list_dom_index['numpoints']) && $this->list_dom_index['numpoints'][$this->current_cell_id]) break;
				// grid start value
				$text_node = & $this->grid_dom->createTextNode($params["value"]);
				$this->grid_dom_index['numstartval'][$this->current_cell_id] = & $params["value"];

				$num_start = & $this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($text_node);

				if(!isset($this->grid_dom_index['numstart'])) {
					$this->grid_dom_index['numstart'] = array();
				}

				$this->grid_dom_index['numstart'][$this->current_cell_id] = & $num_start;

				// list start value
				$text_node = & $this->list_dom->createTextNode($params["value"]);
				$this->list_dom_index['numstartval'][$this->current_cell_id] = & $params["value"];

				$num_start1 = & $this->list_dom_index['div_cells'][$this->current_cell_id]->appendChild($text_node);

				if(!isset($this->list_dom_index['numstart'])) {
					$this->list_dom_index['numstart'] = array();
				}

				$this->list_dom_index['numstart'][$this->current_cell_id] = & $num_start1;
			break;
			case "numericendpointrange":
				if($this->init["type"] !== "NUMERIC_RANGE") break;
        if(isset($this->list_dom_index['numpoints']) && $this->list_dom_index['numpoints'][$this->current_cell_id]) break;
				// grid start value
				$text_node = & $this->grid_dom->createTextNode(" - ".$params["value"]);
				$this->grid_dom_index['numendval'][$this->current_cell_id] = & $params["value"];

				$num_end = & $this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($text_node);

				if(!isset($this->grid_dom_index['numend'])) {
					$this->grid_dom_index['numend'] = array();
				}

				$this->grid_dom_index['numend'][$this->current_cell_id] = & $num_end;

				// list start value
				$text_node = & $this->list_dom->createTextNode(" - ".$params["value"]);
				$this->list_dom_index['numendval'][$this->current_cell_id] = $params["value"];

				$num_end1 = & $this->list_dom_index['div_cells'][$this->current_cell_id]->appendChild($text_node);

				if(!isset($this->list_dom_index['numend'])) {
					$this->list_dom_index['numend'] = array();
				}

				$this->list_dom_index['numend'][$this->current_cell_id] = & $num_end1;
			break;

			case "percentagemin":
				if($this->init["type"] !== "PERCENTAGE_RANGE") break;
                if($this->list_dom_index['numpoints'][$this->current_cell_id]) break;
				$perc = ($params['value']/100) * $this->row_percentage;
				$val = (int) round((float)$perc, PHP_ROUND_HALF_UP);

				// grid range box
				$span = & $this->grid_dom->createElement("span", " ($val%)");
				$this->grid_dom_index['range_values'][$this->current_cell_id]->insertBefore($span, $this->grid_dom_index['numend'][$this->current_cell_id]);

				// list range box
				$span1 = & $this->list_dom->createElement("span", " ($val%)");
				$this->list_dom_index['div_cells'][$this->current_cell_id]->insertBefore($span1, $this->list_dom_index['numstart'][$this->current_cell_id]->nextSibling);

			break;
			case "percentagemax":
				if($this->init["type"] !== "PERCENTAGE_RANGE") break;
                if($this->list_dom_index['numpoints'][$this->current_cell_id]) break;
				$perc = ($params['value']/100) * $this->row_percentage;
				$val = (int) round((float)$perc, PHP_ROUND_HALF_DOWN);

				// grid input range box
				$span = & $this->grid_dom->createElement("span", " ($val%)");

				$this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($span);

				$input = & $this->grid_dom->createElement("input");
				$input->setAttribute("type", "text");
				$input->setAttribute("size", "3");
				$input->setAttribute("class", "grade_input");

				$min = $this->grid_dom_index['numstartval'][$this->current_cell_id];
				$max = $this->grid_dom_index['numendval'][$this->current_cell_id];

                 if($max > 0) {
				    	 			$input->setAttribute("data-range", '{"min" : "'.(int)$min.'", "max" : "'.(int)$max.'" }');
                } else {
                    $max = $this->grid_dom_index['numpointsval'][$this->current_cell_id];
                    $input->setAttribute("data-range", '{"min" : "-1", "max" : "'.(int)$max.'" }');
                }

				//$input->setAttribute("data-range", '{"min" : "'.(int)$min.'", "max" : "'.(int)$max.'" }');

				$this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($input);

				// list input range box
				$span1 = & $this->list_dom->createElement("span", " ($val%)");
				$this->list_dom_index['div_cells'][$this->current_cell_id]->appendChild($span1);

				$input1 = & $this->list_dom->createElement("input");
				$input1->setAttribute("type", "text");
				$input1->setAttribute("size", "3");
				$input1->setAttribute("class", "grade_input");

				$min = $this->list_dom_index['numstartval'][$this->current_cell_id];
				$max = $this->list_dom_index['numendval'][$this->current_cell_id];

                if($max > 0) {
				    $input1->setAttribute("data-range", '{"min" : "'.(int)$min.'", "max" : "'.(int)$max.'" }');
                } else {
                    $max = $this->list_dom_index['numpointsval'][$this->current_cell_id];
                    $input1->setAttribute("data-range", '{"min" : "-1", "max" : "'.(int)$max.'" }');
                }

				$this->list_dom_index['div_cells'][$this->current_cell_id]->appendChild($input1);

			break;
			case "percentage":
				if($this->init["type"] !== "PERCENTAGE") break; // @TODO allow percentage import

				// row depth
				if($depth == 2) {
					$this->row_percentage = $params['value']; // row weight
					$this->pointer['row_weights'][] = (float)$params['value'];
					// set weights in header
					if(isset($this->last_row_header)) {
						$p = & $this->grid_dom->createElement("p");
						$p->appendChild($this->grid_dom->createTextNode(" Weight: ".$this->row_percentage."%"));
						$this->last_row_header->appendChild($p);

						unset($this->last_row_header);
					}

					if(isset($this->last_div_header)) {
						$p = & $this->list_dom->createElement("p");
						$p->appendChild($this->list_dom->createTextNode(" Weight: ".$this->row_percentage."%"));
						$this->last_div_header->appendChild($p);

						unset($this->last_div_header);
					}
				}

				// column depth
				if($depth == 4) {
					$perc = $this->init["maxvalue"] * ((($params["value"] / 100) * $this->row_percentage) / 100);
					$this->pointer['col_scores'][] = (float)$params['value'];

					$str_perc = abs($params["value"])."%";
					$text_node = & $this->grid_dom->createTextNode($str_perc);
					$this->grid_dom_index['percentageval'][$this->current_cell_id] = $perc;

	        $input_t = & $this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($this->grid_dom->createElement("input"));
	      	$input_t->setAttribute("type", "radio");
	        $input_t->setAttribute("value", $perc);
	        $input_t->setAttribute("class", "grade_input");
	        $input_t->setAttribute("name", "_radio_".$this->current_row_id);

					$num_points = & $this->grid_dom_index['range_values'][$this->current_cell_id]->appendChild($text_node);

					if(!isset($this->grid_dom_index['percentage'])) {
						$this->grid_dom_index['percentage'] = array();
					}

					$this->grid_dom_index['percentage'][$this->current_cell_id] = & $num_points;

					// list start value
					$text_node = & $this->list_dom->createTextNode($str_perc);
					$this->list_dom_index['percentageval'][$this->current_cell_id] = $perc;

					$num_points = & $this->list_dom_index['div_cells'][$this->current_cell_id]->appendChild($text_node);

					// moved from line 295
					$input = &$this->list_div->appendChild($this->list_dom->createElement("input"));

					$cell_id = "cell_".$this->current_cell_id;
					$input->setAttribute("class", "rubricCellRadio");
					$input->setAttribute("id", $cell_id);
					$input->setAttribute("type", "radio");
					$input->setAttribute("value", $perc);
					$input->setAttribute("name", "radio_".$this->current_row_id);

					if(!isset($this->list_dom_index['percentage'])) {
						$this->list_dom_index['percentage'] = array();
					}

					$this->list_dom_index['percentage'][$this->current_cell_id] = & $num_points;
				}
			break;
			case "position":
				if($depth == 2) {
					if((int) $params['value'] > $this->row_count) {
						$this->row_count = (int)$params['value'];
					}
				} else if ($depth == 3) {
					if((int) $params['value'] > $this->col_count) {
						$this->col_count = (int)$params['value'];
					}
				}
			break;
		}
	}

	public function walkDocument($doc, $p = '') {

		$pre = "&rarr;".$p;

		echo "<pre>";
		for( $doc->rewind(); $doc->valid(); $doc->next() ) {

			$atts = "";

			foreach($doc->current()->attributes() as $n => $att) {
					$atts .= (string)$n ." = ".$att."<br>";
			}

			echo $pre." ".$doc->current()->getName()." Attributes: $atts</br>";

				foreach($doc->getChildren() as $name => $data) {
					$atts = "";

					foreach($data->attributes() as $n => $att) {
							$atts .= (string)$n ." = ".(string)$att."<br>";
					}

					echo $pre." ".$name . " - " . $data . " Attributes: $atts</br>";
					$this->walkDocument($data, $pre);
				}
		}
	}

	private function getNodeAttributeValue($_n) {

		for( $this->document->rewind(); $this->document->valid(); $this->document->next() ) {
			foreach($this->document->getChildren() as $name => $data) {
				if(strtoupper($name) == strtoupper($_n)) {
					foreach($data->attributes() as $n => $att) {
						if($n == 'value') {
							return (string)$att;
						}
					}
				}
			}
		}

		return FALSE;
	}


	private function simpleParse() {
		$data = file_get_contents($this->path.DIRECTORY_SEPARATOR.$this->filename);
		$this->document = simplexml_load_string($data, 'SimpleXMLIterator');
	}


}
