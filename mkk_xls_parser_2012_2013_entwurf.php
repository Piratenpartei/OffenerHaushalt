<?php

	// includes
		require_once 'lib/phpExcelReader/Excel/reader.php';

	// excel reader
		$data = new Spreadsheet_Excel_Reader();
		$data->setUTFEncoder('iconv');
		#$data->setOutputEncoding('UTF-8');
		$data->setOutputEncoding('ISO-8859-1');

	// open file
		echo "loading data\n";
		$data->read('pdfs/MKK/Haushaltsentwurf_2012_2013_open.xls');

	// error reporting
		error_reporting(E_ALL ^ E_NOTICE);

	// print first info
		echo sizeof($data->sheets)." Pages\n\n";

	// set filename
		$file = basename(__FILE__);
		$file = str_replace(".php", "_", $file);

	// nextpages
		echo "building nextpages\n";
		$nextpages = array();
		foreach($data->sheets as $side => $page) {
			if ($side > 1)
				$nextpages[($side-1)] = $page;
		}
		#print_r(array_keys($nextpages));
		#die();

	// run
		echo "start parsing\n";
		$infos = array();
		foreach($data->sheets as $side => $page) {
			for ($i=1; $i<=$page['numRows']; $i++) {
				// find basis info
					if (
						$page["cells"][$i][2] == "Dezernat"
						&&
						$page["cells"][($i+1)][2] == "Fachbereich"
						&&
						$page["cells"][($i+2)][2] == "Produkt"
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

									// fix dezernat id
									if (intval($dezernat_id) <= 0 || intval($dezernat_id) > 3) {
										$dezernat_id = substr($regs[0], 0, 2);
										$dezernat_name = "Dezernat ".intval($dezernat_id);
										echo "dezernat corrected\n";
									}
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
							$sums = array();
							checkPage($page, $start, $stop, $amounts, $sums, $side);

						// 2nd page?
							if (!$stop) {
								echo "2nd page!\n";
								checkPage($nextpages[$side], $start, $stop, $amounts, $sums, $side);

								if (!$stop) {
									echo "3rd page!\n";
									checkPage($nextpages[($side+1)], $start, $stop, $amounts, $sums, $side);
								}
							}

							#print_r($amounts);
							if (empty($amounts)) {
								echo "\n";
								print_r($page['cells']);

								echo "\n!!! EMPTY AMOUNTS ALERT !!!\n";
								if (!$start) {
									echo "Start point not found\n";
								}
								die();
							}
							echo sizeof($amounts)."\n";
							#die();

						// check sums
							#print_r($amounts);
							$checksums = array();
							foreach($amounts as $data) {
								foreach($data["data"] as $year => $amount) {
									if (!isset($checksums[$year]))
										$checksums[$year] = 0;
									$checksums[$year] += $amount;
								}
							}
							#print_r($checksums);
							#print_r($sums);
							foreach($checksums as $year => $amount) {
								$diff = $amount - $sums[$year];
								$diff = intval($diff);
								if ($diff != 0) {
									echo $year." does not match!\n";
									echo "d ".$diff."\n";
									echo "a ".$amount." ".gettype($amount)."\n";
									echo "s ".$sums[$year]." ".gettype($sums[$year])."\n";
									echo "\n";
									foreach($amounts as $data) {
										foreach($data["data"] as $y => $amount) {
											if ($y == $year) {
												echo $amount."\n";
											}
										}
									}
									print_r($nextpages[$side]);
									die();
								}
							}
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

			// clean up
				unset($page);
				unset($nextpages[$side]);
				unset($data->sheets[$side]);
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

		function checkPage(&$page, &$start, &$stop, &$amounts, &$sums, $side, $debug = false) {
			$years = array("2013", "2012", "2011", "2010");

			for ($ii=1; $ii<=$page['numRows']; $ii++) {
				foreach ($page['cells'][$ii] as $jj => $cell) {
					#echo $cell."\n";
					#if (strpos($cell, "Ordentliche Aufwendungen") !== false) {
					if (strpos($cell, "Ordentliche A") !== false) {
						#print_r($page['cells'][$ii]);
						$start = true;
					}
					if (strpos($cell, "Summe") !== false && strpos($cell, "ordentlichen") !== false && strpos($cell, "Aufwend") !== false) {
						$i = 0;
						$row = $page['cells'][$ii];
						print_r($row);
						for ($j = 3; $j<=sizeof($row); $j++) {
							#if (strpos($row[$j], ",") !== false) {
							if (preg_match("/^([0-9.,-])+$/", $row[$j])) {
								$row[$j] = str_replace(".", "", $row[$j]);
								$row[$j] = str_replace(",", ".", $row[$j]);
								if (is_numeric($row[$j])) {
									$sums[$years[$i]] = floatval($row[$j]);
									$i++;
								}
							}
						}
						if (sizeof($sums) != sizeof($years)) {
							print_r($sums);
							print_r($row);
							die();
						}
						print_r($sums);
						#die();
						$stop = true;
					}
				}
				if ($start && !$stop) {
					$row = checkRow($page['cells'], $ii, $side, $years, $debug);
					if (is_array($row))
						$amounts[$row["id"]] = $row["data"];
					}
				}
		}

		function checkRow($cell, $idx, $side, $years, $debug) {
			$row = $cell[$idx];
			foreach($row as $k => $v) {
				$row[$k] = trim($v);
			}
			if ($debug) print_r($row);

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
												"2010"	=>	0,
												"2011"	=>	0,
												"2012"	=>	0,
												"2013"	=>	0,
											),
								"short"	=>	$row[3],
				);

				if ($debug) print_r($row);
				$i = 0;
				for ($j = 3; $j<=sizeof($row); $j++) {
					#if (strpos($row[$j], ",") !== false) {
					if (preg_match("/^([0-9.,-])+$/", $row[$j])) {
						$row[$j] = str_replace(".", "", $row[$j]);
						$row[$j] = str_replace(",", ".", $row[$j]);
						if (is_numeric($row[$j])) {
							$ret["data"]["data"][$years[$i]] = floatval($row[$j]);
							$i++;
						}
					}
				}
				if ($debug) print_r($ret);
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
