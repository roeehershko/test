<?php

class Element extends GyroDoc {
    protected $data = array(
        'title' => array('type' => 'string', 'value' => null),
        'fset_id' => array('type' => 'integer', 'value' => null, 'fetch' => 'fetchFsetId'),
        'groups' => array('type' => 'array', 'value' => array(), 'fetch' => 'fetchGroups'),
    );
    
    ##
    
    public function __construct($doc_id = null) {
        parent::__construct('element');
        
        if (isset($doc_id)) {
            if ($sql = mysql_fetch_assoc(mysql_query("
                    SELECT
                        sd.doc_id, sd.doc_name, sd.doc_type, sd.doc_subtype, sd.doc_note, sd.doc_active,
                        td.title
                    FROM sys_docs sd LEFT JOIN type_element td ON sd.doc_id = td.element_id
                    WHERE td.element_id = '" . mysql_real_escape_string($doc_id) . "'
                "))) {
                
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
    
    protected function fetchGroups() {
        if (isset($this->data['doc_id']['value'])) {
            $tmp = array();
            $sql_query = mysql_query("SELECT group_id FROM type_group__associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $tmp[] = $sql['group_id'];
            }
            $this->data['groups']['value'] = $tmp;
        }
    }
    
    ##
    
    public function validate(&$errors = null) {
        parent::validate($errors);
        
        if (!$this->data['title']['value']) {
            $errors['title'] = 'invalid';
        }
        
        if (isset($this->data['fset_id']['modified']) && $this->data['fset_id']['value']) {
            if (!@mysql_result(mysql_query("SELECT 1 FROM type_review__fsets WHERE fset_id = '" . mysql_real_escape_string($this->data['fset_id']['value']) . "'"), 0)) {
                $errors['fset_id'] = 'invalid';
            }
        }
        
        if (isset($this->data['groups']['modified']) && $this->data['groups']['value']) {
            if (count($this->data['groups']['value']) != @mysql_result(mysql_query("SELECT COUNT(*) FROM type_group WHERE group_id IN ('" . @implode("','", @array_map('mysql_real_escape_string', $this->data['groups']['value'])) . "')"), 0)) {
                $errors['groups'] = 'invalid';
            }
        }
        
        return $errors ? false : true;
    }
    
    ##
    
    public function save(&$errors = null) {
        if ($this->validate($errors)) {
            mysql_query("BEGIN");
            
            ##
            
            parent::save($errors);
            
            ##
            
            mysql_query("REPLACE type_element (element_id, title) VALUES ('" . mysql_real_escape_string($this->data['doc_id']['value']) . "', '" . mysql_real_escape_string($this->data['title']['value']) . "')") || $sql_errors[] = mysql_error();
            
            #
            
            if (isset($this->data['fset_id']['modified'])) {
                mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
                if ($this->data['fset_id']['value']) {
                    mysql_query("INSERT INTO type_review__fsets_associated_docs (fset_id, associated_doc_id) VALUES ('" . mysql_real_escape_string($this->data['fset_id']['value']) . "', '" . mysql_real_escape_string($this->data['doc_id']['value']) . "')") || $errors[] = mysql_error();
                }
            }
            
            #
            
            if (isset($this->data['groups']['modified'])) {
                if ($this->data['groups']['value']) {
                    $sql_query = mysql_query("SELECT group_id, idx FROM type_group__associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
                    while ($sql = mysql_fetch_assoc($sql_query)) {
                        $groupsIdx[$sql['group_id']] = $sql['idx'];
                    }
                }
                mysql_query("DELETE FROM type_group__associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'") || $errors[] = mysql_error();
                for ($i = 0; $i != count($this->data['groups']['value']); $i++) {
                    if ($this->data['groups']['value'][$i]) {
                        $idx = $groupsIdx[$this->data['groups']['value'][$i]];
                        mysql_query("INSERT INTO type_group__associated_docs (group_id, associated_doc_id, idx) VALUES ('" . mysql_real_escape_string($this->data['groups']['value'][$i]) . "', '" . mysql_real_escape_string($this->data['doc_id']['value']) . "', '" . $idx . "')") || $errors[] = mysql_error();
                    }
                }
            }
                        
            #
            
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
            
            mysql_query("DELETE FROM type_element WHERE element_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_group__associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            
            return true;
        }
        
        return false;
    }
}

?>