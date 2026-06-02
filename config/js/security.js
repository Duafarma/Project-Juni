/**
* Disable right-click of mouse, F12 key, and save key combinations on page
* By Arthur Gareginyan (arthurgareginyan@gmail.com)
* For full source code, visit https://mycyberuniverse.com
window.onload = function(){


};
*/

$(document).keydown(function(e){
	/*
	$('#Selector').bind('copy paste', function(e) {
        e.preventDefault();
    });
	*/
	//var huruf	= event.charCode || event.keyCode;
	//if (ctrl && (key == 67 || key == 86 || key == 88 || key == 73 || key == 74 || key == 86 || key == 123)) { 
	var ctrl	= event.ctrlKey;
	var huruf	= String.fromCharCode(event.keyCode).toLowerCase();
	if (ctrl && (huruf=='c' || huruf=='x' || huruf=='v' || huruf=='s')) {
		event.preventDefault();
		//event.returnValue = false;
	}	

});

document.addEventListener("contextmenu", function(e){
	e.preventDefault();
}, false);