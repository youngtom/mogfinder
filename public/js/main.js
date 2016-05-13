$(document).ready(function () {
	$('.subnav').affix({
		offset: {
			top: 10,
		}
	});
	
	affixWidth();
	
	$('[data-toggle="tooltip"]').tooltip({ html: true });
	
	var searchResults = new Bloodhound({
		datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
		queryTokenizer: Bloodhound.tokenizers.whitespace,
		remote: {
			url: $('#search-form').attr('action') + '?q=%QUERY&json=1',
			wildcard: '%QUERY'
		}
	});
	
	$('#search-form .typeahead').typeahead(null, {
		name: 'results',
		display: 'value',
		minLength: 3,
		highlight: true,
		source: searchResults,
		limit: 10,
		templates: {
			suggestion: function (data) {
				return '<a href="' + data.link + '"><span class="' + data.type + '-link ' + data.linkClass + '">' + data.value + '</span> <span class="result-type">' + data.type + '</span></a>';
			}
		}
	});
	
	$('#search-form .typeahead').bind('typeahead:select', function(ev, suggestion) {
		if (suggestion.link) {
			location.replace(link);
		} else {
			return true;
		}
	});
});

function affixWidth(){
	var width = Number($('.subnav').closest("*[class^='col']").width());
	$('.subnav').css({"width": width});
}

$(window).on('resize scroll', function(){
	affixWidth();
});