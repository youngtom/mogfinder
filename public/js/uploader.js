var newAppearances = 0;

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
	        newAppearances = 0;
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
	            newAppearances = parseInt(data.result.new);
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
	
	if (current > now) {
		$bar.attr('aria-valuenow', current);
		
		if (current && total) {
			var percent = Math.min(100, Math.round((current / total) * 100));
			$bar.attr('data-target-percent', percent);
			$bar.width(percent + '%');
			$('.progress-label span', $bar).html(Math.round(percent));
			
			if (current >= total) {
				var $button = $('#data-upload-button');
				$button.removeClass('btn-warning').addClass('btn-success');
		        $('span', $button).html('&nbsp;Complete');
		        $('.fa-btn', $button).addClass('fa-check').removeClass('fa-circle-o-notch').removeClass('fa-spin');
		        $('#status-msg').html('Import completed - ' + $bar.attr('aria-valuemax') + ' appearances processed. ' + newAppearance + ' new appearances added.');
			}
		}
	}
}