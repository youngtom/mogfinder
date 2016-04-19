$(document).ready(function () {
	$('select#cat').on('change', function () {
		filterClassSelect($('option:selected', $(this)).attr('data-classmask'));
		
		var catID = $(this).val();
		var currentClassmask = ($('select#class').val()) ? Math.pow(2, parseInt($('select#class').val())) : false;
		
		toggleSlotSelect(catID, currentClassmask, false);
	});
	
	$('select#slot').on('change', function () {
		filterClassSelect($('option:selected', $(this)).attr('data-classmask'));
	});
	
	$('select#class').on('change', function () {
		filterSlotSelect($('option:selected', $(this)).attr('data-classmask'));
	}).trigger('change');
	
	$.getScript('//wow.zamimg.com/widgets/power.js');
});

function filterClassSelect(filterMask) {
	filterMask = parseInt(filterMask);
	var curVal = $('select#class').val();
	
	$('select#class option[data-keep!="1"]').remove();
	
	for (var i in classes) {
		var classMask = Math.pow(2, classes[i].id);
		
		if (!filterMask || (filterMask & classMask) != 0) {
			var $option = $('<option />').val(classes[i].id).html(classes[i].name).attr('data-classmask', classMask);
			
			if (curVal == classes[i].id) {
				$option.attr('selected', true);
			}
			
			$('select#class').append($option);
		}
	}
}

function filterSlotSelect(filterMask) {
	filterMask = parseInt(filterMask);
	var curCatVal = $('select#cat').val();
	var curSlotVal = $('select#slot').val();
	
	$('select#cat option[data-keep!="1"]').remove();
	
	for (var i in categories) {
		if (!filterMask || !categories[i].classmask || (filterMask & categories[i].classmask) != 0) {
			var $option = $('<option />').val(categories[i].id).html(categories[i].label).attr('data-classmask', categories[i].classmask);
			
			if (curCatVal == categories[i].id) {
				$option.attr('selected', true);
			}
			
			$('select#cat optgroup[data-group="' + categories[i].group + '"]').append($option);
		}
	}
	
	toggleSlotSelect(curCatVal, filterMask, curSlotVal);
}

function toggleSlotSelect(catID, currentClassmask, currentSelectedSlot) {	
	$('select#slot').html('');
	$('select#slot').append($('<option value="" data-keep="1">All</option>'));
	
	if (catID) {
		$('select#slot').removeClass('hidden');
		
		var slots = (catID in mogslots) ? mogslots[catID] : false;
		
		if (slots) {
			for (var i in slots) {
				if (!currentClassmask || !slots[i].allowed_class_bitmask || (currentClassmask & slots[i].allowed_class_bitmask) != 0) {
					var $option = $('<option />').val(slots[i].id).html(slots[i].simple_label).attr('data-classmask', slots[i].allowed_class_bitmask)
					
					if (currentSelectedSlot == slots[i].id) {
						$option.attr('selected', true);
					}
					
					$('select#slot').append($option);
				}
			}
		}
	} else {
		$('select#slot').addClass('hidden');
	}
}