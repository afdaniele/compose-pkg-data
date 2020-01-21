<div class="modal fade modal-vertical-centered" id="open-entry-modal">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">

			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<h4 style="margin:0 4px 0 4px"><span class="fa fa-code" aria-hidden="true"></span>
                Entry: <span id="key"></span>
                </h4>
			</div>

			<div class="modal-body" style="height: 400px">
				<div class="progress">
					<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="margin-bottom:0; width:100%">
					</div>
				</div>

                <div class="entry_content" style="display: none">
                    <pre style="height: 370px">
                    </pre>
                </div>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>

		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<script type="text/javascript">

	$('#open-entry-modal').on('show.bs.modal', function(e){
	    let key = $(e.relatedTarget).data('key');
	    smartAPI('data', 'get', {
	        'arguments': {
	            'database': '<?php echo $db_name ?>',
                'key': key
            },
            'on_success': function(res){
	            // update header
                $('#open-entry-modal .modal-header #key').html(key);
	            // hide progress bar
                $('#open-entry-modal .modal-body .progress').css('display', 'none');
                // dump entry value into a div
                $('#open-entry-modal .modal-body .entry_content pre').html(JSON.stringify(res['data']['value'], null, 2));
                // show content
                $('#open-entry-modal .modal-body .entry_content').css('display', 'inherit');
            },
            'block': false,
            'quiet': true
        });
	});

	$('#open-entry-modal').on('hide.bs.modal', function(){
        // clear content
        $('#open-entry-modal .modal-body .entry_content pre').empty();
        // hide content
        $('#open-entry-modal .modal-body .progress').css('display', 'none');
        // show progress bar
        $('#open-entry-modal .modal-body .progress').css('display', 'inherit');
	});

</script>
