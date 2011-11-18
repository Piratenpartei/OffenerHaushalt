<?php

	// include
		require_once("Image/Color.php");

	// load
		$file = "data/_kuler_color_scheme.txt";
		$colors = file_get_contents($file);
		$colors = unserialize($colors);
		#print_r($colors);

	// check references
		$cycle = 0;
		$oneway = 0;
		$oneways = array();
		foreach($colors as $color => $data) {
			foreach($data as $color2) {
				if (isset($colors[$color2])) {
					$cycle++;
				} else {
					$oneway++;
					array_push($oneways, $color2);
				}
			}
		}

	// debug
		#print_r($oneways);
		echo $cycle." / ".$oneway." | ".sizeof($colors)."\n";

	// similiar colors
		$ic = new Image_Color();
		$alt_colors = array();
		foreach($oneways as $color) {
			#echo $color."\n";

			$similiars = array(
							"1"	=>	array(),
							"2"	=>	array(),
							"3"	=>	array(),
			);
			foreach($colors as $color2 => $data) {
				$diff = levenshtein($color, $color2);
				if ($diff == 1)
					array_push($similiars["1"], $color2);
				if ($diff == 2)
					array_push($similiars["2"], $color2);
				if ($diff == 3)
					array_push($similiars["3"], $color2);
			}
			#print_r($similiars);

			$testcolors = array_merge($similiars["1"], $similiars["2"], $similiars["3"]);
			#print_r($testcolors);

			$color_rgb = $ic->color2RGB("#".$color);
			#print_r($color_rgb);

			$checkdiff = -1;
			$checkcolor = "";
			foreach($testcolors as $testcolor) {
				$testcolor_rgb = $ic->color2RGB("#".$testcolor);
				#print_r($testcolor_rgb);

				$diff = (
							colorDiff($color_rgb[0], $testcolor_rgb[0])
							*
							colorDiff($color_rgb[1], $testcolor_rgb[1])
							*
							colorDiff($color_rgb[2], $testcolor_rgb[2])
				);
				if ($checkdiff == -1) {
					$checkdiff = $diff;
					$checkcolor = $testcolor;
				}
				if ($diff < $checkdiff) {
					$checkdiff = $diff;
					$checkcolor = $testcolor;
				}
				#echo $diff."\n";
			}
			#echo $checkcolor."\n";
			$alt_colors[(string)$color] = (string)$checkcolor;
			#die();
		}
		#print_r($alt_colors);
		ksort($alt_colors);
		echo sizeof($alt_colors)."\n";

	// write
		$file = "data/_kuler_color_alt_scheme.txt";
		file_put_contents($file, serialize($alt_colors));
		file_put_contents(str_replace(".txt", "_raw.txt", $file), var_export($alt_colors, true));



	// helper function
		function colorDiff($c1, $c2) {
			$d = abs($c1 - $c2);
			if ($d == 0)
				$d = 1;
			return $d;
		}

?>
