jQuery(function($) {
    $('[data-toggle="tooltip"]').tooltip();

    // Update fallback toggle based on Zoho enabled
    function updateFallbackState() {
        var zohoEnabled = $('#zoho_enabled_toggle').is(':checked');
        if (!zohoEnabled) {
            $('#zoho_fallback_toggle').prop('checked', false).prop('disabled', true);
            // also add a disabled class to its label
            $('#zoho_fallback_toggle').closest('label').addClass('disabled');
            $.post(zohoMailer.ajaxUrl, { option: 'zoho_fallback', value: 0 });
        } else {
            $('#zoho_fallback_toggle').prop('disabled', false);
            // also add a disabled class to its label
            $('#zoho_fallback_toggle').closest('label').removeClass('disabled');
        }
    }

    $('#zoho_enabled_toggle').change(function() { 
        var enabled = $(this).is(':checked') ? 1 : 0;
        $.post(zohoMailer.ajaxUrl, { option: 'zoho_enabled', value: enabled }, function(response) {
            response = JSON.parse(response);
            if (response.success) {
                alert_float(enabled ? 'success' : 'warning', 'Zoho Mailer ' + (enabled ? 'Enabled' : 'Disabled'));
                updateFallbackState();
            } else {
                alert_float('danger', response.message || 'Failed to update Zoho setting');
            }
        });
    });

    $('#zoho_fallback_toggle').change(function() {
        var fallback = $(this).is(':checked') ? 1 : 0;
        $.post(zohoMailer.ajaxUrl, { option: 'zoho_fallback', value: fallback }, function(response) {
            response = JSON.parse(response);
            if (response.success) {
                alert_float(fallback ? 'success' : 'warning', 'Zoho Fallback ' + (fallback ? 'Enabled' : 'Disabled'));
            } else {
                alert_float('danger', response.message || 'Failed to update Fallback setting');
            }
        });
    });

    $(document).ready(updateFallbackState);
});

// Copy to clipboard
function copyToClipboard(value, button) {
    var tempInput = document.createElement('input');
    tempInput.value = value;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);

    var original = $(button).html();
    $(button).html('<i class="fa fa-check"></i> Copied!').prop('disabled', true);
    setTimeout(function() {
        $(button).html(original).prop('disabled', false);
    }, 1500);
}
