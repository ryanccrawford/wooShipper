if( typeof woopro_si_settings.no_frame != 'undefined' && woopro_si_settings.no_frame == 1 ) {
    if (top.location != self.location) {
        top.location = self.location.href
    }
}
jQuery(document).ready(function() {
    jQuery('.woopro_si_cbox').unbind("click.prettyphoto");
    jQuery('.woopro_si_cbox').colorbox({
        href : function() {
            var url = jQuery(this).data('src');
            return ( url != '' ? url : jQuery( this ).attr('href') );
        },
        maxWidth : '80%',
        maxHeight : '80%'
    });
});