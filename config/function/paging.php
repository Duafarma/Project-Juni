<?php
	class Paging extends DB{
		function getHal($hal){
			$nilai	= $hal ? $hal : 1;
			return $nilai;
		}

		function getPage($page, $halaman){
			$nilai	= ($page>1) ? ($page * $halaman) - $halaman : 0;
			return $nilai;
		}

		function myPaging($menu, $total, $maxi, $page) {
			$pages	= ceil($total / $maxi);
			$prev	= ((($page-2)<=1) ? 1 : ($page - 2));
			$next	= ((($page+2)>=$pages) ? ($page + ($pages - $page)) : ($page + 2));
			$navi	= '<li class="page-item"><a class="page-link page-link-icon" onclick="viewdata(\''.$menu.'\', '.$maxi.', 1)" href="#"><i class="fa fa-angle-double-left"></i></a></li>';
			for($a=$prev; $a<$page; $a++){
				$navi	.= '<li class="page-item"><a class="page-link" onclick="viewdata(\''.$menu.'\', '.$maxi.', '.$a.')" href="#">'.$a.'</a></li>';
			}
			$navi	.= '<li class="page-item active"><a class="page-link" onclick="viewdata(\''.$menu.'\', '.$maxi.', '.$page.')" href="#">'.$page.'</a></li>';
			for ($i=$page + 1; $i<=$next; $i++){
				$navi	.= '<li class="page-item"><a class="page-link" onclick="viewdata(\''.$menu.'\', '.$maxi.', '.$i.')" href="#">'.$i.'</a></li>';
			}
			$navi	.= '<li class="page-item"><a class="page-link page-link-icon" onclick="viewdata(\''.$menu.'\', '.$maxi.', '.$pages.')" href="#"><i class="fa fa-angle-double-right"></i></a></li>';
			return $navi;
		}
	}
?>