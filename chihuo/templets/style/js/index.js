
tabHover('#cptj_m li','#cptj_l>div');
tabHover('#tab1 li','#tablist1>ol');
tabHover('#tab2 li','#tablist2>ol');
tabHover('#rdtab span','#rdlist>div');

$(".ranklist li").hover(function(){
	$(this).addClass("li_cur").siblings().removeClass("li_cur");
})
$("#j_pic li").hover(function(){
	$(this).find('i').show();
},function(){
	$(this).find('i').hide();
})




$("#focuspic").Slide({
	effect : "fade",
    speed : "normal",
    timer : 3000
});
$("#zxfb").Slide({
    effect : "scroolLoop",
    speed : "normal",
	autoPlay: false,
    timer : 3000,
    steps:6
});