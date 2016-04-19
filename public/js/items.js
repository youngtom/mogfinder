var selectedClass = false, selectedFaction = false, selectedSource = false;

$(document).ready(function () {
	$('.collected-toggle-btn').on('click', function () {
		var collected = parseInt($(this).attr('data-collected'));
		
		if ($(this).hasClass('btn-primary')) {
			$(this).removeClass('btn-primary');
			$(this).addClass('btn-default');
			
			if (collected) {
				$('.item-display-group').addClass('hide-collected');
			} else {
				$('.item-display-group').addClass('hide-uncollected');
			}
		} else {
			$(this).addClass('btn-primary');
			$(this).removeClass('btn-default');
			
			if (collected) {
				$('.item-display-group').removeClass('hide-collected');
			} else {
				$('.item-display-group').removeClass('hide-uncollected');
			}
		}
		
		resetScroll();
	});
	
	$('.quest-toggle-btn').on('click', function () {
		if ($(this).hasClass('btn-primary')) {
			$(this).removeClass('btn-primary');
			$(this).addClass('btn-default');
			$('.item-row[data-quest-item="1"]').addClass('quest-filtered');
			
			$('.item-display-panel').each(function () {
				var $validRows = $('.item-row:not(.quest-filtered)', $(this));
				
				if ($validRows.length <= 1) {
					$(this).addClass('quest-filtered');
				}
			});
		} else {
			$(this).addClass('btn-primary');
			$(this).removeClass('btn-default');
			$('.item-row').removeClass('quest-filtered');
			$('.item-display-panel').removeClass('quest-filtered');
		}
		
		resetScroll();
	});
	
	$('.selectable-filter .dropdown-menu li a').on('click', function () {
		var $dropDown = $(this).closest('.selectable-filter');
		
		$('.dropdown-menu li:not(.all-select)', $dropDown).show();
		
		if ($(this).hasClass('show-all')) {
			$dropDown.addClass('all-selected');
			$('.selected-value', $dropDown).html('All');
			$('.btn.dropdown-toggle', $dropDown).attr('data-class', '');
			$('.btn.dropdown-toggle', $dropDown).attr('data-faction', '');
		} else {
			$dropDown.removeClass('all-selected');
			$('.selected-value', $dropDown).html($(this).html());
			$(this).parent().hide();
			$('.btn.dropdown-toggle', $dropDown).attr('data-class', $(this).attr('data-class-code'));
			$('.btn.dropdown-toggle', $dropDown).attr('data-faction', $(this).attr('data-faction-code'));
		}
	});
	
	$('.class-filter .dropdown-menu li a').on('click', function (e, manual) {
		var classID = parseInt($(this).attr('data-class-id'));
		selectedClass = classID ? $(this).attr('data-class-code') : false;
		
		filterClassItems(classID);
		
		if (classID) {
			$('.panel-group').addClass('class-filtered');
			$('.btn.dropdown-toggle', $(this).parents('.class-filter')).removeClass('btn-primary').addClass('btn-default');
		} else {
			$('.panel-group').removeClass('class-filtered');
			$('.btn.dropdown-toggle', $(this).parents('.class-filter')).addClass('btn-primary').removeClass('btn-default');
		}
		
		if (!manual) {			
			$('.class-filter .dropdown-toggle').dropdown("toggle");
			
			updateURL();	
		}
		return false;
	});
	
	$('.faction-filter .dropdown-menu li a').on('click', function (e, manual) {
		var factionMask = parseInt($(this).attr('data-faction-mask'));
		selectedFaction = factionMask ? $(this).attr('data-faction-code') : false;
		
		filterFactionItems(factionMask);
		
		if (factionMask) {
			$('.panel-group').addClass('faction-filtered');
			$('.btn.dropdown-toggle', $(this).parents('.faction-filter')).removeClass('btn-primary').addClass('btn-default');
		} else {
			$('.panel-group').removeClass('faction-filtered');
			$('.btn.dropdown-toggle', $(this).parents('.faction-filter')).addClass('btn-primary').removeClass('btn-default');
		}
		
		if (!manual) {			
			$('.faction-filter .dropdown-toggle').dropdown("toggle");
			
			updateURL();	
		}
		return false;
	});
	
	$('.source-filter .dropdown-menu li a').on('click', function (e, manual) {
		var sourceID = $(this).attr('data-source-id');
		selectedSource = sourceID ? $(this).attr('data-source-code') : false;
		
		filterSourceItems(sourceID);
		
		if (sourceID != '0') {
			$('.panel-group').addClass('source-filtered');
			$('.source-filter .dropdown-toggle').addClass('btn-success').removeClass('btn-default');
		} else {
			$('.panel-group').removeClass('source-filtered');
			$('.source-filter .dropdown-toggle').addClass('btn-default').removeClass('btn-success');
		}
		
		if (!manual) {			
			$('.source-filter .dropdown-toggle').dropdown("toggle");
			
			updateURL();	
		}
		return false;
	});
	
	$('.character-filter .dropdown-menu li a').on('click', function () {
		var charID = parseInt($(this).attr('data-character-id'));
		filterCharacterItems(charID);
		
		if (charID) {
			$('.panel-group').addClass('chaaracter-filtered');
		} else {
			$('.panel-group').removeClass('character-filtered');
		}
		
		$('.character-filter .dropdown-toggle').dropdown("toggle");
		
		return false;
	});
	
	if (window.location.hash) {
		var urlArr = window.location.hash.replace('#', '').split(';');
		var urlObj = {};
		var a;
		
		for (i = 0; i < urlArr.length; i++) {
			if (urlArr[i].indexOf(':')) {
				a = urlArr[i].split(':');
				urlObj[a[0]] = a[1];
			}
		}
		
		if ('class' in urlObj) {
			$('.class-filter .dropdown-menu li a[data-class-code="' + urlObj.class + '"]').trigger('click', [true]);
		}
		
		if ('faction' in urlObj) {
			$('.faction-filter .dropdown-menu li a[data-faction-code="' + urlObj.faction + '"]').trigger('click', [true]);
		}
		
		if ('source' in urlObj) {
			$('.source-filter .dropdown-menu li a[data-source-code="' + urlObj.source + '"]').trigger('click', [true]);
		}
	}
	
	$.getScript('//wow.zamimg.com/widgets/power.js');
});

function filterClassItems(classID) {
	$('.item-row').removeClass('invalid-class');
	
	if (classID) {
		var classMask = Math.pow(2, classID);
		
		$('.item-row[data-classmask!="0"]').each(function () {
			var itemMask = parseInt($(this).attr('data-classmask'));
			
			if ((classMask & itemMask) == 0) {
				$(this).addClass('invalid-class');
			}
		});
	}
		
	updateItemDisplayPanels(true);
}

function filterFactionItems(factionMask) {
	$('.item-row').removeClass('invalid-faction');
	
	if (factionMask) {
		$('.item-row[data-racemask!="0"]').each(function () {
			var itemMask = parseInt($(this).attr('data-racemask'));
			
			if ((itemMask & factionMask) == 0) {
				$(this).addClass('invalid-faction');
			}
		});
	}
		
	updateItemDisplayPanels(true);
}

function filterCharacterItems(charID) {
	$('.item-display-panel').removeClass('filtered');
	
	if (charID) {
		$('.item-display-panel').each(function () {
			if (!$('.item-row[data-character-id="' + charID + '"]', $(this)).length) {
				$(this).addClass('filtered');
			}
		});
	}
}

function filterSourceItems(sourceID) {
	$('.item-row').removeClass('invalid-source');
	
	if (sourceID != '0') {
		$('.item-row[data-sources!="0"]').each(function () {
			if (($(this).attr('data-sources') != sourceID) && (parseInt($.inArray(sourceID, $(this).attr('data-sources').split('|'))) < 0)) {
				$(this).addClass('invalid-source');
			}
		});
	}
		
	updateItemDisplayPanels(false);
}

function updateItemDisplayPanels(updateCollected) {
	$('.item-display-panel').each(function () {
		var $validRows = $('.item-row:not(.invalid-class,.invalid-faction,.invalid-race,.invalid-character,.invalid-source)', $(this));
		var $priorityRows = $('.item-row.priority', $(this));
		
		if ($validRows.length) {
			$(this).removeClass('filtered');
			
			if ($priorityRows.length) {
				$('.display-item-link', $(this)).html($('.itemname', $priorityRows.first()).html());
			} else {
				$('.display-item-link', $(this)).html($('.itemname', $validRows.first()).html());
			}
			
			var numItems = $priorityRows.length + $('.item-row:not(.invalid-class,.invalid-faction,.invalid-race,.invalid-character,.invalid-source,.priority)', $(this)).length;
			
			if (numItems > 1) {
				var addlStr = '(and ' + String(numItems - 1) + ' other';
				addlStr = (numItems > 2) ? addlStr + 's)' : addlStr + ')';
				$('.num-addl-items', $(this)).show().html(addlStr);
			} else {
				$('.num-addl-items', $(this)).hide();
			}
		} else {
			$(this).addClass('filtered');
		}
		
		if (updateCollected) {
			if ($('.item-row[data-item-collected="1"]:not(.invalid-class,.invalid-faction,.invalid-race)', $(this)).length) {
				$(this).attr('data-display-collected', 1);
			} else {
				$(this).attr('data-display-collected', 0);
			}
		}
	});
	
	$('.collected-count').html($('.item-display-panel[data-display-collected="1"]:not(.filtered)').length);
	$('.uncollected-count').html($('.item-display-panel[data-display-collected="0"]:not(.filtered)').length);
	
	resetScroll();
}

function resetScroll() {
	/*
	var $activePanel = $('.item-display-panel:not(.filtered) .panel-heading:not(.collapsed)');
	var offset = ($activePanel.length) ? $activePanel.scrollTop() : 0;
	$(window).scrollTop(offset);
	*/
	$('.panel-collapse').collapse('hide');
	$(window).scrollTop(0);
}

function updateURL() {
	var urlArr = [];
	
	if (selectedClass) {
		urlArr.push('class:' + selectedClass);
	}
	
	if (selectedFaction) {
		urlArr.push('faction:' + selectedFaction);
	}
	
	if (selectedSource) {
		urlArr.push('source:' + selectedSource);
	}
	
	if (urlArr.length) {
		window.location.hash = urlArr.join(';');
	} else {
		window.location.hash = '';
	}
}