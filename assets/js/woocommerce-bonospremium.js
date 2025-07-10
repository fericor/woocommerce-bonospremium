jQuery(document).ready(function($) {
    $('#btnBonosPremiumGenerarQR').click(function() {
        $("#imgBonosPremiumQR").attr("src", "/wp-content/plugins/woocommerce-bonospremium/assets/images/loading.gif");
        let ID = $(this).attr('data-id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_ajax_action',
                idProducto: ID, 
            },
            success: function(response) {
                $("#imgBonosPremiumQR").attr("src", response.imagen);
                console.log(response.imagen);
            },
        });
    });

    $('#btnPrintPdf').click(function() {
        let ID = $(this).attr('data-id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'my_ajax_action',
                idProducto: ID, 
            },
            success: function(response) {
                console.log(response);
            },
        });
    });

});