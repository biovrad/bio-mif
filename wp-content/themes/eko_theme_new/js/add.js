var class_arr =  new Array("first");
var logo1 = "Био";
var logo2 = "Миф";
$(document).ready( function(){
/*
	add class to menu
*/
	$("#menu li ").each(function (i,domEle) {
		 $(domEle).children("a").addClass(class_arr[0]);
	});
	$('ul.sub_menu li a').removeClass("first");

/*
	for menu work
*/	
	$("#menu li a.first").toggle(
      function () {
        $(this).next("ul").slideDown("fast");
      },
      function () {
  			$(this).next("ul").slideUp("fast");
      }
    );
 /*
	change works
*/	   
	$.get("wp-content/themes/eko_theme_new/js/word.xml", function(data){
 		     	var html = "<ul class='slider'>";
				$(data).find('word').each(function(){
				 html +="<li>" +$(this).text()+ "</li>";
				});
				 html +="</ul>";
				 $('div.changer').prepend(html);
				 add_cycle();  
	});
 /*
	logo works
*/	   	
	$("div.logotip").prepend("<span>"+ logo1 +"</span><span>"+ logo2 +"</span>");

});
if($("div.logotip")){
	function add_cycle(){
		$("div.logotip").cycle({
			fx:'curtainX',       
			delay: -7000        
		});
		
		$("ul.slider").cycle({
			fx:'fadeZoom',       
			delay: -800        
		});
	}
}
