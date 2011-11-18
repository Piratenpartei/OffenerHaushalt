<?php

	// includes
		require_once 'lib/phpExcelReader/Excel/reader.php';

	// excel reader
		$data = new Spreadsheet_Excel_Reader();
		$data->setUTFEncoder('iconv');
		#$data->setOutputEncoding('UTF-8');
		$data->setOutputEncoding('ISO-8859-1');

	// open file
		$data->read('pdfs/MKK/Haushalt_2011_open.xls');

	// error reporting
		error_reporting(E_ALL ^ E_NOTICE);

	// print first info
		echo sizeof($data->sheets)." Pages\n\n";

	// set filename
		$file = basename(__FILE__);
		$file = str_replace(".php", "_", $file);

	// run
		$infos = array();
		foreach($data->sheets as $side => $page) {
			for ($i=1; $i<=$page['numRows']; $i++) {
				// find basis info
					if (
						$page["cells"][$i][1] == "Dezernat"
						&&
						$page["cells"][($i+1)][1] == "Fachbereich"
						&&
						$page["cells"][($i+2)][1] == "Produkt"
					) {
						// page
							echo "\nPage ".$side."\n";

						// basis info
							$dezernat_id = 0;
							$dezernat_name = "";
							#print_r($page["cells"][$i]);
							foreach ($page["cells"][$i] as $j => $cell) {
								#echo $j." ".$cell."\n";
								preg_match("/([0-9])+/", $cell, $regs);
								if (isset($regs[1])) {
									#print_r($regs);
									$dezernat_id = $regs[0];
									$dezernat_name = "Dezernat ".intval($dezernat_id);
								}
							}

							$fachbereich_id = 0;
							$fachbereich_name = "";
							#print_r($page["cells"][($i+1)]);
							foreach ($page["cells"][($i+1)] as $j => $cell) {
								#echo $j." ".$cell."\n";
								preg_match("/([0-9])+/", $cell, $regs);
								if (isset($regs[1])) {
									#print_r($regs);
									$fachbereich_id = substr($regs[0], 2);
								}
								if ($j > 1) {
									preg_match("/(\D)+/", $cell, $regs);
									if (isset($regs[1])) {
										#print_r($regs);
										$t = trim($regs[0]);
										if (!empty($t))
											$fachbereich_name = $t;
									}
								}
							}

							$produkt_id = 0;
							$produkt_name = "";
							#print_r($page["cells"][($i+2)]);
							foreach ($page["cells"][($i+2)] as $j => $cell) {
								#echo $j." ".$cell."\n";
								preg_match("/^([0-9])+/", $cell, $regs);
								if (isset($regs[1])) {
									#print_r($regs);
									$produkt_id = substr($regs[0], 2);
								}
								if ($j > 1) {
									preg_match("/(\D)+/", trim(str_replace("§", "", $cell)), $regs);
									if (isset($regs[1])) {
										#print_r($regs);
										$t = trim($regs[0]);
										if (!empty($t))
											$produkt_name = $t;
									}
								}
							}

						// debug
							echo $dezernat_id." / ".$dezernat_name."\n";
							echo $fachbereich_id." / ".$fachbereich_name."\n";
							echo $produkt_id." / ".$produkt_name."\n";

						// check
							if (strlen($produkt_name) < 3) {
								print_R($page["cells"][($i+2)]);
								die();
							}

						// find amount value start
							$start = false;
							$stop = false;
							$amounts = array();
							checkPage($page, $start, $stop, $amounts, $side);

						// 2nd page?
							if (!$stop) {
								#echo "2nd page?\n";
								if (isset($data->sheets[($side+1)])) {
									#echo "2nd page!\n";
									$page = $data->sheets[($side+1)];
									checkPage($page, $start, $stop, $amounts, $side);
								}
							}

							#print_r($amounts);
							if (empty($amounts)) {
								echo $side."\n";
								print_r($page['cells'][20]);
								print_r($page['cells'][21]);
								die();
							}
							echo sizeof($amounts)."\n";
							#die();

						// build structure
							if (!isset($infos[$dezernat_id])) {
								$infos[$dezernat_id] = array(
														"text"	=>	$dezernat_name,
														"data"	=>	array(),
								);
							}

							if (!isset($infos[$dezernat_id]["data"][$fachbereich_id])) {
								$infos[$dezernat_id]["data"][$fachbereich_id] = array(
																				"text"	=>	$fachbereich_name,
																				"data"	=>	array(),
								);
							}

							if (!isset($infos[$dezernat_id]["data"][$fachbereich_id]["data"][$produkt_id])) {
								$infos[$dezernat_id]["data"][$fachbereich_id]["data"][$produkt_id] = array(
																										"text"	=>	$produkt_name,
																										"data"	=>	array(),
								);
							}

							foreach($amounts as $id => $amount) {
								$infos[$dezernat_id]["data"][$fachbereich_id]["data"][$produkt_id]["data"][$id] = $amount;
							}
					}
			}
		}

	// write files
		file_put_contents("data/".$file."data.txt", serialize($infos));
		file_put_contents("data/".$file."data_raw.txt", var_export($infos, true));



	// functions ##########################################################################################################################
		function createNumber($text) {
			$text = str_replace(".", "", $text);
			$text = str_replace(",", ".", $text);
			$text = trim($text);

			return floatval($text);
		}

		function checkPage(&$page, &$start, &$stop, &$amounts, $side) {
			for ($ii=1; $ii<=$page['numRows']; $ii++) {
				foreach ($page['cells'][$ii] as $jj => $cell) {
					#echo $cell."\n";
					if (strpos($cell, "Ordentliche Aufwendun") !== false) {
						#print_r($page['cells'][$ii]);
						$start = true;
					}
					if (strpos($cell, "Summe der ordentlichen Aufwendungen") !== false) {
						#print_r($page['cells'][$ii]);
						$stop = true;
					}
				}
				if ($start && !$stop) {
					$row = checkRow($page['cells'], $ii, $side);
					if (is_array($row))
						$amounts[$row["id"]] = $row["data"];
					}
				}
		}

		function checkRow($cell, $idx, $side) {
			$row = $cell[$idx];
			foreach($row as $k => $v) {
				$row[$k] = trim($v);
			}
			#print_r($row);

			$years = array("2011", "2010", "2009");

			$ret = array();
			if (
				empty($row[1])
				&&
				is_numeric($row[2])
				&&
				!empty($row[3])
			) {
				$ret["id"] = trim($row[2]);
				$ret["data"] = array(
								"page"	=>	$side,
								"data"	=>	array(
												"2009"	=>	0,
												"2010"	=>	0,
												"2011"	=>	0,
											),
								"short"	=>	$row[3],
				);

				#print_r($row);
				$i = 0;
				for ($j = 3; $j<=sizeof($row); $j++) {
					if (strpos($row[$j], ",") !== false) {
						$row[$j] = str_replace(".", "", $row[$j]);
						$row[$j] = str_replace(",", ".", $row[$j]);
						if (is_numeric($row[$j])) {
							$ret["data"]["data"][$years[$i]] = $row[$j];
							$i++;
						}
					}
				}
				#print_r($ret);
				#die("stop");

				if (isset($cell[($idx+1)])) {
					$row2 = $cell[($idx+1)];
					foreach($row2 as $k => $v) {
						$row2[$k] = trim($v);
					}
					#print_r($row2);

					if (
						empty($row2[1])
						&&
						empty($row2[2])
						&&
						!empty($row2[3])
						&&
						empty($row2[4])
						&&
						empty($row2[5])
						&&
						empty($row2[6])
					) {
						$ret["data"]["short"] .= " ".$row2[3];
					}
				}

				#print_r($ret);
				#die();

				return $ret;
			} else {
				return false;
			}
		}

?>
