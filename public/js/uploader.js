(function($) {
    $.fn.countTo = function(options) {
        // merge the default plugin settings with the custom options
        options = $.extend({}, $.fn.countTo.defaults, options || {});

        // how many times to update the value, and how much to increment the value on each update
        var loops = Math.ceil(options.speed / options.refreshInterval),
            increment = (options.to - options.from) / loops;

        return $(this).each(function() {
            var _this = this,
                loopCount = 0,
                value = options.from,
                interval = setInterval(updateTimer, options.refreshInterval);

            function updateTimer() {
                value += increment;
                loopCount++;
                $(_this).html(value.toFixed(options.decimals));

                if (typeof(options.onUpdate) == 'function') {
                    options.onUpdate.call(_this, value);
                }

                if (loopCount >= loops) {
                    clearInterval(interval);
                    value = options.to;

                    if (typeof(options.onComplete) == 'function') {
                        options.onComplete.call(_this, value);
                    }
                }
            }
        });
    };

    $.fn.countTo.defaults = {
        from: 0,  // the number the element should start at
        to: 100,  // the number the element should end at
        speed: 1000,  // how long it should take to count between the target numbers
        refreshInterval: 100,  // how often the element should be updated
        decimals: 0,  // the number of decimal places to show
        onUpdate: null,  // callback method for every time the element is updated,
        onComplete: null,  // callback method for when the element finishes updating
    };
})(jQuery);

$(function () {	
    $('#luaupload').fileupload({
        dataType: 'json',
        acceptFileTypes: /(\.|\/)(lua)$/i,
        formData: function (form) {
	        return form.serializeArray();
        },
        start: function (e, data) {
	        var $button = $(this).parent();
	        $button.addClass('disabled');
	        $('.fa-btn', $button).removeClass('fa-upload').addClass('fa-circle-o-notch').addClass('fa-spin');
	        $('span', $button).html('&nbsp;Uploading...');
	        $('.status.error').hide();
        },
        done: function (e, data) {
	        var $button = $(this).parent();
	        
            if (data.result.success) {
	            $button.removeClass('btn-primary').addClass('btn-warning');
	            $('span', $button).html('&nbsp;Processing');
	            $('.status').hide();
	            $('#luaupload').hide();
	            
	            $('#status-msg').show().html(data.result.msg);
	            
	            $('#upload-progress .progress-label').html('<span>0</span>%');
	            updateProgressbar(0, parseInt(data.result.total));
	            
	            if (data.result.reportURL) {
		            var reportPoll = function() {
						$.getJSON(data.result.reportURL, function(polldata) {
							updateProgressbar(polldata.current, polldata.total);
							
							if (polldata.current < polldata.total) {
								setTimeout(reportPoll, 2500);
							}
						});
					};
					
					reportPoll();
				}
            } else {
	            $('.status').hide();
				$('.status.error').show().html(data.result.errormsg);
				
				resetUploadButton($button);
            }
        },
        fail: function (e, data) {
            $('.status').hide();
            $('.status.error').show().html('There was an error uploading the file. Please try again.');
            
	        resetUploadButton($(this).parent());
        }
    });
});

function resetUploadButton($button) {	
    $button.removeClass('disabled');
    $('.fa-btn', $button).addClass('fa-upload').removeClass('fa-circle-o-notch').removeClass('fa-spin');
    $('span', $button).html($button.attr('data-default-text'));
}

function updateProgressbar(current, total, $button) {
	var $bar = $('.progress-bar', $('#upload-progress'));
	$('#upload-progress').show();
	
	var now = parseInt($bar.attr('aria-valuenow'));
	$bar.attr('aria-valuemax', total);
	var currentPct = $bar.attr('data-current-pct') ? parseInt($bar.attr('data-current-pct')) : 0;
	
	if (current > now) {
		$bar.attr('aria-valuenow', current);
		
		if (current && total) {
			var percent = Math.min(100, Math.round((current / total) * 100));
			$bar.attr('data-target-percent', percent);
			$bar.width(percent + '%');
			
			$('.progress-label span', $bar).countTo({
	            from: currentPct,
	            to: Math.round(percent),
	            speed: 1000,
	            refreshInterval: 50,
	            onComplete: function(value) {
	                $bar.attr('data-current-pct', Math.round(percent));
	            }
	        });
			
			if (current >= total) {
				var $button = $('#data-upload-button');
				$button.removeClass('btn-warning').addClass('btn-success');
		        $('span', $button).html('&nbsp;Complete');
		        $('.fa-btn', $button).addClass('fa-check').removeClass('fa-circle-o-notch').removeClass('fa-spin');
		        $('#status-msg').html('Import completed - ' + $bar.attr('aria-valuemax') + ' items processed.');
			}
		}
	}
}