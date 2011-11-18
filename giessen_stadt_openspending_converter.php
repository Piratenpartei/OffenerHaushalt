<?php

	// format
		# [kennzeichen] [Amtnummer] [Amt] [Betrag] [Kurzbezeichnung] [Langb.] [Jahr]
		# 03229, 62, "Amt fürs Scheißen", 2938.99, "Kurzzeug", "langzeug",2010

	// header
		header("Content-Type: text/html; charset=utf-8");

	// includes
		require_once("inc/openspending.inc.php");

	// load data
		$file =  "data/giessen_stadt_xls_parser_2012_entwurf_data_2012.txt";
		$infos_2012 = file_get_contents($file);
		$infos_2012 = unserialize($infos_2012);

		$file =  "data/giessen_stadt_xls_parser_2011_stand_data.txt";
		$infos = file_get_contents($file);
		$infos = unserialize($infos);

	// years
		$years = array("2009");
		$years_2012 = array("2010", "2011", "2012");

	// load colors
		$colors = unserialize(file_get_contents("data/_kuler_color_scheme.txt"));
		$colors_keys = array_keys($colors);
		$alt_colors = unserialize(file_get_contents("data/_kuler_color_alt_scheme.txt"));

	// cols
		$cols = array(
					"id",
					"date",

					"group-id",
					"group-color",
					"group",

					"amt-id",
					"amt-color",
					"amt",

					"product-id",
					"product-color",
					"product",

					"budget",
		);

	// convert to csv
		$csv = "";
		$delimiter = ",";

		foreach($cols as $col) {
			$csv .= csvText($col).
						$delimiter;
		}
		csvEndOfLine($csv);

		$color_idx = 0;
		$color_1 = $colors[$colors_keys[$color_idx]][0];
		$color_2 = findNextColor($color_1, $colors, $alt_colors);
		$color_3 = findNextColor($color_2, $colors, $alt_colors);
		foreach($infos as $gid => $data) {
			foreach($data["data"] as $oid => $data2) {
				foreach ($data2["data"] as $idx => $info) {
					foreach ($years as $year) {
						$uid = md5(uniqid(true)); /* must be unique */
						$csv .= csvText($uid).
									$delimiter.
								csvInt($year).
									$delimiter.

								csvText(str_pad($gid, 2, 0, STR_PAD_LEFT)).
									$delimiter.
								csvColor($color_1).
									$delimiter.
								csvText($data["text"]).
									$delimiter.

								csvText(str_pad($oid, 2, 0, STR_PAD_LEFT)).
									$delimiter.
								csvColor($color_2).
									$delimiter.
								csvText($data2["text"]).
									$delimiter.

								csvText($idx).
									$delimiter.
								csvColor($color_3).
									$delimiter.
								csvText($info["short"]).
									$delimiter.

								csvFloat($info["data"][$year]).
									$delimiter;
						csvEndOfLine($csv);
					}

					foreach ($years_2012 as $year) {
						$uid = md5(uniqid(true)); /* must be unique */
						$csv .= csvText($uid).
									$delimiter.
								csvInt($year).
									$delimiter.

								csvText(str_pad($gid, 2, 0, STR_PAD_LEFT)).
									$delimiter.
								csvColor($color_1).
									$delimiter.
								csvText($data["text"]).
									$delimiter.

								csvText(str_pad($oid, 2, 0, STR_PAD_LEFT)).
									$delimiter.
								csvColor($color_2).
									$delimiter.
								csvText($data2["text"]).
									$delimiter.

								csvText($idx).
									$delimiter.
								csvColor($color_3).
									$delimiter.
								csvText($info["short"]).
									$delimiter.

								csvFloat($infos_2012[$gid]["data"][$oid]["data"][$idx]["data"][$year]).
									$delimiter;
						csvEndOfLine($csv);
					}

/*
					echo $csv;
					die();
*/
					$color_3 = findNextColor($color_3, $colors, $alt_colors);
				}
				$color_2 = findNextColor($color_2, $colors, $alt_colors);
			}
			$color_idx++;
			$color_1 = $colors[$colors_keys[$color_idx]][0];
		}

	// write csv
		$file = "data/giessen_stadt_data.csv";
		file_put_contents($file, utf8_encode($csv));


	// generate json
		$json = array(
					"dataset" => array(
						"name"					=> "giessen_city_goverment_budget",
						"label"					=> "Haushalt Stadt Gießen",
						"description"			=> "Haushalt Stadt Gießen<br><br><a href='https://kuler.adobe.com'><img src='https://www.piratenpartei-hessen.de/sites/piratenpartei-hessen.de/files/images/ku_36pxWtext.png' border='0'></a>",
						"currency"				=> "EUR",
						"unique_keys"			=> 	array("id"),
						"temporal_granularity"	=>	"year",
					),

					"mapping" => array(

						"id" => array(
							"label"				=> "Unique ID",
							"description"		=> "Unique transaction ID",
							"column"			=> "id",
							"datatype"			=> "string",
							"type"				=> "value",
						),

						"group" => array(
							"fields" => array(
								array(
									"column"	=> "group",
									"datatype"	=> "string",
									"name"		=> "label",
								),
								array(
									"column"	=> "group-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "group-color",
									"datatype"	=> "string",
									"name"		=> "color",
								),
							),
							"type"				=> "classifier",
							"description"		=> "Gruppe",
							"label"				=> "Gruppe",
							"taxonomy"			=> "giessen-level-1",
							"facet"				=> true,
						),

						"amt" => array(
							"fields" => array(
								array(
									"column"	=> "amt",
									"datatype"	=> "string",
									"name"		=> "label",
								),
								array(
									"column"	=> "amt-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "amt-color",
									"datatype"	=> "string",
									"name"		=> "color",
								),
							),
							"type"				=> "classifier",
							"description"		=> "Amt",
							"label"				=> "Amt",
							"taxonomy"			=> "giessen-level-2",
							"facet"				=> true,
						),

						"product" => array(
							"fields" => array(
								array(
									"column"	=> "product",
									"datatype"	=> "string",
									"name"		=> "label",
								),
								array(
									"column"	=> "product-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "product-color",
									"datatype"	=> "string",
									"name"		=> "color",
								),
							),
							"type"				=> "classifier",
							"description"		=> "Produkt",
							"label"				=> "Produkt",
							"taxonomy"			=> "giessen-level-3",
							"facet"				=> true,
						),

/*
						"date" => array(
							"type"				=> "value",
							"description"		=> "Jahr",
							"label"				=> "Jahr",
							"datatype"			=> "date",
							"fields" => array(),
						),
*/
						"time" => array(
							"type"				=> "value",
							"label"				=> "Jahr",
							"datatype"			=> "date",
							"column"			=> "date",
						),

						"to" => array(
							"fields" => array(
								array(
									"column"	=> "",
									"datatype"	=> "constant",
									"constant"	=> "society",
									"name"		=> "name",
								),
								array(
									"column"	=> "",
									"datatype"	=> "constant",
									"constant"	=> "Gesellschaft",
									"name"		=> "label",
								),
							),
							"type"				=> "entity",
							"label"				=> "Empfänger",
						),

						"from" => array(
							"fields" => array(
								array(
									"column"	=> "amt-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "amt",
									"datatype"	=> "string",
									"name"		=> "label",
								),
							),
							"type"				=> "entity",
							"description"		=> "",
							"label"				=> "Ämter Stadt Gießen",
						),

						"amount" => array(
							"type"				=> "value",
							"description"		=> "Betrag für angegebenes Jahr",
							"label"				=> "Betrag",
							"datatype"			=> "float",
/*
							"fields" => array(),
*/
							"column"			=> "budget",
						),
					),

					"views" => array(
						array(
							"name"				=> "default",
							"entity"			=> "dataset",
							"label"				=> "Aufgeteilt nach Gruppen",
							"dimension"			=> "dataset",
							"breakdown"			=> "group",
							"filters" => array(
								"name"			=> "giessen_city_goverment_budget",
							),
						),
						array(
							"name"				=> "default",
							"entity"			=> "classifier",
							"label"				=> "Aufgeteilt nach Ämtern",
							"dimension"			=> "group",
							"breakdown"			=> "amt",
							"filters" => array(
								"taxonomy"		=> "giessen-level-1",
							),
						),
						array(
							"name"				=> "default",
							"entity"			=> "classifier",
							"label"				=> "Aufgeteilt nach Produkten",
							"dimension"			=> "amt",
							"breakdown"			=> "product",
							"filters" => array(
								"taxonomy"		=> "giessen-level-2",
							),
						),
					),
		);

	// years
		foreach ($years as $year) {
/*
			$date = array(
						"column"	=> "date_".$year,
						"datatype"	=> "date",
						"name"		=> "value",
			);
			array_push($json["mapping"]["date"]["fields"], $date);
*/

/*
			$date = array(
						"column"	=> "budget_".$year,
						"datatype"	=> "float",
						"name"		=> "value",
			);
			array_push($json["mapping"]["amount"]["fields"], $date);
*/
		}

	// readablity
		$json = json_encode($json);
		$json = jsonToReadable($json);

	// write json
		$file = str_replace(".csv", ".json", $file);
		file_put_contents($file, $json);

?>
