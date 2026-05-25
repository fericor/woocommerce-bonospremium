jQuery(document).ready(function($) {
    // Función para actualizar saldo via AJAX
    function updateWalletBalance() {
        $.ajax({
            url: bono_wallet_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_bono_wallet_balance',
                nonce: bono_wallet_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar saldo en la página si existe
                    $('.bono-balance-amount').text(response.data.saldo + ' €');
                    $('.bono-wallet-amount-short').text(response.data.saldo + ' €');
                }
            }
        });
    }
    
    // Actualizar saldo cada 30 segundos en páginas relevantes
    if ($('.bono-wallet-container').length || $('.bono-wallet-checkout-option').length) {
        setInterval(updateWalletBalance, 30000);
    }
    
    // Manejar checkout updates
    $(document.body).on('updated_checkout', function() {
        // Recalcular si hay wallet en uso
        if ($('#use_bono_wallet').is(':checked')) {
            bonoCalculateFinalTotal();
        }
    });
});

// Funciones globales para uso en checkout
function bonoSetWalletAmount(amount) {
    jQuery('#bono_wallet_amount').val(amount).trigger('change');
}

function bonoCalculateFinalTotal() {
    const cartTotal = parseFloat(jQuery('#bonoCartTotal').text().replace(' €', '').replace('.', '').replace(',', '.'));
    const walletAmount = parseFloat(jQuery('#bono_wallet_amount').val()) || 0;
    const finalTotal = cartTotal - walletAmount;
    
    jQuery('#bonoFinalTotal').text(finalTotal.toFixed(2).replace('.', ',') + ' €');
}