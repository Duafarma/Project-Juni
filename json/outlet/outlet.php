<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/paging.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$paging	= new Paging;
	$conn	= $base->open();
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	//POST DATA
	$cari	= $secu->injection(@$_GET['caridata']);
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="10">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_out) AS total FROM outlet AS A INNER JOIN outlet_alamat AS B ON A.id_out=B.id_out INNER JOIN outlet_diskon AS C ON A.id_out=C.id_out INNER JOIN kategori_outlet AS D ON A.id_kot=D.id_kot WHERE A.nama_out LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_out, A.kode_out, A.nama_out, A.resmi_out,F.nama_mg,  A.npwp_out, A.ofcode_out, B.telp_ola, B.email_ola, D.nama_kot, C.diskon_odi, A.status_out FROM outlet AS A INNER JOIN outlet_alamat AS B ON A.id_out=B.id_out INNER JOIN outlet_diskon AS C ON A.id_out=C.id_out INNER JOIN kategori_outlet AS D ON A.id_kot=D.id_kot LEFT JOIN master_grup AS F ON A.id_mg = F.id_mg  WHERE  A.nama_out LIKE '%$cari%' ORDER BY A.created_at DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_out']);
			$diskon	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="'.$data->sistem('url_sis').'/dispro/out='.$uniq.'&cari#"><span class="badge badge-success"><i class="fa fa-tag"></i></span></a>' : '';
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_out'],['Active','Revised'])) ? '<a href="'.$data->sistem('url_sis').'/outlet/e/'.$hasil['id_out'].'"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active' && in_array($hasil['status_out'],['Active'])) ? ' <a href="#modal1" onclick="crud(\'outlet\', \'delete\', \''.$hasil['id_out'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$badgeStatus = ($hasil['status_out']==='Active') ? 'primary' : (($hasil['status_out']==='Need Revision') ? 'info' : 'warning');
			$approval = ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_out'],['Inactive','Revised'])) ? ' <a href="#modal1" onclick="crud(\'outlet\', \'updateStatusApprove\', \''.$hasil['id_out'].'\')" data-toggle="modal"><span class="badge badge-success"><i class="fa fa-check"></i></span></a>' : '';
			$uhpim	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_out'],['Active','Revised'])) ? '<a href="'.$data->sistem('url_sis').'/outlet/h/'.$hasil['id_out'].'"><span class="badge badge-secondary"><i class="fa fa-edit"></i>pim</span></a>' : '';
            $diskonPrinciple = ($data->akses($admin, $menu, 'A.update_status') === 'Active') 
                ? ' <a href="'.$data->sistem('url_sis').'/outlet/diskonprinciple/'.$hasil['id_out'].'">
                    <span class="badge badge-warning">
                        <i class="fa fa-percent"></i>
                    </span>
                  </a>' 
                : '';

			$legalOnly = ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_out'],['Active','Revised'])) ? '<a href="'.$data->sistem('url_sis').'/outlet/legal/'.$hasil['id_out'].'"><span class="badge badge-success"><i class="fa fa-edit"></i> Legal</span></a>' : '';

			$item = ($data->akses($admin, $menu, 'A.update_status') === 'Active' && in_array($hasil['status_out'], ['Active', 'Revised'])) 
			? '<a href="' . $data->sistem('url_sis') . '/outlet/i/' . $hasil['id_out'] . '"><span class="badge badge-primary"><i class="fa fa-edit"></i> Item</span></a>' 
			: '';
			$platform = ($data->akses($admin, $menu, 'A.update_status') === 'Active' && in_array($hasil['status_out'], ['Active', 'Revised'])) 
			? '<a href="' . $data->sistem('url_sis') . '/outlet/p/' . $hasil['id_out'] . '"><span class="badge badge-dark"><i class="fa fa-edit"></i>Platfond</span> </a>' 
			: '';
		
			$reject = ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_out'],['Inactive','Revised'])) ? ' <a href="#modal1" onclick="crud(\'outlet\', \'updateStatusReject\', \''.$hasil['id_out'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-times"></i></span></a>' : '';
			$approvalSPV = ($data->akses($admin, $menu, 'A.update_status')==='Active' && $hasil['status_out']==='Rekomendasi SPV') ? ' <a href="#modal1" onclick="crud(\'outlet\', \'updateStatusApproveSPV\', \''.$hasil['id_out'].'\')" data-toggle="modal"><span class="badge badge-success"><i class="fa fa-check-circle"></i> Approve</span></a>' : '';

			$tabel	.= '<tr><td><center>'.$no.'</center></td><td><span class="badge badge-'.$badgeStatus.'">'.strtoupper($hasil['status_out']).'</span></td><td><a href="'.$data->sistem('url_sis').'/outlet/v/'.$hasil['id_out'].'">'.$hasil['resmi_out'].'</a></td><td>'.$hasil['nama_out'].'</td><td>'.$hasil['nama_mg'].'</td><td>'.$hasil['diskon_odi'].'</td><td>'.$hasil['npwp_out'].'</td><td><center>'.$item.$diskonPrinciple.'</center></td><td><center>'.$legalOnly.$uhpim.$platform.$edit.$delete.$approval.$reject.$approvalSPV.'</center></td></tr>';
		}
		$navi	= $paging->myPaging($menu, $jumlah['total'], $maxi, $page); 
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>