<?php

	// format
		# [kennzeichen] [Amtnummer] [Amt] [Betrag] [Kurzbezeichnung] [Langb.] [Jahr]
		# 03229, 62, "Amt fürs Scheißen", 2938.99, "Kurzzeug", "langzeug",2010

	// header
		header("Content-Type: text/html; charset=utf-8");

	// includes
		require_once("inc/openspending.inc.php");

	// load old data
		$file_2009 =  "data/mkk_xls_parser_2011_stand_data.txt";
		$infos_2009 = file_get_contents($file_2009);
		$infos_2009 = unserialize($infos_2009);

		$pdf_link_2009 = "http://www.mkk.de/cms/media/pdf/ihr-kreis_1/haushalt/Haushalt_2011.pdf";
		$years_2009 = array("2009");

	// load current data
		$file =  "data/mkk_xls_parser_2012_2013_entwurf_data.txt";
		$infos = file_get_contents($file);
		$infos = unserialize($infos);

		$pdf_link = "http://www.mkk.de/cms/media/pdf/ihr-kreis_1/haushalt/Haushaltsentwurf_20122013.pdf";
		$years = array("2012", "2011", "2010");

	// basedata
		$basedata = array(
			"default" => array(
				"infos"	=> $infos,
				"link"	=> $pdf_link,
				"years"	=> $years,
			),
			"2009" => array(
				"infos"	=> $infos_2009,
				"link"	=> $pdf_link_2009,
				"years"	=> $years_2009,
			),
		);

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
					"dezernat-desc",
					"dezernat",

					"fachbereich-id",
					"fachbereich-color",
					"fachbereich-desc",
					"fachbereich",

					"produkt-id",
					"produkt-color",
					"produkt-desc",
					"produkt",

					"konto-id",
					"konto-color",
					"konto-desc",
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

		foreach($basedata as $baseid => $base) {
			$infos = $base["infos"];
			$years = $base["years"];
			$link = $base["link"];

			foreach($infos as $did => $data) {
				foreach($data["data"] as $fid => $data2) {
					foreach ($data2["data"] as $pid => $data3) {
						foreach ($data3["data"] as $kid => $info) {
							foreach ($years as $year) {

								if (isset($info["data"][$year])) {
									$uid = md5(uniqid(true));									// must be unique
									$csv .= csvText($uid).
												$delimiter.
											csvInt($year).
												$delimiter.

											csvText(str_pad($did, 2, 0, STR_PAD_LEFT)).
												$delimiter.
											csvColor($color_1).
												$delimiter.
											csvText("Fehlende Beschreibung: Dezernat").
												$delimiter.
											csvText($data["text"]).
												$delimiter.

											csvText(str_pad($fid, 2, 0, STR_PAD_LEFT)).
												$delimiter.
											csvColor($color_2).
												$delimiter.
											csvText("Fehlende Beschreibung: Fachbereich").
												$delimiter.
											csvText($data2["text"]).
												$delimiter.

											csvText(str_pad($pid, 2, 0, STR_PAD_LEFT)).
												$delimiter.
											csvColor($color_3).
												$delimiter.
											csvText("Fehlende Beschreibung: Produkt").
												$delimiter.
											csvText($data3["text"]).
												$delimiter.

											csvText($kid).
												$delimiter.
											csvColor($color_4).
												$delimiter.
											csvText("Link zur Seite im <a href='".$link."#page=".$page."'>Haushalts-PDF</a> (ab Seite ".$page.")").
												$delimiter.
											csvText($info["short"]).
												$delimiter.

											csvFloat($info["data"][$year]).
												$delimiter;
									csvEndOfLine($csv);
								}
/*
								echo $csv."\n";
								die();
*/
							}
/*
							echo $csv."\n";
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
		}


	// write csv
		$file = "data/mkk_data.csv";
		file_put_contents($file, utf8_encode($csv));
		#die();

	// generate dataset json
		$json = array(
					"dezernat" => array(
						"attributes" => array(
							"color" => array(
								"column"	=> "dezernat-color",			// column name from csv
								"datatype"	=> "string",
							),
							"description" => array(
								"column"	=> "dezernat-desc",				// column name from csv
								"datatype"	=> "string",
							),
							"dezernat-id" => array(
								"column"	=> "dezernat-id",				// column name from csv
								"datatype"	=> "string",
							),
							"name" => array(
								"column"	=> "dezernat",					// column name from csv
								"datatype"	=> "id",
							),
							"label" => array(
								"column"	=> "dezernat",					// column name from csv
								"datatype"	=> "string",
							),
						),
						"type"				=> "compound",
						"description"		=> "Dezernat",
						"label"				=> "Dezernat",
						"taxonomy"			=> "mkk-level-1",
						"facet"				=> true,
						"dimension"			=> "dezernat",
					),

					"fachbereich" => array(
						"attributes" => array(
							"color" => array(
								"column"	=> "fachbereich-color",			// column name from csv
								"datatype"	=> "string",
							),
							"description" => array(
								"column"	=> "fachbereich-desc",			// column name from csv
								"datatype"	=> "string",
							),
							"fachbereich-id" => array(
								"column"	=> "fachbereich-id",			// column name from csv
								"datatype"	=> "string",
							),
							"name" => array(
								"column"	=> "fachbereich",				// column name from csv
								"datatype"	=> "id",
							),
							"label" => array(
								"column"	=> "fachbereich",				// column name from csv
								"datatype"	=> "string",
							),
						),
						"type"				=> "compound",
						"description"		=> "Fachbereich",
						"label"				=> "Fachbereich",
						"taxonomy"			=> "mkk-level-2",
						"facet"				=> true,
						"dimension"			=> "fachbereich",
					),

					"produkt" => array(
						"attributes" => array(
							"color" => array(
								"column"	=> "produkt-color",				// column name from csv
								"datatype"	=> "string",
							),
							"description" => array(
								"column"	=> "produkt-desc",				// column name from csv
								"datatype"	=> "string",
							),
							"produkt-id" => array(
								"column"	=> "produkt-id",				// column name from csv
								"datatype"	=> "string",
							),
							"name" => array(
								"column"	=> "produkt",					// column name from csv
								"datatype"	=> "id",
							),
							"label" => array(
								"column"	=> "produkt",					// column name from csv
								"datatype"	=> "string",
							),
						),
						"type"				=> "compound",
						"description"		=> "Produkt",
						"label"				=> "Produkt",
						"taxonomy"			=> "mkk-level-3",
						"facet"				=> true,
						"dimension"			=> "produkt",
					),

					"konto" => array(
						"attributes" => array(
							"color" => array(
								"column"	=> "konto-color",				// column name from csv
								"datatype"	=> "string",
							),
							"description" => array(
								"column"	=> "konto-desc",				// column name from csv
								"datatype"	=> "string",
							),
							"konto-id" => array(
								"column"	=> "konto-id",					// column name from csv
								"datatype"	=> "string",
							),
							"name" => array(
								"column"	=> "konto",						// column name from csv
								"datatype"	=> "id",
							),
							"label" => array(
								"column"	=> "konto",						// column name from csv
								"datatype"	=> "string",
							),
						),
						"type"				=> "compound",
						"description"		=> "Konto",
						"label"				=> "Konto",
						"taxonomy"			=> "mkk-level-4",
						"facet"				=> true,
						"dimension"			=> "konto",
					),

					"time" => array(
						"type"				=> "date",
						"description"		=> null,
						"label"				=> "Jahr",
						"datatype"			=> "date",
						"column"			=> "date",
						"dimension"			=> "time",
					),

					"amount" => array(
						"type"				=> "measure",
						"description"		=> "Betrag für angegebenes Jahr",
						"label"				=> "Betrag",
						"datatype"			=> "float",
						"column"			=> "budget",
						"dimension"			=> "amount",
					),

					"uniqueid" => array(
						"type"				=> "attribute",
						"description"		=> "Unique transaction ID",
						"label"				=> "Unique ID",
						"datatype"			=> "string",
						"column"			=> "id",
						"dimension"			=> "uniqueid",
						"key"				=> true,
					),
		);

	// readablity
		$json = json_encode($json);
		$json = jsonToReadable($json);

	// write json
		$file2 = str_replace(".csv", "_dataset.json", $file);
		file_put_contents($file2, $json);


	// generate view json
		$json = array(
					array(
						"name"				=> "default",
						"entity"			=> "dataset",
						"label"				=> "Aufgeteilt nach Dezernaten",
						"dimension"			=> "dataset",
						"drilldown"			=> "dezernat",
						"cuts"				=> array(),
					),
					array(
						"name"				=> "default",
						"entity"			=> "classifier",
						"label"				=> "Aufgeteilt nach Fachbereichen",
						"dimension"			=> "dezernat",
						"drilldown"			=> "fachbereich",
						"cuts"				=> array(),
					),
					array(
						"name"				=> "default",
						"entity"			=> "classifier",
						"label"				=> "Aufgeteilt nach Produkten",
						"dimension"			=> "fachbereich",
						"drilldown"			=> "produkt",
						"cuts"				=> array(),
					),
					array(
						"name"				=> "default",
						"entity"			=> "classifier",
						"label"				=> "Aufgeteilt nach Konten",
						"dimension"			=> "produkt",
						"drilldown"			=> "konto",
						"cuts"				=> array(),
					),
		);

	// readablity
		$json = json_encode($json);
		$json = jsonToReadable($json);

	// write json
		$file2 = str_replace(".csv", "_view.json", $file);
		file_put_contents($file2, $json);

?>
