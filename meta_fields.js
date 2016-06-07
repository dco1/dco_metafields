jQuery(document).ready(function($) {
	// Color Picking Stuff	
    $('.color-picker input').each(function(){ 
        $this = $(this);
        $this.wpColorPicker({ 'clear' : function(e){ $this.attr('value', '');  }  }); 
    
    });
   	
	//Image Uploading
	var frame;
	
	$('.image-upload').on('click', function( event ){
	   	
		artinput   = $(this).parent().find('.image-upload'); /*grab the specific input*/
		artidinput = $(this).parent().find('.image-id'); /*grab the specific input*/
		
		event.preventDefault();
		
		// If the media frame already exists, reopen it.
		if ( frame ) {
			frame.open();
			return;
		}
				
		// Create the media frame.
		frame = wp.media({
			title: 'Select or Upload Media',
			button: {
				text:  'Use this Media',
			},
			multiple: false  // Set to true to allow multiple files to be selected
		});
		
		// When an image is selected, run a callback.
		frame.on( 'select', function() {
			// We set multiple to false so only get one image from the uploader
			attachment = frame.state().get('selection').first().toJSON();
			// Do something with attachment.id and/or attachment.url here
			// Send the attachment URL to our custom image input field.
			artinput.attr( 'src' , attachment.url).removeClass('default');
			// Send the attachment id to our hidden input
			artidinput.val( attachment.id ).change();;
		});
		
		// Finally, open the modal
		frame.open();
	});
   	
   	
   	// Date Picking
   	   	
   	$('input.date_input').each( function(){ $(this).datepicker(); }); 
   	$('input.time_input').each( function(){ $(this).timePicker({
        show24Hours: false,
        separator: ':',
        step: 15
    }); });
   	

});
