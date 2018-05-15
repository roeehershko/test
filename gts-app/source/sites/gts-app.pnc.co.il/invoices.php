<?php

    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

    require('config.inc.php');
    require('core.inc.php');

    if (!connectDB($connectDB__error)) {
        die($connectDB__error);
    }

    //if ($trans = mysql_fetch_object(mysql_query("SELECT merchant_id, trans_id, timestamp, type, amount, currency FROM trans WHERE trans_id = '" . mysql_real_escape_string($_GET['trans_id']) . "' AND status IN ('completed', 'pending')"))) {
    if ($trans = mysql_fetch_object(mysql_query("SELECT merchant_id, trans_id, timestamp, type, amount, currency FROM trans WHERE trans_id = '" . mysql_real_escape_string($_GET['trans_id']) . "'"))) {
        if ($trans->invoice_data = mysql_fetch_object(mysql_query("SELECT number, customer_name, customer_number, address_street, address_city, address_zip, phone, description, gyro_details FROM trans_invoices WHERE trans_id = '" . $trans->trans_id . "'"))) {
            $trans->invoice_data->link = 'https://'.constant('GTS_APP_HOST').'/invoices/' . authInvoice($trans->trans_id) . '/' . $trans->trans_id . '.pdf';
        }
        
        if ($trans->type == 'credit') {
            $trans->credit_data = mysql_fetch_object(mysql_query("SELECT cc_holder_name, cc_last_4, cc_exp, cc_type, credit_terms, payments_number, authorization_number, voucher_number, reference_number FROM trans_credit WHERE trans_id = '" . $trans->trans_id . "'"));
        } else if ($trans->type == 'check') {
            $trans->check_data = mysql_fetch_object(mysql_query("SELECT check_number, bank_number, branch_number, account_number FROM trans_check WHERE trans_id = '" . $trans->trans_id . "'"));
        }
    }

    if (authInvoice($_GET['trans_id']) != $_GET['auth'] || !$trans || !$trans->invoice_data) {
        header('HTTP/1.0 401 Unauthorized');
        echo 'Access denied; invalid invoice.';
        exit;
    }

    if ($merchant = mysql_fetch_object(mysql_query("SELECT merchant_id, username, processor, shva_transactions_merchant_number, company_id, company_name, company_email, invoices_template, note, params FROM merchants WHERE merchant_id = '" . $trans->merchant_id . "' AND `terminated` = '0'"))) {
        $merchant->params = unserialize($merchant->params);
        $merchant->params->note = $merchant->note;
        
        if (!file_exists('invoices-templates/' . $merchant->invoices_template . '.inc.php')) {
            header('HTTP/1.0 401 Unauthorized');
            echo 'Access denied; invoices template not found.';
            exit;
        }
    } else {
        header('HTTP/1.0 401 Unauthorized');
        echo 'Access denied; invoices disabled.';
        exit;
    }

    if (!$trans->invoice_data->number) {
        if ($invoice_number = generateInvoice($trans->trans_id)) {
            $trans->invoice_data->number = $invoice_number;
        } else {
            header('HTTP/1.0 401 Unauthorized');
            echo 'Access denied; invalid invoice.';
            exit;
        }
    }

    $sql_query = mysql_query("SELECT name, value FROM trans_params WHERE trans_id = '" . $trans->trans_id . "' AND private = '0'");
    while ($sql = mysql_fetch_assoc($sql_query)) {
        $trans->params->{$sql['name']} = $sql['value'];
    }

    if ($_GET['mode'] == 'pdf') {
        $tmpfname = tempnam('/tmp', 'PDF');
        file_put_contents($tmpfname, getHTMLInvoice($merchant, $trans));
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $trans->trans_id . '.pdf');
        
        passthru("xhtml2pdf -q " . escapeshellarg($tmpfname) . " -");
        
        unlink($tmpfname);
        exit;
    } else {
        header('Content-Type: text/html; charset=UTF-8');
        
        echo getHTMLInvoice($merchant, $trans);
    }

    function getHTMLInvoice($merchant, $trans) {
        ob_start();
        localscopeinclude('invoices-templates/' . $merchant->invoices_template . '.inc.php', array(
            'merchant' => $merchant,
            'trans' => $trans
        ));
        return ob_get_clean();
    }

?>