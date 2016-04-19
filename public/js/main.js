$(document).ready(function () {
	$('.subnav').affix({
		offset: {
			top: 10,
		}
	});
	
	affixWidth();
	
	$('[data-toggle="tooltip"]').tooltip({ html: true });
});

function affixWidth(){
	var width = Number($('.subnav').closest("*[class^='col']").width());
	$('.subnav').css({"width": width});
}

$(window).on('resize scroll', function(){
	affixWidth();
});