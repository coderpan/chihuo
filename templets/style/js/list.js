$('#con_print').click(function(){
	window.print();
});
$("#temp1").Slide({
	effect : "fade",
    speed : "slow",
    timer : 5000
});
$("#temp2").Slide({
    effect : "scroolLoop",
    autoPlay:false,
    speed : "normal",
    timer : 3000,
    steps:1
});
tabHover('#ctab li','#ctablist>div');