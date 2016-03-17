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
	        $('span', $button).html('Uploading...');
        },
        done: function (e, data) {
	        var $button = $(this).parent();
	        
            if (data.result.success) {
	            $button.removeClass('btn-primary').addClass('btn-success');
	            $('span', $button).html('Processing');
	            $('.status').hide();
	            
	            updateProgressbar($('#upload-progress'), 0, parseInt(data.result.total));
	            
	            if (data.result.reportURL) {
		            var reportPoll = function() {
						$.getJSON(data.result.reportURL, function(polldata) {
							updateProgressbar($('#upload-progress'), polldata.current, polldata.total);
							
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

function updateProgressbar($progress, current, total) {
	var $bar = $('.progress-bar', $progress);
	$progress.show();
	
	var now = parseInt($bar.attr('aria-valuenow'));
	$bar.attr('aria-valuemax', total);
	
	if (current > now) {
		$bar.attr('aria-valuenow', current);
		
		if (current && total) {
			var percent = (current / total) * 100;
			$bar.width(percent + '%');
		}
	}
}