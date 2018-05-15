<?php

class User extends GyroDoc {
    protected $data = array(
        'type' => array('type' => 'string', 'value' => null, 'protected' => true), /* deprecated */
        'first_name' => array('type' => 'string', 'value' => null),
        'last_name' => array('type' => 'string', 'value' => null),
        'email' => array('type' => 'string', 'value' => null),
        'password' => array('type' => 'string', 'value' => null),
        'company' => array('type' => 'string', 'value' => null),
        'job_title' => array('type' => 'string', 'value' => null),
        'date_of_birth' => array('type' => 'date', 'value' => null),
        'street_1' => array('type' => 'string', 'value' => null),
        'street_2' => array('type' => 'string', 'value' => null),
        'city' => array('type' => 'string', 'value' => null),
        'state' => array('type' => 'string', 'value' => null),
        'zipcode' => array('type' => 'string', 'value' => null),
        'country' => array('type' => 'string', 'value' => null),
        'phone_1' => array('type' => 'string', 'value' => null),
        'phone_2' => array('type' => 'string', 'value' => null),
        'mobile' => array('type' => 'string', 'value' => null),
        'seo_title' => array('type' => 'string', 'value' => null),
        'seo_description' => array('type' => 'string', 'value' => null),
        'seo_keywords' => array('type' => 'string', 'value' => null),
        'credits' => array('type' => 'integer', 'value' => null),
        'registration' => array('type' => 'datetime', 'value' => null, 'protected' => true),
        'last_login' => array('type' => 'datetime', 'value' => null, 'protected' => true),
        'send_newsletters' => array('type' => 'boolean', 'value' => null),
        'send_notifications' => array('type' => 'boolean', 'value' => null),
        
        'fset_id' => array('type' => 'integer', 'value' => null, 'fetch' => 'fetchFsetId'),
        'addresses' => array('type' => 'array', 'value' => array(), 'fetch' => 'fetchAddresses'),
        'memberzones' => array('type' => 'array', 'value' => array(), 'fetch' => 'fetchMemberzones'),
        'files' => array('type' => 'array', 'value' => array(), 'fetch' => 'fetchFiles'),
    );
    
    ##
    
    public function __construct($doc_id = null) {
        parent::__construct('user');
        
        if (isset($doc_id)) {
            if ($sql = mysql_fetch_assoc(mysql_query("
                    SELECT
                        sd.doc_id, sd.doc_name, sd.doc_type, sd.doc_subtype, sd.doc_note, sd.doc_active,
                        td.first_name, td.last_name, td.email, td.company, td.job_title, td.date_of_birth, td.street_1, td.street_2, td.city, td.state, td.zipcode, td.country, td.phone_1, td.phone_2, td.mobile, td.seo_title, td.seo_description, td.seo_keywords, td.credits, td.registration, td.last_login, td.send_newsletters, td.send_notifications
                    FROM sys_docs sd LEFT JOIN type_user td ON sd.doc_id = td.user_id
                    WHERE td.user_id = '" . mysql_real_escape_string($doc_id) . "'
                "))) {
                
                /* deprecated */
                $this->data['type']['value'] = $sql['doc_subtype'];
                
                foreach ($sql as $name => $value) {
                    if (array_key_exists($name, $this->data)) {
                        $this->data[$name]['value'] = $value;
                    }
                }
            }
        }
    }
    
    ##
    
    protected function fetchFsetId() {
        if (isset($this->data['doc_id']['value'])) {
            $this->data['fset_id']['value'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'"), 0);
        }
    }
    
    protected function fetchAddresses() {
        if (isset($this->data['doc_id']['value'])) {
            $tmp = array();
            $sql_query = mysql_query("SELECT first_name, last_name, company, job_title, street_1, street_2, city, state, zipcode, country, phone_1, phone_2 FROM type_user__addresses WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' ORDER BY idx ASC");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $tmp[] = $sql;
            }
            $this->data['addresses']['value'] = $tmp;
        }
    }
    
    protected function fetchMemberzones() {
        if (isset($this->data['doc_id']['value'])) {
            $tmp = array();
            $sql_query = mysql_query("SELECT memberzone_id FROM type_memberzone__users WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $tmp[] = $sql['memberzone_id'];
            }
            $this->data['memberzones']['value'] = $tmp;
        }
    }
    
    protected function fetchFiles() {
        if (isset($this->data['doc_id']['value'])) {
            $tmp = array();
            $sql_query = mysql_query("SELECT url, file_id, `set` FROM type_user__files WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' ORDER BY `set` ASC, idx ASC");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $tmp[] = $sql;
            }
            $this->data['files']['value'] = $tmp;
        }
    }
    
    ##
    
    public function validate(&$errors = null) {
        /* deprecated */
        if (isset($this->data['type']['modified'])) {
            $this->data['doc_subtype']['value'] = $this->data['type']['value'];
            $this->data['doc_subtype']['modified'] = true;
        }
        
        if (isset($this->data['doc_subtype']['modified']) && ($this->data['doc_subtype']['value'] == 'tech-admin' || $this->data['doc_subtype']['value'] == 'content-admin')) {
            $errors['doc_subtype'] = 'restricted';
        }
        
        parent::validate($errors);
        
        if (!$this->data['first_name']['value']) {
            $errors['first_name'] = 'invalid';
        }
        
        if (!$this->data['last_name']['value']) {
            $errors['last_name'] = 'invalid';
        }
        
        // Only validate email of new users.
        if (!isset($this->data['doc_id']['value']) && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $this->data['email']['value'])) {
            $errors['email'] = 'invalid';
        } elseif (@mysql_result(mysql_query("SELECT 1 FROM type_user WHERE email = '" . mysql_real_escape_string($this->data['email']['value']) . "' AND user_id != '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'"), 0)) {
            $errors['email'] = 'already exists';
        }
        
        if (!isset($this->data['doc_id']['value']) && !$this->data['password']['value']) {
            $errors['password'] = 'invalid';
        }
        
        if ($this->data['date_of_birth']['value'] && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->data['date_of_birth']['value']) || !checkdate(substr($this->data['date_of_birth']['value'], 5, 2), substr($this->data['date_of_birth']['value'], 8, 2), substr($this->data['date_of_birth']['value'], 0, 4)))) {
            $errors['date_of_birth'] = 'invalid';
        }
        
        if ($this->data['mobile']['value'] && !preg_match('/^\d{10,15}$/', $this->data['mobile']['value'])) {
            $errors['mobile'] = 'invalid';
        }
        
        if ($this->data['credits']['value'] && !preg_match('/^\-?\d+$/', $this->data['credits']['value'])) {
            $errors['credits'] = 'invalid';
        }
        
        if (isset($this->data['fset_id']['modified']) && $this->data['fset_id']['value']) {
            if (!@mysql_result(mysql_query("SELECT 1 FROM type_review__fsets WHERE fset_id = '" . mysql_real_escape_string($this->data['fset_id']['value']) . "'"), 0)) {
                $errors['fset_id'] = 'invalid';
            }
        }
        
        if (isset($this->data['addresses']['modified']) && $this->data['addresses']['value']) {
            $this->data['addresses']['value'] = getNonEmptyIterations($this->data['addresses']['value']);
            for ($i = 0; $i != count($this->data['addresses']['value']); $i++) {
                if (!$this->data['addresses']['value'][$i]['first_name']) {
                    $errors['addresses[' . $i . '][first_name]'] = 'invalid';
                }
                if (!$this->data['addresses']['value'][$i]['last_name']) {
                    $errors['addresses[' . $i . '][last_name]'] = 'invalid';
                }
                if (!$this->data['addresses']['value'][$i]['street_1']) {
                    $errors['addresses[' . $i . '][street_1]'] = 'invalid';
                }
            }
        }
        
        if (isset($this->data['memberzones']['modified']) && $this->data['memberzones']['value']) {
            if (count($this->data['memberzones']['value']) != @mysql_result(mysql_query("SELECT COUNT(*) FROM type_memberzone WHERE memberzone_id IN ('" . @implode("','", @array_map('mysql_real_escape_string', $this->data['memberzones']['value'])) . "')"), 0)) {
                $errors['memberzones'] = 'invalid';
            }
        }
        
        if (isset($this->data['files']['modified']) && $this->data['files']['value']) {
            $this->data['files']['value'] = getNonEmptyIterations($this->data['files']['value']);
        }
        
        if (!$errors) {
            if ($subscriber_id = @mysql_result(mysql_query("SELECT subscriber_id FROM type_subscriber WHERE email = '" . mysql_real_escape_string($this->data['email']['value']) . "'"), 0)) {
                $subscriber = new Subscriber($subscriber_id);
                $subscriber->delete();
            }
        }
        
        return $errors ? false : true;
    }
    
    ##
    
    public function save(&$errors = null) {
        if ($this->validate($errors)) {
            mysql_query("BEGIN");
            
            ##
            
            $newDoc = !isset($this->data['doc_id']['value']);
            
            parent::save($errors);
            
            ##
            
            if ($newDoc) {
                mysql_query("INSERT INTO type_user (user_id, email, password, first_name, last_name, company, job_title, street_1, street_2, city, state, zipcode, country, phone_1, phone_2, mobile, date_of_birth, seo_title, seo_description, seo_keywords, credits, registration, send_newsletters, send_notifications) VALUES ('" . mysql_real_escape_string($this->data['doc_id']['value']) . "', '" . mysql_real_escape_string($this->data['email']['value']) . "', '" . md5(constant('SECRET_PHRASE') . $this->data['password']['value']) . "', '" . mysql_real_escape_string($this->data['first_name']['value']) . "', '" . mysql_real_escape_string($this->data['last_name']['value']) . "', '" . mysql_real_escape_string($this->data['company']['value']) . "', '" . mysql_real_escape_string($this->data['job_title']['value']) . "', '" . mysql_real_escape_string($this->data['street_1']['value']) . "', '" . mysql_real_escape_string($this->data['street_2']['value']) . "', '" . mysql_real_escape_string($this->data['city']['value']) . "', '" . mysql_real_escape_string($this->data['state']['value']) . "', '" . mysql_real_escape_string($this->data['zipcode']['value']) . "', '" . mysql_real_escape_string($this->data['country']['value']) . "', '" . mysql_real_escape_string($this->data['phone_1']['value']) . "', '" . mysql_real_escape_string($this->data['phone_2']['value']) . "', " . SQLNULL(mysql_real_escape_string($this->data['mobile']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['date_of_birth']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_title']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_description']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_keywords']['value'])) . ", '" . mysql_real_escape_string($this->data['credits']['value']) . "', NOW(), '" . mysql_real_escape_string($this->data['send_newsletters']['value']) . "', '" . mysql_real_escape_string($this->data['send_notifications']['value']) . "')") || $errors[] = mysql_error();
            } else {
                mysql_query("UPDATE type_user SET
                    email = '" . mysql_real_escape_string($this->data['email']['value']) . "',
                    password = " . (isset($this->data['password']['value']) ? "'" . md5(constant('SECRET_PHRASE') . $this->data['password']['value']) . "'" : "password") . ",
                    first_name = '" . mysql_real_escape_string($this->data['first_name']['value']) . "',
                    last_name = '" . mysql_real_escape_string($this->data['last_name']['value']) . "',
                    company = '" . mysql_real_escape_string($this->data['company']['value']) . "',
                    job_title = '" . mysql_real_escape_string($this->data['job_title']['value']) . "',
                    street_1 = '" . mysql_real_escape_string($this->data['street_1']['value']) . "',
                    street_2 = '" . mysql_real_escape_string($this->data['street_2']['value']) . "',
                    city = '" . mysql_real_escape_string($this->data['city']['value']) . "',
                    state = '" . mysql_real_escape_string($this->data['state']['value']) . "',
                    zipcode = '" . mysql_real_escape_string($this->data['zipcode']['value']) . "',
                    country = '" . mysql_real_escape_string($this->data['country']['value']) . "',
                    phone_1 = '" . mysql_real_escape_string($this->data['phone_1']['value']) . "',
                    phone_2 = '" . mysql_real_escape_string($this->data['phone_2']['value']) . "',
                    mobile = " . SQLNULL(mysql_real_escape_string($this->data['mobile']['value'])) . ",
                    date_of_birth = " . SQLNULL(mysql_real_escape_string($this->data['date_of_birth']['value'])) . ",
                    seo_title = " . SQLNULL(mysql_real_escape_string($this->data['seo_title']['value'])) . ",
                    seo_description = " . SQLNULL(mysql_real_escape_string($this->data['seo_description']['value'])) . ",
                    seo_keywords = " . SQLNULL(mysql_real_escape_string($this->data['seo_keywords']['value'])) . ",
                    credits = '" . mysql_real_escape_string($this->data['credits']['value']) . "',
                    registration = registration,
                    last_login = last_login,
                    send_newsletters = '" . mysql_real_escape_string($this->data['send_newsletters']['value']) . "',
                    send_notifications = '" . mysql_real_escape_string($this->data['send_notifications']['value']) . "',
                    auth_remote_address = auth_remote_address,
                    auth_expiration_time = auth_expiration_time WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'
                ") || $errors[] = mysql_error();
            }
            
            #
            
            if (isset($this->data['fset_id']['modified'])) {
                mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
                if ($this->data['fset_id']['value']) {
                    mysql_query("INSERT INTO type_review__fsets_associated_docs (fset_id, associated_doc_id) VALUES ('" . mysql_real_escape_string($this->data['fset_id']['value']) . "', '" . mysql_real_escape_string($this->data['doc_id']['value']) . "')") || $errors[] = mysql_error();
                }
            }
            
            #
            
            if (isset($this->data['addresses']['modified'])) {
                mysql_query("DELETE FROM type_user__addresses WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'") || $errors[] = mysql_error();
                for ($i = 0; $i != count($this->data['addresses']['value']); $i++) {
                    mysql_query("INSERT INTO type_user__addresses (user_id, first_name, last_name, company, job_title, street_1, street_2, city, state, zipcode, country, phone_1, phone_2, idx) VALUES ('" . mysql_real_escape_string($this->data['doc_id']['value']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['first_name']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['last_name']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['company']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['job_title']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['street_1']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['street_2']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['city']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['state']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['zipcode']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['country']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['phone_1']) . "', '" . mysql_real_escape_string($this->data['addresses']['value'][$i]['phone_2']) . "', '$i')") || $errors[] = mysql_error();
                }
            }
            
            #
            
            if (isset($this->data['memberzones']['modified'])) {
                mysql_query("DELETE FROM type_memberzone__users WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'") || $errors[] = mysql_error();
                for ($i = 0; $i != count($this->data['memberzones']['value']); $i++) {
                    mysql_query("INSERT INTO type_memberzone__users (memberzone_id, user_id) VALUES ('" . mysql_real_escape_string($this->data['memberzones']['value'][$i]) . "', '" . mysql_real_escape_string($this->data['doc_id']['value']) . "')") || $errors[] = mysql_error();
                }
            }
            
            #
            
            if (isset($this->data['files']['modified'])) {
                mysql_query("DELETE FROM type_user__files WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'") || $errors[] = mysql_error();
                // Group the files by "sets" before inserting them into the database, so as to properly index them.
                for ($i = 0, $filesBySet = array(); $i != count($this->data['files']['value']); $i++) {
                    $filesBySet[$this->data['files']['value'][$i]['set']][] = $this->data['files']['value'][$i];
                }
                if ($filesBySet) {
                    foreach ($filesBySet as $set => $files) {
                        for ($i = 0; $i != count($files); $i++) {
                            mysql_query("INSERT INTO type_user__files (user_id, file_id, url, `set`, idx) VALUES ('" . mysql_real_escape_string($this->data['doc_id']['value']) . "', '" . mysql_real_escape_string(uniqid()) . "', '" . mysql_real_escape_string($files[$i]['url']) . "', " . SQLNULL(mysql_real_escape_string($set)) . ", '$i')") || $errors[] = mysql_error();
                        }
                    }
                }
            }
            
            ##
            
            if ($errors) {
                mysql_query("ROLLBACK");
                
                return false;
            } else {
                mysql_query("COMMIT");
                
                return $this->data['doc_id']['value'];
            }
        } else {
            return false;
        }
    }
    
    ##
    
    public function delete() {
        if (isset($this->data['doc_id']['value'])) {
            parent::delete();
            
            $userName = @mysql_result(mysql_query("SELECT CONCAT(first_name, ' ', last_name) FROM type_user WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'"), 0);
            mysql_query("UPDATE type_talkback SET name = '" . mysql_real_escape_string($userName) . "', user_id = NULL WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("UPDATE type_review SET name = '" . mysql_real_escape_string($userName) . "', user_id = NULL WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            
            mysql_query("DELETE FROM type_user WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            
            mysql_query("DELETE FROM type_user__addresses WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_user__admin_access WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_user__files WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_user__ext WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'"); // Exists in hot4fun
            
            mysql_query("DELETE FROM type_memberzone__users WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            
            $sql_query = mysql_query("SELECT auction_id FROM type_auction WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                mysql_query("DELETE FROM sys_docs WHERE doc_id = '$sql[auction_id]'");
                mysql_query("DELETE FROM type_auction WHERE auction_id = '$sql[auction_id]'");
                #
                mysql_query("DELETE FROM type_auction__files WHERE auction_id = '$sql[auction_id]'");
                #
                mysql_query("DELETE FROM type_auction__images WHERE auction_id = '$sql[auction_id]'");
                mysql_query("DELETE FROM type_auction__links WHERE auction_id = '$sql[auction_id]'");
                mysql_query("DELETE FROM type_auction__bids WHERE auction_id = '$sql[auction_id]'");
                #
                mysql_query("DELETE FROM type_group__associated_docs WHERE associated_doc_id = '$sql[auction_id]'");
                #
                mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '$sql[auction_id]'");
            }
            mysql_query("DELETE FROM type_auction__bids WHERE user_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            
            @unlink(constant('UPLOAD_BASE') . $this->data['doc_id']['value']);
            
            return true;
        }
        
        return false;
    }
    
    ##
    
    public function saveUploadedFiles($userFile) {
        $whitelist = array(
            'text/plain',
            'text/csv',
            'text/vcard',
            'image/gif',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/vnd.adobe.photoshop',
            'audio/mp3',
            'video/x-flv',
            'video/x-msvideo',
            'video/quicktime',
            'video/mpeg',
            'video/x-ms-wmv',
            'video/mp4',
            'application/pdf',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/x-gzip',
            'application/x-tar',
            'application/x-rar',
            'application/x-shockwave-flash',
            'application/octet-stream'         
        );
        
        $tmp = array();
        
        if (isset($this->data['doc_id']['value'])) {
            if ($_FILES[$userFile]) {
                $directory = 'users/' . $this->data['doc_id']['value'] . '/';
                
                // "$userFile" can refer to either a single file or an (HTML-type) array of files (i.e. "file" vs. "files[]").
                if (is_array($_FILES[$userFile]['name'])) {
                    $files = $_FILES;
                } else {
                    $files = array(
                        $userFile => array(
                            'name' => array($_FILES[$userFile]['name']),
                            'type' => array($_FILES[$userFile]['type']),
                            'tmp_name' => array($_FILES[$userFile]['tmp_name']),
                            'error' => array($_FILES[$userFile]['error']),
                            'size' => array($_FILES[$userFile]['size'])
                        )
                    );
                }
                /*
                if ($userFile == 'steps') {
	                echo json_encode($files); exit;
                }
                */
                foreach ($files[$userFile]['name'] as $i => $name) {
               	//for ($i = 0; $i != count($files[$userFile]['name']); $i++) {
               	    if (!empty($files[$userFile]['name'][$i]) && is_array($files[$userFile]['name'][$i])) {
	                    foreach ($files[$userFile]['name'][$i] as $key => $value) {
		                 	if ($files[$userFile]['error'][$i][$key] == 0 && in_array($files[$userFile]['type'][$i][$key], $whitelist)) {
		                        $filename = uniqid() . '.' . preg_replace('/^.+([^\.]+)$/U', '$1', strtolower($files[$userFile]['name'][$i][$key]));
		                        
		                        @mkdir(CONSTANT('UPLOAD_BASE') . $directory, 0777, true);
		                        
		                        if (move_uploaded_file($files[$userFile]['tmp_name'][$i][$key], CONSTANT('UPLOAD_BASE') . $directory . $filename)) {
		                            $tmp[$i][$key] = CONSTANT('UPLOAD_BASE_VIRTUAL') . $directory . $filename;
		                        }
		                    }   
	                    }
	                } else if (!empty($files[$userFile]['name'])) {
						foreach ($files[$userFile]['name'] as $key => $value) {
		                 	if ($files[$userFile]['error'][$key] == 0 && in_array($files[$userFile]['type'][$key], $whitelist)) {
		                        $filename = uniqid() . '.' . preg_replace('/^.+([^\.]+)$/U', '$1', strtolower($files[$userFile]['name'][$key]));
		                        
		                        @mkdir(CONSTANT('UPLOAD_BASE') . $directory, 0777, true);
		                        
		                        if (move_uploaded_file($files[$userFile]['tmp_name'][$key], CONSTANT('UPLOAD_BASE') . $directory . $filename)) {
		                            $tmp[$key] = CONSTANT('UPLOAD_BASE_VIRTUAL') . $directory . $filename;
		                        }
		                    }   
	                    }
                    } else {
	                 	if ($files[$userFile]['error'][$i] == 0 && in_array($files[$userFile]['type'][$i], $whitelist)) {
	                        $filename = uniqid() . '.' . preg_replace('/^.+([^\.]+)$/U', '$1', strtolower($files[$userFile]['name'][$i]));
	                        
	                        @mkdir(CONSTANT('UPLOAD_BASE') . $directory, 0777, true);
	                        
	                        if (move_uploaded_file($files[$userFile]['tmp_name'][$i], CONSTANT('UPLOAD_BASE') . $directory . $filename)) {
	                            $tmp[] = CONSTANT('UPLOAD_BASE_VIRTUAL') . $directory . $filename;
	                        }
	                    }
                    }
                }
            }
        }
        /*
        if ($userFile == 'steps') {
            echo json_encode($tmp); exit;
        }
        */
        return $tmp;
    }
}

?>
