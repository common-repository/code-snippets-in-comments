(function ($) {
    $( document ).ready( function() {

        $( document ).on( 'click' , '#commenteditor span' , function() {

            $this = $( this );
            var prewrap = "<pre>";
            var lang = $($this).attr( 'data-id' );
            var lengthf = 5;

            if ( lang ) {

                prewrap = "<pre lang=\"" + lang + "\">";
                lengthf = $( $this ).attr( 'data-range' );

            }

            var content = $( 'textarea#comment' ).val();
            var input = document.getElementById( 'comment' );
            var start = input.selectionStart;
            var end = input.selectionEnd;

            if ( start != end ) {

                var focus = start + parseInt( lengthf ) + 1;
                var newContent = content.substr(0, start) + prewrap + "\n" + content.substr( start , ( end - start ) ) + "\n</pre>" + content.substr( end );

            } else {

                var position = $( "textarea#comment" ).getCursorPosition();
                var newContent = content.substr( 0 , position ) + prewrap + "</pre>" + content.substr( position );
                var focus = start + parseInt(lengthf);

            }

            $( "textarea#comment" ).val( newContent ).focus().selectRange( focus );

        });
    });
}(jQuery));