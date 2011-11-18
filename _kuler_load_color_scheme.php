<?php

	// get api key
		$api_ini_file = "_kuler_load_color_scheme.ini";
		if (!is_file($api_ini_file))
			die("no api key file found");
		$kuler = parse_ini_file($api_ini_file);

	// include
		require_once "XML/RSS.php";

	// max items
		$max_items = 100;

	// load colors
		$colors = array();

		for ($start_idx = 0; $start_idx<30; $start_idx++) {
			$kuler_url = "https://kuler-api.adobe.com/rss/get.cfm?listtype=rating&startIndex=".($start_idx*$max_items)."&itemsPerPage=".$max_items."&key=".$kuler["api_key"];

			$rss =& new XML_RSS($kuler_url);
			$rss->parse();
			foreach ($rss->getItems() as $item) {
				$desc = $item["description"];
				$desc = explode("Hex:", $desc);
				$desc = trim($desc[1]);
				$desc = str_replace(" ", "", $desc);
				$desc = explode(",", $desc);
				#print_r($desc);
				for ($i=0; $i<4; $i++) {
					if (checkColor($desc[$i]) && checkColor($desc[($i+1)])) {
						if (!isset($colors[$desc[$i]]))
							$colors[$desc[$i]] = array();
						array_push($colors[$desc[$i]], $desc[($i+1)]);
					}
				}
			}
			echo $start_idx." ".sizeof($rss->getItems())."\n";
			#die();
		}

	// debug
		#print_r($colors);

	// save
		$file = "data/_kuler_color_scheme.txt";
		file_put_contents($file, serialize($colors));
		file_put_contents(str_replace(".txt", "_raw.txt", $file), var_export($colors, true));



	// helper functions
		function checkColor($color) {
			$e = substr_count($color, "E");
			$f = substr_count($color, "F");

			if ($e+$f >= 3)
				return false;
			else
				return true;
		}

?>
