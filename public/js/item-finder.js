$(document).ready(function () {
	$('.form-control').on('change', function () {
		if ($(this).val()) {
			$(this).addClass('has-input');
		} else {
			$(this).removeClass('has-input');
		}
	});
	$('.form-control').trigger('change');
	
	$('select#source').on('change', function () {
		if ($('option:selected', $(this)).val()) {
			$('#only-this-source').removeClass('hidden');
		} else {
			$('#only-this-source').addClass('hidden');
			$('#only_selected_source').attr('checked', false);
		}
	}).trigger('change');
	
	$('select#zone').on('change', function () {
		var zoneID = $(this).val();
		
		toggleBossSelect(zoneID, false);
	});
});

function toggleBossSelect(zoneID, currentSelectedBoss) {	
	$('select#boss').html('');
	$('select#boss').append($('<option value="" data-keep="1">All Bosses</option>'));
	
	var bosses = (zoneID in bossesByZone) ? bossesByZone[zoneID] : false;
	
	if (zoneID && bosses) {
		$('select#boss').removeClass('hidden');
		
		for (var i in bosses) {
			var $option = $('<option />').val(bosses[i].id).html(bosses[i].name);
			
			if (currentSelectedBoss == bosses[i].id) {
				$option.attr('selected', true);
			}
			
			$('select#boss').append($option);
		}
	} else {
		$('select#boss').addClass('hidden');
	}
}