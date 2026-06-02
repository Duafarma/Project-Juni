<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu	= new Security;
$base	= new DB;
$data	= new Data;
$catat	= date('Y-m-d H:i:s');
$admin	= $secu->injection(@$_COOKIE['adminkuy']);
$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
$act	= $secu->injection(@$_GET['act']);
$secu->validadmin($admin, $kunci);
if ($secu->validadmin($admin, $kunci) == false) {
	header('location:' . $data->sistem('url_sis') . '/signout');
} else {
	$conn	= $base->open();
	$msgBugs = array();
	switch ($act) {
		case "input":
			$hasil = "Error";
			// Generate ID only once, then use it for both systems
			// Always generate new ID for the initial operation
			$id = 'TRR' . time();
			$_POST['id'] = $id; // Ensure the same ID is sent to other system

			$type	= $secu->injection(@$_POST['transfer_apl_type']);
			$tgl	= $secu->injection(@$_POST['tanggal']);
			$from	= $secu->injection(@$_POST['transfer_apl_from']);
			$to		= $secu->injection(@$_POST['transfer_apl_to']);
			$ket	= $secu->injection(@$_POST['keterangan']);
			$status	= 'Process';
			$jum	= count(@$_POST['product']);
			$no		= 0;

			// Get the names of source and destination applications based on their IDs
			$from_app = $data->get_apl($from);
			$to_app = $data->get_apl($to);
			$nama_apl_from = isset($from_app[0]['nama_apl']) ? $from_app[0]['nama_apl'] : '';
			$nama_apl_to = isset($to_app[0]['nama_apl']) ? $to_app[0]['nama_apl'] : '';

			// Determine which application name to use for code generation based on transfer type
			if ($type == 'OUT') {
				// For outgoing transfers, use source app name (which should be the current app)
				$nama_apl_for_code = $nama_apl_from;
			} else {
				// For incoming transfers, use destination app name (which should be the current app)
				$nama_apl_for_code = $nama_apl_to;
			}

			// Use the determined app name for code generation
			$kode = $data->transcodetfretur('transaksi_transferretur', $nama_apl_for_code, 'kode_ttr', 'TRF');

			// Log the application names for debugging
			if (isset($logFile)) {
				fwrite($logFile, date('Y-m-d H:i:s') . " - From app: {$nama_apl_from}, To app: {$nama_apl_to}, Using: {$nama_apl_for_code}\n");
			}

			$kode = $data->transcodetfretur('transaksi_transferretur', $nama_apl_for_code, 'kode_ttr', 'TRF');

			$_POST['kode'] = $kode; // Include it in POST data for the other system

			// Create log directory if not exists
			if (!file_exists('../../logs')) {
				mkdir('../../logs', 0777, true);
			}
			$logFile = fopen('../../logs/transfer_action_' . date('Y-m-d') . '.log', 'a+');
			fwrite($logFile, date('Y-m-d H:i:s') . " - Starting new transfer: Type={$type}\n");
			fwrite($logFile, date('Y-m-d H:i:s') . " - Generated ID: {$id}, Code: {$kode}\n");

			if ($type == 'OUT') {
				// proses transfer OUT
				if (count($msgBugs) == 0) {
					$target 	= $data->get_apl($to);
					$targetUrl 	= $target[0]['base_url_apl'];
					$targetKey  = $target[0]['key_apl'];
					$encrypt    = md5(md5($id . "#" . $kode) . "#" . $targetKey);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/postProductReturTransfer.php?encrypt=" . $encrypt . "&act=" . $act);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$res = curl_exec($ch);
					// curl handling error
					if (curl_errno($ch)) {
						$error_msg = curl_error($ch);
					}
					curl_close($ch);
					if (isset($error_msg)) {
						$hasil = $error_msg;
						array_push($msgBugs, $hasil);
					} else {
						$json = json_decode($res);

						// Update response handling for new JSON format
						if (isset($json->success) && $json->success === true) {
							$hasil = "Success";
						} else {
							$hasil = isset($json->message) ? $json->message : "Error";
							if (isset($json->errors) && is_array($json->errors)) {
								$msgBugs = array_merge($msgBugs, $json->errors);
							} else {
								array_push($msgBugs, $hasil);
							}
						}
					}
				}
				// save transfer product detail
				if (count($msgBugs) == 0) {
					while ($no < $jum) {
						if (count($msgBugs) == 0) {
							$produk	= $secu->injection(@$_POST['product'][$no]);
							$jumlah	= str_replace('.', '', @$_POST['jumlah'][$no]);
							$idpsd = $secu->injection(@$_POST['idpsd'][$no]);
							// save
							if (count($msgBugs) == 0) {
								$qSave = "INSERT
                                            INTO
                                            transaksi_transferreturdetail (
                                                id_ttr, 
                                                id_i_r, 
                                                id_pro,
                                                jumlah_ttd, 
                                                created_at, 
                                                created_by)
                                            VALUES(
                                                :id, 
                                                :id_i_r,
                                                :produk,
                                                :jumlah, 
                                                :catat, 
                                                :admin)";
								try {
									$save	= $conn->prepare($qSave);
									$save->bindParam(':id', $id, PDO::PARAM_STR);
									$save->bindParam(':produk', $produk, PDO::PARAM_STR);
									$save->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
									$save->bindParam(':catat', $catat, PDO::PARAM_STR);
									$save->bindParam(':admin', $admin, PDO::PARAM_STR);
									$save->bindParam(':id_i_r', $idpsd, PDO::PARAM_STR);
									$save->execute();
								} catch (PDOException $e) {
									array_push($msgBugs, $e->getMessage());
								}
							}
							$no++;
						} else {
							break;
						}
					}
					// save transfer product
					if (count($msgBugs) == 0) {
						$qSave = "INSERT
                                    INTO
                                    transaksi_transferretur (
                                        id_ttr, 
                                        kode_ttr, 
                                        tipe_ttr, 
                                        id_app_from, 
                                        id_app_to, 
                                        tgl_ttr, 
                                        ket_ttr, 
                                        status_ttr, 
                                        created_at, 
                                        created_by)
                                    VALUES (
                                        :id, 
                                        :kode,
                                        :type, 
                                        :id_app_from, 
                                        :id_app_to, 
                                        :tgl_ttr, 
                                        :ket, 
                                        :status, 
                                        :catat, 
                                        :admin)";
						try {
							$save	= $conn->prepare($qSave);
							$save->bindParam(':id', $id, PDO::PARAM_STR);
							$save->bindParam(':kode', $kode, PDO::PARAM_STR);
							$save->bindParam(':type', $type, PDO::PARAM_STR);
							$save->bindParam(':id_app_from', $from, PDO::PARAM_STR);
							$save->bindParam(':id_app_to', $to, PDO::PARAM_STR);
							$save->bindParam(':tgl_ttr', $tgl, PDO::PARAM_STR);
							$save->bindParam(':ket', $ket, PDO::PARAM_STR);
							$save->bindParam(':status', $status, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
					}
					// save riwayat
					if (count($msgBugs) == 0) {
						$qRiwayat = "INSERT
                                        INTO
                                        riwayat (
                                            kode_riwayat, 
                                            menu_riwayat, 
                                            status_riwayat, 
                                            ket_riwayat,
                                            created_at, 
                                            created_by)
                                        VALUES (
                                            '$id', 
                                            'Transfer Retur', 
                                            'Create',
                                            '$status', 
                                            '$catat',
                                            '$admin')";
						try {
							$riwayat = $conn->prepare($qRiwayat);
							$riwayat->execute();
						} catch (PDOException $th) {
							array_push($msgBugs, $th->getMessage());
						}
					}
				}
			}
			if ($type == 'IN') {
				// check produk exists or not
				while ($no < $jum) {
					if (count($msgBugs) == 0) {
						$produkExt			= $secu->injection($_POST['product'][$no]);
						$idpsdExt 			= $secu->injection($_POST['idpsd'][$no]);
						$produk 			=	 $idpsd = null;
						$jumlah				=	 str_replace('.', '', $_POST['jumlah'][$no]);
						$namaproduct 		= $secu->injection($_POST['namaproduct'][$no]);
						$bcode 				= $secu->injection($_POST['bcode'][$no]);
						$id_trd 			= $secu->injection($_POST['id_trd'][$no]);
						$tgl_expired 		=	 $secu->injection($_POST['tgl_expired'][$no]);
						$tgl_psd 			= $secu->injection($_POST['tgl_psd'][$no]);
						$gudang 			= $secu->injection($_POST['gudang'][$no]);
						// check product
						$qSearch = "SELECT * 
                                    FROM produk
                                    WHERE
                                        nama_pro = '$namaproduct' AND
                                        minstok_pro > 0 AND
                                        status_pro = 'Active'
                                    LIMIT 1";
						try {
							$search	= $conn->prepare($qSearch);
							$search->execute();
							$dataTf  = $search->fetch(PDO::FETCH_ASSOC);
							if (!is_array($dataTf)) {
								array_push($msgBugs, "Produk " . $namaproduct . " belum tersedia pada system penerima!");
							} else {
								$produk = $dataTf['id_pro'];
							}
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
						// check product stok detail
						if (empty($msgBugs)) {
							$qSearch = "SELECT * 
                                        FROM inventory_retur
                                        WHERE
                                            id_pro = '$produk' AND
                                            no_bcode = '$bcode'
                                        ORDER BY sisa ASC
                                        LIMIT 1";
							try {
								$search	= $conn->prepare($qSearch);
								$search->execute();
								$dataTf  = $search->fetch(PDO::FETCH_ASSOC);
								if (!is_array($dataTf)) {
									$qSave = "INSERT 
                                                INTO inventory_retur (
                                                    id_r_d,
                                                    id_pro,
                                                    no_bcode,
                                                    ed,
                                                    tanggal,
                                                    masuk,
                                                    keluar,
                                                    sisa,
                                                    gudang,
                                                    created_at, 
                                                    created_by,
                                                    updated_at, 
                                                    updated_by)
                                                VALUES (
                                                    :id_trd,
                                                    :id_pro,
                                                    :no_bcode,
                                                    :tgl_expired,
                                                    :tgl_psd,
                                                    0,
                                                    0,
                                                    0,
                                                    :gudang,
                                                    :catat, 
                                                    'System',
                                                    :catat, 
                                                    'System')";
									try {
										$save	= $conn->prepare($qSave);
										$save->bindParam(':id_trd', $id_trd, PDO::PARAM_STR);
										$save->bindParam(':id_pro', $produk, PDO::PARAM_STR);
										$save->bindParam(':no_bcode', $bcode, PDO::PARAM_STR);
										$save->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
										$save->bindParam(':tgl_psd', $tgl_psd, PDO::PARAM_STR);
										$save->bindParam(':gudang', $gudang, PDO::PARAM_STR);
										$save->bindParam(':catat', $catat, PDO::PARAM_STR);
										$save->execute();
										$idpsd = $conn->lastInsertId();
									} catch (PDOException $e) {
										array_push($msgBugs, $e->getMessage());
									}
								} else {
									$idpsd = $dataTf['id_i_r'];
								}
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						}
						if (count($msgBugs) == 0) {
							$qSave = "INSERT
                                        INTO
                                        transaksi_transferreturdetail (
                                            id_ttr, 
                                            id_i_r, 
                                            id_ext_i_r, 
                                            id_pro, 
                                            id_ext_pro,
                                            jumlah_ttd, 
                                            created_at, 
                                            created_by)
                                        VALUES(
                                            :id, 
                                            :id_i_r,
                                            :id_ext_i_r, 
                                            :produk, 
                                            :id_ext_pro,
                                            :jumlah, 
                                            :catat, 
                                            :admin)";
							try {
								$save	= $conn->prepare($qSave);
								$save->bindParam(':id', $id, PDO::PARAM_STR);
								$save->bindParam(':produk', $produk, PDO::PARAM_STR);
								$save->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
								$save->bindParam(':catat', $catat, PDO::PARAM_STR);
								$save->bindParam(':admin', $admin, PDO::PARAM_STR);
								$save->bindParam(':id_i_r', $idpsd, PDO::PARAM_STR);
								$save->bindParam(':id_ext_i_r', $idpsdExt, PDO::PARAM_STR);
								$save->bindParam(':id_ext_pro', $produkExt, PDO::PARAM_STR);
								$save->execute();
							} catch (PDOException $e) {
								array_push($msgBugs, $e->getMessage());
							}
						} else {
							break;
						}
					}
					$no++;
				}
				if (count($msgBugs) == 0) {
					// save transfer product
					$status	= 'Process';
					if (count($msgBugs) == 0) {
						$qSave = "INSERT
                                    INTO
                                    transaksi_transferretur (
                                        id_ttr, 
                                        kode_ttr, 
                                        tipe_ttr, 
                                        id_app_from, 
                                        id_app_to, 
                                        tgl_ttr, 
                                        ket_ttr, 
                                        status_ttr, 
                                        created_at, 
                                        created_by)
                                    VALUES (
                                        :id, 
                                        :kode, 
                                        :type, 
                                        :id_app_from, 
                                        :id_app_to, 
                                        :tgl_ttr, 
                                        :ket, 
                                        :status, 
                                        :catat, 
                                        :admin)";
						try {
							$save	= $conn->prepare($qSave);
							$save->bindParam(':id', $id, PDO::PARAM_STR);
							$save->bindParam(':kode', $kode, PDO::PARAM_STR);
							$save->bindParam(':type', $type, PDO::PARAM_STR);
							$save->bindParam(':id_app_from', $from, PDO::PARAM_STR);
							$save->bindParam(':id_app_to', $to, PDO::PARAM_STR);
							$save->bindParam(':tgl_ttr', $tgl, PDO::PARAM_STR);
							$save->bindParam(':ket', $ket, PDO::PARAM_STR);
							$save->bindParam(':status', $status, PDO::PARAM_STR);
							$save->bindParam(':catat', $catat, PDO::PARAM_STR);
							$save->bindParam(':admin', $admin, PDO::PARAM_STR);
							$save->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
					}
					// save riwayat
					if (count($msgBugs) == 0) {
						$qRiwayat = "INSERT
                                        INTO
                                        riwayat (
                                            kode_riwayat, 
                                            menu_riwayat, 
                                            status_riwayat, 
                                            ket_riwayat,
                                            created_at, 
                                            created_by)
                                        VALUES (
                                            '$id', 
                                            'Transfer Retur', 
                                            'Create',
                                            '$status', 
                                            '$catat',
                                            '$admin')";
						try {
							$riwayat = $conn->prepare($qRiwayat);
							$riwayat->execute();
						} catch (PDOException $e) {
							array_push($msgBugs, $e->getMessage());
						}
					}
				}
				// proses transfer OUT
				if (count($msgBugs) == 0) {
					$target 	= $data->get_apl($to);
					$targetUrl 	= $target[0]['base_url_apl'];
					$targetKey  = $target[0]['key_apl'];
					$encrypt    = md5(md5($id . "#" . $kode) . "#" . $targetKey);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/postProductReturTransfer.php?encrypt=" . $encrypt . "&act=" . $act);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$res = curl_exec($ch);
					// curl handling error
					if (curl_errno($ch)) {
						$error_msg = curl_error($ch);
					}
					curl_close($ch);
					if (isset($error_msg)) {
						$hasil = $error_msg;
						array_push($msgBugs, $hasil);
					} else {
						$json = json_decode($res);

						// Update response handling for new JSON format
						if (isset($json->success) && $json->success === true) {
							$hasil = "Success";
						} else {
							$hasil = isset($json->message) ? $json->message : "Error";
							if (isset($json->errors) && is_array($json->errors)) {
								$msgBugs = array_merge($msgBugs, $json->errors);
							} else {
								array_push($msgBugs, $hasil);
							}
						}
					}
				}
			}
			$conn	= $base->close();

			// Prepare response data
			$success = (count($msgBugs) == 0 && $hasil == "Success");
			$message = $success ? "Transfer retur berhasil diproses" : "Gagal memproses transfer retur";
			$responseData = [
				'id' => $id,
				'kode' => $kode,
				'type' => $type,
				'status' => $success ? "Success" : "Error"
			];

			// Send standardized JSON response
			echo $data->jsonResponse($success, $message, $responseData, $msgBugs);
			break;

		case "approval":
			header('Content-Type: application/json'); // Pastikan output selalu JSON

			$hasil      = "Error";
			$id         = $secu->injection($_POST['id']);
			$kode       = $secu->injection($_POST['kode']);
			$type       = $secu->injection($_POST['type']);
			$from       = $secu->injection($_POST['id_app_from']);
			$to         = $secu->injection($_POST['id_app_to']);
			$approval   = $secu->injection($_POST['approval']);
			$status     = ($approval == "1") ? 'Approved' : 'Rejected';
			$selfApl    = $data->self_apl();

			// Create log directory if not exists
			if (!file_exists('../../logs')) {
				mkdir('../../logs', 0777, true);
			}
			$logFile = fopen('../../logs/approval_' . date('Y-m-d') . '.log', 'a+');
			fwrite($logFile, date('Y-m-d H:i:s') . " - Starting approval process for id=$id, kode=$kode, status=$status\n");

			try {
				// Step 1: Update local database first - this has priority
				try {
					// Selalu update di database lokal terlebih dahulu
					$qUpdate = "UPDATE 
								transaksi_transferretur
							SET
								status_ttr = :status,
								updated_at = :catat,
								updated_by = :admin
							WHERE
								id_ttr = :id";

					$update = $conn->prepare($qUpdate);
					$update->bindParam(':status', $status, PDO::PARAM_STR);
					$update->bindParam(':catat', $catat, PDO::PARAM_STR);
					$update->bindParam(':admin', $admin, PDO::PARAM_STR);
					$update->bindParam(':id', $id, PDO::PARAM_STR);
					$update->execute();

					fwrite($logFile, date('Y-m-d H:i:s') . " - Updated local record, rows affected: " . $update->rowCount() . "\n");

					if ($update->rowCount() > 0) {
						// Add to history
						$qRiwayat = "INSERT INTO riwayat (
										kode_riwayat, 
										menu_riwayat, 
										status_riwayat,
										ket_riwayat,
										created_at, 
										created_by
									) VALUES (
										:id, 
										'Transfer Retur', 
										'Update', 
										:ket, 
										:catat,
										:admin
									)";

						$riwayat = $conn->prepare($qRiwayat);
						$riwayat->bindParam(':id', $id, PDO::PARAM_STR);
						$riwayat->bindParam(':ket', $status, PDO::PARAM_STR);
						$riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
						$riwayat->bindParam(':admin', $admin, PDO::PARAM_STR);
						$riwayat->execute();

						// If approved, update inventory
						if ($status == 'Approved' && $approval == '1') {
							// Get all items in this transaction
							$qItems = "SELECT ttd.*, ir.sisa 
									FROM transaksi_transferreturdetail ttd
									LEFT JOIN inventory_retur ir ON ttd.id_i_r = ir.id_i_r
									WHERE ttd.id_ttr = :id";
							$items = $conn->prepare($qItems);
							$items->bindParam(':id', $id, PDO::PARAM_STR);
							$items->execute();

							while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
								$id_ir = $item['id_i_r'];
								$jumlah = $item['jumlah_ttd'];

								// Adjust inventory based on transfer type
								if ($type == 'IN') {
									// For receiving system, increase stock
									$qUpdate = "UPDATE inventory_retur 
												SET masuk = masuk + :jumlah, 
													sisa = sisa + :jumlah,
													updated_at = :catat,
													updated_by = :admin 
												WHERE id_i_r = :id_ir";
								} else {
									// For sending system, decrease stock
									$qUpdate = "UPDATE inventory_retur 
												SET keluar = keluar + :jumlah, 
													sisa = sisa - :jumlah,
													updated_at = :catat,
													updated_by = :admin 
												WHERE id_i_r = :id_ir";
								}

								$invUpdate = $conn->prepare($qUpdate);
								$invUpdate->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
								$invUpdate->bindParam(':catat', $catat, PDO::PARAM_STR);
								$invUpdate->bindParam(':admin', $admin, PDO::PARAM_STR);
								$invUpdate->bindParam(':id_ir', $id_ir, PDO::PARAM_STR);
								$invUpdate->execute();

								fwrite($logFile, date('Y-m-d H:i:s') . " - Updated inventory item $id_ir: " . ($type == 'IN' ? "Added $jumlah" : "Removed $jumlah") . "\n");
							}
						}

						// Mark local transaction as successful
						$hasil = "Success";

						// Step 2: Now try to communicate with the other system - errors here won't fail the transaction
						$targetSystemId = ($selfApl['id_apl'] == $from) ? $to : $from;

						$qTargetSystem = "SELECT * FROM aplikasi WHERE id_apl = :id_apl";
						$targetQuery = $conn->prepare($qTargetSystem);
						$targetQuery->bindParam(':id_apl', $targetSystemId, PDO::PARAM_STR);
						$targetQuery->execute();
						$targetSystem = $targetQuery->fetch(PDO::FETCH_ASSOC);

						if ($targetSystem) {
							// Siapkan komunikasi API
							$apiUrl = rtrim($targetSystem['base_url_apl'], '/') . '/api/postProductReturTransfer.php?act=approval';
							$apiKey = $targetSystem['key_apl'];
							$encrypt = md5(md5($id . "#" . $kode) . "#" . $apiKey);
							$apiUrl .= "&encrypt=" . $encrypt;

							fwrite($logFile, date('Y-m-d H:i:s') . " - Calling API at: $apiUrl\n");

							// Determine the appropriate type to send to the other system
							$otherType = ($type == 'IN') ? 'OUT' : 'IN';

							// Create API data
							$apiData = [
								'id' => $id,
								'kode' => $kode,
								'type' => $otherType, // Send the reversed type
								'id_app_from' => $from,
								'id_app_to' => $to,
								'approval' => $approval
							];

							try {
								// Make API call with improved error handling
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_URL, $apiUrl);
								curl_setopt($ch, CURLOPT_POST, 1);
								curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								curl_setopt($ch, CURLOPT_TIMEOUT, 30);
								curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

								$response = curl_exec($ch);
								$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
								$curlError = curl_error($ch);
								curl_close($ch);

								fwrite($logFile, date('Y-m-d H:i:s') . " - API Response Code: $httpCode\n");
								fwrite($logFile, date('Y-m-d H:i:s') . " - API Response: " . substr($response, 0, 500) . "\n");

								if ($curlError) {
									fwrite($logFile, date('Y-m-d H:i:s') . " - Curl error but continuing: $curlError\n");
								}
							} catch (Exception $e) {
								fwrite($logFile, date('Y-m-d H:i:s') . " - API call exception but continuing: " . $e->getMessage() . "\n");
								// Tetap lanjutkan - kita tidak ingin API error menggagalkan transaksi lokal
							}
						} else {
							fwrite($logFile, date('Y-m-d H:i:s') . " - Target system not found: $targetSystemId\n");
						}
					} else {
						$msgBugs[] = "Gagal memperbarui status - record tidak ditemukan";
						fwrite($logFile, date('Y-m-d H:i:s') . " - Failed to update status - record not found\n");
					}
				} catch (PDOException $e) {
					$msgBugs[] = "Database error: " . $e->getMessage();
					fwrite($logFile, date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n");
				}
			} catch (Exception $e) {
				$msgBugs[] = "General error: " . $e->getMessage();
				fwrite($logFile, date('Y-m-d H:i:s') . " - General error: " . $e->getMessage() . "\n");
			}

			fclose($logFile);

			// Return standardized JSON response
			$success = ($hasil == "Success");
			$message = $success
				? "Transfer retur berhasil " . ($approval == "1" ? "disetujui" : "ditolak")
				: "Gagal " . ($approval == "1" ? "menyetujui" : "menolak") . " transfer retur";

			// Gunakan fungsi jsonResponse dari kelas Data
			echo $data->jsonResponse($success, $message, ['id' => $id, 'status' => $status], $msgBugs);
			break;

		case "cancel":
			$hasil      = "Error";
			$id         = $secu->injection(@$_POST['id']);
			$kode       = $secu->injection(@$_POST['kode']);
			$type       = $secu->injection(@$_POST['type']);
			$from       = $secu->injection(@$_POST['id_app_from']);
			$to         = $secu->injection(@$_POST['id_app_to']);
			$status     = 'Canceled';
			$selfApl    = $data->self_apl();

			// Create log directory if not exists
			if (!file_exists('../../logs')) {
				mkdir('../../logs', 0777, true);
			}

			// Log request
			$logFile = fopen('../../logs/cancel_' . date('Y-m-d') . '.log', 'a+');
			fwrite($logFile, date('Y-m-d H:i:s') . " - CANCEL REQUEST: " . json_encode($_POST) . "\n");

			// Validate the transfer record exists
			$qRead = "SELECT * FROM transaksi_transferretur WHERE id_ttr = :id";
			try {
				$read = $conn->prepare($qRead);
				$read->bindParam(':id', $id, PDO::PARAM_STR);
				$read->execute();
				$transfer = $read->fetch(PDO::FETCH_ASSOC);

				if (!$transfer) {
					array_push($msgBugs, "Data transfer retur tidak ditemukan");
				} elseif ($transfer['status_ttr'] !== 'Waiting') {
					array_push($msgBugs, "Hanya transfer dengan status 'Waiting' yang dapat dibatalkan");
				}
			} catch (PDOException $e) {
				array_push($msgBugs, $e->getMessage());
				fwrite($logFile, date('Y-m-d H:i:s') . " - DB ERROR: " . $e->getMessage() . "\n");
			}

			// Proceed if no errors so far
			if (empty($msgBugs)) {
				// Step 1: Update status on this system
				$qUpdate = "UPDATE 
							transaksi_transferretur
						SET
							status_ttr = :status,
							updated_at = :catat,
							updated_by = :admin
						WHERE
							id_ttr = :id";

				try {
					$update = $conn->prepare($qUpdate);
					$update->bindParam(':status', $status, PDO::PARAM_STR);
					$update->bindParam(':catat', $catat, PDO::PARAM_STR);
					$update->bindParam(':admin', $admin, PDO::PARAM_STR);
					$update->bindParam(':id', $id, PDO::PARAM_STR);
					$update->execute();

					fwrite($logFile, date('Y-m-d H:i:s') . " - Updated local status to: {$status}\n");

					// Add to history
					$qRiwayat = "INSERT INTO riwayat (
									kode_riwayat, 
									menu_riwayat, 
									status_riwayat,
									ket_riwayat,
									created_at, 
									created_by
								) VALUES (
									:id, 
									'Transfer Retur', 
									'Update', 
									:ket, 
									:catat,
									:admin
								)";

					$riwayat = $conn->prepare($qRiwayat);
					$riwayat->bindParam(':id', $id, PDO::PARAM_STR);
					$riwayat->bindParam(':ket', $status, PDO::PARAM_STR);
					$riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
					$riwayat->bindParam(':admin', $admin, PDO::PARAM_STR);
					$riwayat->execute();

					// Step 2: Sync with the other system
					// Determine which system to call (from or to)
					$targetAppId = ($selfApl['id_apl'] == $from) ? $to : $from;

					$qApp = "SELECT * FROM aplikasi WHERE id_apl = :targetAppId AND active_apl = '1' LIMIT 1";
					$getApp = $conn->prepare($qApp);
					$getApp->bindParam(':targetAppId', $targetAppId, PDO::PARAM_STR);
					$getApp->execute();
					$targetApp = $getApp->fetch(PDO::FETCH_ASSOC);

					if (!$targetApp) {
						array_push($msgBugs, "Aplikasi tujuan tidak ditemukan atau tidak aktif");
						fwrite($logFile, date('Y-m-d H:i:s') . " - Target app not found: {$targetAppId}\n");
					} else {
						// Call the API to cancel on the other system
						$apiUrl = rtrim($targetApp['base_url_apl'], '/') . '/api/postProductReturTransfer.php?act=delete';
						$apiKey = $targetApp['key_apl'];
						$encrypt = md5(md5($id . "#" . $kode) . "#" . $apiKey);
						$apiUrl .= "&encrypt=" . $encrypt;

						fwrite($logFile, date('Y-m-d H:i:s') . " - Calling API: {$apiUrl}\n");

						// Make API call
						$apiData = [
							'id' => $id,
							'kode' => $kode,
							'kode_ext' => $kode // kode_ext_ttr in the other system
						];

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $apiUrl);
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_TIMEOUT, 30);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

						$apiResponse = curl_exec($ch);
						$curlError = curl_error($ch);
						$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						curl_close($ch);

						fwrite($logFile, date('Y-m-d H:i:s') . " - API Response ({$httpCode}): {$apiResponse}\n");

						if ($curlError) {
							array_push($msgBugs, "Error komunikasi dengan sistem lain: " . $curlError);
							fwrite($logFile, date('Y-m-d H:i:s') . " - cURL Error: " . $curlError . "\n");
						} else {
							try {
								$apiResult = json_decode($apiResponse, true);

								if (!isset($apiResult['success']) || $apiResult['success'] !== true) {
									$apiErrorMsg = isset($apiResult['message']) ? $apiResult['message'] : 'Unknown error';
									array_push($msgBugs, "Error dari sistem lain: " . $apiErrorMsg);
									fwrite($logFile, date('Y-m-d H:i:s') . " - API Error: " . $apiErrorMsg . "\n");
								} else {
									$hasil = "Success";
									fwrite($logFile, date('Y-m-d H:i:s') . " - API Success\n");
								}
							} catch (Exception $e) {
								array_push($msgBugs, "Error memproses respons dari sistem lain");
								fwrite($logFile, date('Y-m-d H:i:s') . " - JSON Parse Error: " . $e->getMessage() . "\n");
							}
						}
					}
				} catch (PDOException $e) {
					array_push($msgBugs, $e->getMessage());
					fwrite($logFile, date('Y-m-d H:i:s') . " - Update Error: " . $e->getMessage() . "\n");
				}
			}

			// Close log file
			fwrite($logFile, date('Y-m-d H:i:s') . " - Final Result: " . (empty($msgBugs) ? "Success" : implode(", ", $msgBugs)) . "\n\n");
			fclose($logFile);

			// Prepare response
			$success = empty($msgBugs);
			$message = $success ? "Transfer retur berhasil dibatalkan" : "Gagal membatalkan transfer retur";

			$responseData = [
				'id' => $id,
				'kode' => $kode,
				'status' => $status,
				'type' => $type
			];

			// Send JSON response
			echo $data->jsonResponse($success, $message, $responseData, $msgBugs);
			break;

		case "tutup":
			$kode	= $secu->injection($_POST['keycode']);
			$proses	= 'Close';
			$edit = $conn->prepare("UPDATE transaksi_order SET proses_tor=:proses, updated_at=:catat, updated_by=:admin WHERE id_tor=:kode");
			$edit->bindParam(':kode', $kode, PDO::PARAM_STR);
			$edit->bindParam(':proses', $proses, PDO::PARAM_STR);
			$edit->bindParam(':catat', $catat, PDO::PARAM_STR);
			$edit->bindParam(':admin', $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat = $conn->prepare("INSERT INTO riwayat VALUES('', '$kode', 'Order Produk', 'Update', 'Tutup Order Produk', '$catat', '$admin')");
			$riwayat->execute();

			// Prepare response data
			$success = ($edit !== false);
			$message = $success ? "Order berhasil ditutup" : "Gagal menutup order";
			$responseData = [
				'kode' => $kode,
				'status' => $proses
			];

			// Send standardized JSON response
			echo $data->jsonResponse($success, $message, $responseData, []);
			break;
	}
	$conn	= $base->close();
}
