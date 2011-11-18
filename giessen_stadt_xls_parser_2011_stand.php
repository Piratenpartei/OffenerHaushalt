<?php

	// includes
		require_once 'inc/gruppen.inc.php';
		require_once 'lib/phpExcelReader/Excel/reader.php';

	// excel reader
		$data = new Spreadsheet_Excel_Reader();
		$data->setUTFEncoder('iconv');
		#$data->setOutputEncoding('UTF-8');
		$data->setOutputEncoding('ISO-8859-1');

	// open file
		$data->read('pdfs/GI/Stadt/684_7030_1_open.xls');

	// error reporting
		error_reporting(E_ALL ^ E_NOTICE);

	// print first info
		echo sizeof($data->sheets)." Pages\n";

	// set filename
		$file = basename(__FILE__);
		$file = str_replace(".php", "_", $file);

	// run
		$infos = array();
		foreach($data->sheets as $side => $page) {
			for ($i=1; $i<=$page['numRows']; $i++) {
				$cell = $page["cells"][$i][1];
				$cell = trim($cell);
				if (strpos($cell, "Kennzahlen f") !== false) {
					// extracted info
						$info = array(
							"page" => ($side+1),
							"data" => array(),
						);

					// find kennzahl
						preg_match_all("/(\d)+/", $cell, $regs);
						#print_r($regs);
						$idx = $regs[0][0];
						$info["idx"] = $idx;

					// complex parsing errors
						if (!empty($idx)) {
							$cplx = 0;
							$cell = explode($idx, $cell);
							if (isset($cell[1])) {
								#print_r($cell);
								if (strpos($cell[1], "Plan 2009") !== false) {
									$cplx++;
									if (strpos($cell[1], "Ist 2009") !== false) {
										$cplx++;
										if (strpos($cell[1], "Plan 2010") !== false) {
											$cplx++;
										}
									}
								}
							}
							#echo $cplx."\n";
						}

					// get short description
						for ($ii=1;  $ii<=$page['numRows']; $ii++) {
							for ($j=1; $j<=2; $j++) {
								$cell = $page["cells"][$ii][$j];
								$search = "Gruppe ".$info["idx"];
								if (strpos($cell, $search) !== false) {
									$cell = explode($search, $cell);
									$cell = array_pop($cell);
									$cell = trim($cell);
									#echo $cell."\n";
									$info["short"] = $cell;
								}
							}
						}

					// set group and maybe fix idx
						if (strlen($info["idx"]) != 8) {
							preg_match_all("/(\d)+/", $info["short"], $regs);
							#print_r($regs);
							if (isset($regs[0][0])) {
								$info["idx"] = $regs[0][0];
								$info["short"] = str_replace($info["idx"], "", $info["short"]);
								$info["short"] = trim($info["short"]);
								print_r($info);
							}
							#die();
						}
						$info["group"] = substr($info["idx"], 0, 2);

					// get long description
						$start_row = 0;
						$end_row = 0;
						for ($ii=1;  $ii<=$page['numRows']; $ii++) {
							$cell = $page["cells"][$ii][1];
							$cell = trim($cell);
							#echo $cell."\n";
							if (strpos($cell, "Beschreibung") !== false) {
								$start_row = $ii;
							} else if (strpos($cell, "Ziele") !== false) {
								$end_row = $ii;
								break;
							} else if (strpos($cell, "Kennzahlen") !== false) {
								$end_row = $ii;
								break;
							}
						}
						#echo $start_row.":".$end_row."\n";

						$long = "";
						for ($ii=$start_row;  $ii<=$end_row; $ii++) {
							for ($j=1; $j<=$page['numCols']; $j++) {
								$cell = $page["cells"][$ii][$j];
								$cell = str_replace("Beschreibung", "", $cell);
								$cell = trim($cell);
								#echo $cell."\n";
								if (strpos($cell, "Ziele") !== false)
									break;
								else if (strpos($cell, "Kennzahlen") !== false)
									break;
								else {
									$long .= $cell;
									$long = trim($long);
									$long .= " ";
								}
							}
						}

						$search = "Gruppe ".$info["idx"];
						if (strpos($long, $search) !== false) {
							$long = explode($search, $long);
							$long = $long[1];
						}
						if (strpos($long, "Verantw") !== false && strpos($long, "Org") !== false && strpos($long, "Einheit") !== false) {
							$long = explode("Verantw.Org.Einheit", $long);
							$long = $long[0];
						}
						$long = trim($long);

						#echo $long."\n";
						$info["long"] = $long;

					// get organisations einheit
						for ($ii=1;  $ii<=$page['numRows']; $ii++) {
							$cell = $page["cells"][$ii][1];
							if (strpos($cell, "Verantw") !== false && strpos($cell, "Org") !== false && strpos($cell, "Einheit") !== false) {
								#echo $cell."\n";
								$tcell = $page["cells"][$ii][2];
								$tcell = trim($tcell);
								#echo $tcell."\n";
								if (empty($tcell)) {
									$tcell = explode("Einheit", $cell);
									#print_r($tcell);
									$tcell = $tcell[1];
									$tcell = trim($tcell);
								}

								preg_match_all("/\-(\d+)\-/", $tcell, $regs);
								#print_r($regs);

								$tcell = str_replace("-".$regs[1][0]."-", "", $tcell);
								$tcell = trim($tcell);

								$info["org"] = array(
									"id"	=>	$regs[1][0],
									"text"	=>	$tcell,
								);
								#print_r($info);
							}
						}

					// debug
						#print_r($info);

					// find col indexes
						$idx_2009 = -1;
						$idx_2009_sub = -1;
						$idx_2010 = -1;
						$idx_2010_sub = -1;
						$idx_2011 = -1;

						echo ($side+1)." ".$i." ".$page["cells"][$i][1]."\n";
						for ($j=2; $j<=$page['numCols']; $j++) {
							$cell = $page["cells"][$i][$j];
							$cell = trim($cell);
							switch ($cell) {
								case "Plan 2009":
									break;
								case "Ist 2009":
									$idx_2009 = $j;
									break;
								case "Plan 2009 Ist 2009":
									$idx_2009 = $j;
									$idx_2009_sub = 1;
									break;
								case "Plan 2010":
									$idx_2010 = $j;
									break;
								case "Plan 2011":
									$idx_2011 = $j;
									break;
								default:
									if (!empty($cell)) {
										echo $cell;
										die();
									}
							}
/*
							if (!empty($cell)) {
								echo "\t".$j." ".$cell."\n";
							}
*/
						}
						#echo $idx_2009." : ".$idx_2010." : ".$idx_2011."\n";

					// extract annual numbers
						/* ordentliche aufwendungen nehmen oder verwaltungsergebnis */

						for ($ii=$i; $ii<=$page['numRows']; $ii++) {
							$cell = $page["cells"][$ii][1];
							if (strpos($cell, "Ordentliche") !== false && strpos($cell, "Aufwendungen") !== false) {
								// 2009
									// default
										$val_2009 = $page["cells"][$ii][$idx_2009];
										$val_2009 = createNumber($val_2009);
									// simple parsing error
										if ($idx_2009_sub != -1) {
											$val_2009 = explode(" ", $val_2009);
											$val_2009 = $val_2009[$idx_2009_sub];
											$val_2009 = createNumber($val_2009);
										}
									// set
										$info["data"]["2009"] = $val_2009;

								// 2010
									$val_2010 = $page["cells"][$ii][$idx_2010];
									$val_2010 = createNumber($val_2010);
									$info["data"]["2010"] = $val_2010;

								// 2011
									$val_2011 = $page["cells"][$ii][$idx_2011];
									$val_2011 = createNumber($val_2011);
									$info["data"]["2011"] = $val_2011;

								// exit
									break;
							}
						}

					// look on next page
						if (empty($info["data"])) {
							$nextpage = $data->sheets[($side+1)];
							for ($ii=1; $ii<=$nextpage['numRows']; $ii++) {
								$cell = $nextpage["cells"][$ii][1];
								$row = "";
								if (strpos($cell, "Ordentliche") !== false && strpos($cell, "Aufwendungen") !== false) {
									// aggregate
										for ($j=1; $j<=$nextpage['numCols']; $j++) {
											$cell = $nextpage["cells"][$ii][$j];
											$cell = trim($cell);
											$row .= $cell." ";
										}
										$row = trim($row);

									// prepare for fix
										$cell = $row;
										$cplx = 4;
										break;
								}
							}
						}

					// fix complex parsing errors
						if ($cplx > 0) {
							$cell = explode(" ", $cell);
							#print_r($cell);
							if ($cplx == 4) {
								$val_2011 = createNumber(array_pop($cell));
								$info["data"]["2011"] = $val_2011;
								$cplx--;
							}
							if ($cplx == 3) {
								$val_2010 = createNumber(array_pop($cell));
								$info["data"]["2010"] = $val_2010;
								$cplx--;
							}
							if ($cplx == 2) {
								array_pop($cell);
								$cplx--;
							}
							if ($cplx == 1) {
								$val_2009 = createNumber(array_pop($cell));
								$info["data"]["2009"] = $val_2009;
							}
						}

					// debug
						print_r($info);

					// check for parsing errors
						if (!isset($val_2009) || !isset($val_2010) || !isset($val_2011)) {
							echo "\n!!! PARSING ERROR NUMVER VALUES !!!\n";
							die();
						} else if (empty($info["data"])) {
							echo "\n!!! PARSING ERROR NUMVER VALUES !!!\n";
							die();
						}

						if (!isset($info["long"]) || !isset($info["short"]) || !isset($info["org"])) {
							echo "\n!!! PARSING ERROR TEXT VALUES !!!\n";
							die();
						} else if (empty($info["long"]) || empty($info["short"]) || empty($info["org"])) {
							echo "\n!!! PARSING ERROR TEXT VALUES !!!\n";
							die();
						}

					// debug
						#file_put_contents("data/".$file."page_".$side.".txt", var_export($page, true));
						#die();

					// add data
						$gid = $info["group"];
						if (!isset($infos[$gid]))
							$infos[$gid] = array(
								"text"	=>	$gruppen[$gid],
								"data"	=>	array(),
							);
						unset($info["group"]);

						$oid = intval($info["org"]["id"]);
						if (!isset($infos[$gid]["data"][$oid]))
							$infos[$gid]["data"][$oid] = array(
								"text"	=>	$info["org"]["text"],
								"data"	=>	array(),
							);
						unset($info["org"]);

						$idx = intval($info["idx"]);
						unset($info["idx"]);

						$infos[$gid]["data"][$oid]["data"][$idx] = $info;

					// output
						echo "\n";
				}
			}
		}

	// sort
		ksort($infos);
		foreach($infos as $oid => $data) {
			ksort($data["data"]);
			$infos[$oid]["data"] = $data["data"];
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

?>
