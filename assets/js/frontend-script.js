// Real Estate Sales Frontend Script
// This file is loaded for all frontend pages but most functionality
// is included directly in the frontend dashboard shortcode

jQuery(document).ready(function($) {
    // Basic frontend functionality
    console.log('RES Frontend Script Loaded');
    
    // Add any global frontend JavaScript here if needed in the future
    
    // Example: Global form validation
    $('.res-frontend-form').on('submit', function(e) {
        var hasErrors = false;
        
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                hasErrors = true;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Global AJAX error handler
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.responseJSON && xhr.responseJSON.data) {
            console.error('AJAX Error:', xhr.responseJSON.data);
        } else {
            console.error('AJAX Error:', thrownError);
        }
    });
});