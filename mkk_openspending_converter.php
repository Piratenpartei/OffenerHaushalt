<?php

	// format
		# [kennzeichen] [Amtnummer] [Amt] [Betrag] [Kurzbezeichnung] [Langb.] [Jahr]
		# 03229, 62, "Amt fürs Scheißen", 2938.99, "Kurzzeug", "langzeug",2010

	// header
		header("Content-Type: text/html; charset=utf-8");

	// includes
		require_once("inc/openspending.inc.php");

	// load data
/*
		$file =  "data/mkk_xls_parser_2012_entwurf_data.txt";
		$infos_2012 = file_get_contents($file);
		$infos_2012 = unserialize($infos_2012);
*/

		$file =  "data/mkk_xls_parser_2011_stand_data.txt";
		$infos = file_get_contents($file);
		$infos = unserialize($infos);

	// years
		$years = array("2009", "2010", "2011");
/*
		$years_2012 = array("2010", "2011", "2012");
*/

	// load colors
		$colors = unserialize(file_get_contents("data/_kuler_color_scheme.txt"));
		$colors_keys = array_keys($colors);
		$alt_colors = unserialize(file_get_contents("data/_kuler_color_alt_scheme.txt"));

	// cols
		$cols = array(
					"id",
					"date",

					"dezernat-id",
					"dezernat-color",
					"dezernat",

					"fachbereich-id",
					"fachbereich-color",
					"fachbereich",

					"produkt-id",
					"produkt-color",
					"produkt",

					"konto-id",
					"konto-color",
					"konto",

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
		$color_4 = findNextColor($color_3, $colors, $alt_colors);
		foreach($infos as $did => $data) {
			foreach($data["data"] as $fid => $data2) {
				foreach ($data2["data"] as $pid => $data3) {
					foreach ($data3["data"] as $kid => $info) {
						foreach ($years as $year) {
							$uid = md5(uniqid(true)); /* must be unique */
							$csv .= csvText($uid).
										$delimiter.
									csvInt($year).
										$delimiter.

									csvText(str_pad($did, 2, 0, STR_PAD_LEFT)).
										$delimiter.
									csvColor($color_1).
										$delimiter.
									csvText($data["text"]).
										$delimiter.

									csvText(str_pad($fid, 2, 0, STR_PAD_LEFT)).
										$delimiter.
									csvColor($color_2).
										$delimiter.
									csvText($data2["text"]).
										$delimiter.

									csvText(str_pad($pid, 2, 0, STR_PAD_LEFT)).
										$delimiter.
									csvColor($color_3).
										$delimiter.
									csvText($data3["text"]).
										$delimiter.

									csvText($kid).
										$delimiter.
									csvColor($color_4).
										$delimiter.
									csvText($info["short"]).
										$delimiter.

									csvFloat($info["data"][$year]).
										$delimiter;
							csvEndOfLine($csv);
						}
/*
						foreach ($years_2012 as $year) {
							$uid = md5(uniqid(true)); /* must be unique */
/*
							$csv .= csvText($uid).
										$delimiter.
									csvInt($year).
										$delimiter.

									csvText(str_pad($did, 2, 0, STR_PAD_LEFT)).
										$delimiter.
									csvColor($color).
										$delimiter.
									csvText($data["text"]).
										$delimiter.

									csvText(str_pad($fid, 2, 0, STR_PAD_LEFT)).
										$delimiter.
									csvColor($subcolor).
										$delimiter.
									csvText($data2["text"]).
										$delimiter.

									csvText(str_pad($pid, 2, 0, STR_PAD_LEFT)).
										$delimiter.
									csvColor($subcolor).
										$delimiter.
									csvText($data3["text"]).
										$delimiter.

									csvText($kid).
										$delimiter.
									csvColor($subcolor).
										$delimiter.
									csvText($info["short"]).
										$delimiter.

									csvFloat($infos_2012[$did]["data"][$fid]["data"][$pid]["data"][$kid]["data"][$year]).
										$delimiter;
							csvEndOfLine($csv);
						}
*/
/*
						echo $csv;
						die();
*/
						$color_4 = findNextColor($color_4, $colors, $alt_colors);
					}
					$color_3 = findNextColor($color_3, $colors, $alt_colors);
				}
				$color_2 = findNextColor($color_2, $colors, $alt_colors);
			}
			$color_idx++;
			$color_1 = $colors[$colors_keys[$color_idx]][0];
		}

	// write csv
		$file = "data/mkk_data.csv";
		file_put_contents($file, utf8_encode($csv));
		#die();


	// generate json
		$json = array(
					"dataset" => array(
						"name"					=> "main-kinzig-kreis_goverment_budget",
						"label"					=> "Haushalt Main-Kinzig-Kreis",
						"description"			=> "Haushalt Main-Kinzig-Kreis<br><br><a href='https://kuler.adobe.com'><img src='https://www.piratenpartei-hessen.de/sites/piratenpartei-hessen.de/files/images/ku_36pxWtext.png' border='0'></a>",
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

						"dezernat" => array(
							"fields" => array(
								array(
									"column"	=> "dezernat",
									"datatype"	=> "string",
									"name"		=> "label",
								),
								array(
									"column"	=> "dezernat-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "dezernat-color",
									"datatype"	=> "string",
									"name"		=> "color",
								),
							),
							"type"				=> "classifier",
							"description"		=> "Dezernat",
							"label"				=> "Dezernat",
							"taxonomy"			=> "mkk-level-1",
							"facet"				=> true,
						),

						"fachbereich" => array(
							"fields" => array(
								array(
									"column"	=> "fachbereich",
									"datatype"	=> "string",
									"name"		=> "label",
								),
								array(
									"column"	=> "fachbereich-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "fachbereich-color",
									"datatype"	=> "string",
									"name"		=> "color",
								),
							),
							"type"				=> "classifier",
							"description"		=> "Fachbereich",
							"label"				=> "Fachbereich",
							"taxonomy"			=> "mkk-level-2",
							"facet"				=> true,
						),

						"produkt" => array(
							"fields" => array(
								array(
									"column"	=> "produkt",
									"datatype"	=> "string",
									"name"		=> "label",
								),
								array(
									"column"	=> "produkt-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "produkt-color",
									"datatype"	=> "string",
									"name"		=> "color",
								),
							),
							"type"				=> "classifier",
							"description"		=> "Produkt",
							"label"				=> "Produkt",
							"taxonomy"			=> "mkk-level-3",
							"facet"				=> true,
						),

						"konto" => array(
							"fields" => array(
								array(
									"column"	=> "konto",
									"datatype"	=> "string",
									"name"		=> "label",
								),
								array(
									"column"	=> "konto-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "konto-color",
									"datatype"	=> "string",
									"name"		=> "color",
								),
							),
							"type"				=> "classifier",
							"description"		=> "Konto",
							"label"				=> "Konto",
							"taxonomy"			=> "mkk-level-4",
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
									"column"	=> "dezernat-id",
									"datatype"	=> "string",
									"name"		=> "id",
								),
								array(
									"column"	=> "dezernat",
									"datatype"	=> "string",
									"name"		=> "label",
								),
							),
							"type"				=> "entity",
							"description"		=> "",
							"label"				=> "Dezernate des Main-Kinzig-Kreis",
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
							"label"				=> "Aufgeteilt nach Dezernaten",
							"dimension"			=> "dataset",
							"breakdown"			=> "dezernat",
							"filters" => array(
								"name"			=> "main-kinzig-kreis_goverment_budget",
							),
						),
						array(
							"name"				=> "default",
							"entity"			=> "classifier",
							"label"				=> "Aufgeteilt nach Fachbereichen",
							"dimension"			=> "dezernat",
							"breakdown"			=> "fachbereich",
							"filters" => array(
								"taxonomy"		=> "mkk-level-1",
							),
						),
						array(
							"name"				=> "default",
							"entity"			=> "classifier",
							"label"				=> "Aufgeteilt nach Produkten",
							"dimension"			=> "fachbereich",
							"breakdown"			=> "produkt",
							"filters" => array(
								"taxonomy"		=> "mkk-level-2",
							),
						),
						array(
							"name"				=> "default",
							"entity"			=> "classifier",
							"label"				=> "Aufgeteilt nach Konten",
							"dimension"			=> "produkt",
							"breakdown"			=> "konto",
							"filters" => array(
								"taxonomy"		=> "mkk-level-3",
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
