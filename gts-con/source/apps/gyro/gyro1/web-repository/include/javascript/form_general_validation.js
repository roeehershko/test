var submission_in_progress = 0;

function validate(node) {

    // Variables
    validation = 1;
    
    // Validating the general fields automatically using the REPO form_fields_validation function
    field_error = form_fields_validation(node);
    
    if (field_error == 0) {
        // If all is okay, we remove the error alert, if exists
        document.getElementById('error__general').style.display = 'none';
    } else {
        // Displaying the client side error (the one at the top of the page)
        document.getElementById('error__general').style.display = '';
        validation = 0;
    }
    
    // Removing Server Side errors and notes, if exist
    if (document.getElementById('server_side_notes')) {
        document.getElementById('server_side_notes').style.display='none';
    }
    if (document.getElementById('server_side_errors')) {
        document.getElementById('server_side_errors').style.display='none';
    }

    // Validation Conclusion
    if (validation == 0) {
        
        document.getElementById('server_side_errors').style.display = 'none';
        document.getElementById('client_side_errors').style.display = '';	
        window.location='#errors_anchor';
        return false;
        
    } else {
        
        document.getElementById('server_side_errors').style.display = 'none';
        document.getElementById('client_side_errors').style.display = 'none';
        
        if (submission_in_progress == 0) {
            submission_in_progress = 1;
            document.getElementById('error__submission_in_progress').style.display = 'none';
            return true;
        } else {
            document.getElementById('client_side_errors').style.display = '';
            document.getElementById('error__submission_in_progress').style.display = '';
            window.location='#errors_anchor';
            return false;
        }
    }
    
}
