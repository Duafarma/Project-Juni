function cekcari(kata){
	var cari	= (kata=='') ? '' : kata.split(' ').join('-');
	return cari;
}

function timpain(unik, kata, simbol){
	var cari	= (kata=='') ? 'all' : kata.split(unik).join(simbol);
	return cari;
}