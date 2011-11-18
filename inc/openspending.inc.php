<?php

	// functions ##########################################################################################################################
		function csvText($text) {
			$text = str_replace("\"", "'", $text);
			return '"'.$text.'"';
		}

		function csvFloat($float) {
			return sprintf("%0.2f", $float);
		}

		function csvInt($int) {
			return $int;
		}

		function csvColor($color) {
			return "#".$color;
		}

		function csvEndOfLine(&$csv) {
			$csv = trim($csv);
			$csv = substr($csv, 0, -1);
			$csv .= "\n";
		}

		function findNextColor($color, $colors, $alt_colors) {
			if (isset($colors[$color]))
				return $colors[$color][0];
			else
				return $colors[$alt_colors[$color]][0];
		}

		function jsonToReadable($jsonString) {
			$tabcount = 0;
			$result = '';
			$inquote = false;

			$tab = "   ";
			$newline = "\n";

			for($i = 0; $i < strlen( $jsonString); $i++) {
				$char = $jsonString[ $i];

				if ($char == '"' && $jsonString[ $i-1] != '\\') $inquote = !$inquote;

				if ($inquote) {
					$result .= $char;
					continue;
				}

				switch($char) {
					case '{':
						if ($i) $result .= $newline;
						$result .= str_repeat($tab, $tabcount).$char.$newline.str_repeat( $tab, ++$tabcount);
						break;
					case '}':
						$result .= $newline.str_repeat( $tab, --$tabcount).$char;
						break;
					case ',':
						$result .= $char;
						if ($jsonString[ $i+1] != '{') $result.=$newline.str_repeat($tab, $tabcount);
						break;
					default:
						$result .= $char;
				}
			}
			return $result;
		}

?>
