<?php

class Group extends GyroDoc {
    protected $data = array(
        'parent' => array('type' => 'obj Group', 'value' => null, 'fetch' => 'fetchParent'),
        'parent_group_id' => array('type' => 'integer', 'value' => null), /* deprecated */
        'type' => array('type' => 'string', 'value' => null), /* deprecated */
        'depth' => array('type' => 'into', 'value' => null, 'fetch' => 'fetchDepth'),
        'title' => array('type' => 'string', 'value' => null),
        'description' => array('type' => 'string', 'value' => null, 'fetch' => 'fetchDescription'),
        'status' => array('type' => 'string', 'value' => null),
        'seo_title' => array('type' => 'string', 'value' => null),
        'seo_description' => array('type' => 'string', 'value' => null),
        'seo_keywords' => array('type' => 'string', 'value' => null),
        'idx' => array('type' => 'integer', 'value' => null),
        
        'fset_id' => array('type' => 'integer', 'value' => null, 'fetch' => 'fetchFsetId'),
        'coupons' => array('type' => 'array', 'value' => null, 'fetch' => 'fetchCoupons'),
        'memberzone_id' => array('type' => 'integer', 'value' => null, 'fetch' => 'fetchMemberzoneId', 'protected' => true),
        'children' => array('type' => 'array', 'value' => null, 'fetch' => 'fetchChildGroups', 'protected' => true),
    );
    
    ##
    
    public function __construct($doc_id = null) {
        parent::__construct('group');
        
        if (isset($doc_id)) {
            if ($sql = mysql_fetch_assoc(mysql_query("
                    SELECT
                        sd.doc_id, sd.doc_name, sd.doc_type, sd.doc_subtype, sd.doc_note, sd.doc_active,
                        td.parent_group_id, td.title, td.description, td.status, td.seo_title, td.seo_description, td.seo_keywords, td.idx
                    FROM sys_docs sd LEFT JOIN type_group td ON sd.doc_id = td.group_id
                    WHERE td.group_id = '" . mysql_real_escape_string($doc_id) . "'
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
    
    protected function fetchParent() {
        if (isset($this->data['doc_id']['value']) && !$this->data['parent']['value'] && isset($this->data['parent_group_id']['value'])) {
            $this->data['parent']['value'] = new Group($this->data['parent_group_id']['value']);
        }
    }
    
    protected function fetchDepth() {
        if (isset($this->data['doc_id']['value'])) {
            if ($this->parent) {
                $this->data['depth']['value'] = $this->parent->depth + 1;
            } else {
                $this->data['depth']['value'] = 0;
            }
        }
    }
    
    protected function fetchDescription() {
        $this->data['description']['value'] = href_HTMLArea($this->data['description']['value']);
    }
    
    protected function fetchFsetId() {
        if (isset($this->data['doc_id']['value'])) {
            $this->data['fset_id']['value'] = @mysql_result(mysql_query("SELECT fset_id FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'"), 0);
        }
    }
    
    protected function fetchCoupons() {
        if (isset($this->data['doc_id']['value'])) {
            $tmp = array();
            $sql_query = mysql_query("SELECT coupon_id FROM type_coupon__groups WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $tmp[] = $sql['coupon_id'];
            }
            $this->data['coupons']['value'] = $tmp;
        }
    }
    
    protected function fetchMemberzoneId() {
        if (isset($this->data['doc_id']['value'])) {
            $groupId = mysql_real_escape_string($this->data['doc_id']['value']);
            do {
                if (!$memberzoneId = @mysql_result(mysql_query("SELECT memberzone_id FROM type_memberzone WHERE group_id = '$groupId'"), 0)) {
                    $groupId = @mysql_result(mysql_query("SELECT parent_group_id FROM type_group WHERE group_id = '$groupId' AND parent_group_id IS NOT NULL"), 0);
                }
            } while ($groupId && !$memberzoneId);
            
            $this->data['memberzone_id']['value'] = $memberzoneId;
        }
    }
    
    protected function fetchChildGroups() {
        if (isset($this->data['doc_id']['value'])) {
            $tmp = array();
            $sql_query = mysql_query("SELECT group_id FROM type_group WHERE parent_group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' ORDER BY idx ASC");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $tmp[] = new Group($sql['group_id']);
            }
            $this->data['children']['value'] = $tmp;
        }
    }
    
    ##
    
    public function validate(&$errors = null) {
        /* deprecated */
        if (isset($this->data['parent_group_id']['modified'])) {
            $this->data['parent']['value'] = new Group($this->data['parent_group_id']['value']);
            $this->data['parent']['modified'] = true;
        }
        
        /* deprecated */
        if (isset($this->data['type']['modified'])) {
            $this->data['doc_subtype']['value'] = $this->data['type']['value'];
            $this->data['doc_subtype']['modified'] = true;
        }
        
        if (!$this->data['doc_subtype']['value'] && $this->data['parent']['value']) {
            $this->data['doc_subtype']['value'] = $this->data['parent']['value']->doc_subtype;
        }
        
        parent::validate($errors);
        
        if (isset($this->data['parent']['modified']) && $this->data['parent']['value']) {
            if (!is_object($this->data['parent']['value'])) {
                $errors['parent'] = 'invalid';
            } elseif (!@mysql_result(mysql_query("SELECT 1 FROM type_group WHERE group_id = '" . mysql_real_escape_string($this->data['parent']['value']->doc_id) . "'"), 0)) {
                $errors['parent'] = 'invalid';
            } elseif ($this->data['parent']['value']->doc_id == $this->data['doc_id']['value']) {
                $errors['parent'] = 'invalid';
            } else {
                // Check that the parent-group is not a descendant of the current group.
                $tmp = $this->data['parent']['value']->doc_id;
                while ($tmp = @mysql_result(mysql_query("SELECT parent_group_id FROM type_group WHERE group_id = '" . mysql_real_escape_string($tmp) . "' AND parent_group_id IS NOT NULL"), 0)) {
                    if ($tmp == $this->data['doc_id']['value']) {
                        $errors['parent'] = 'invalid';
                    }
                }
            }
        }
        
        if ((isset($this->data['doc_subtype']['modified']) || isset($this->data['parent']['modified'])) && $this->data['doc_subtype']['value'] && $this->data['parent']['value'] && $this->data['doc_subtype']['value'] != $this->data['parent']['value']->doc_subtype) {
            $errors['doc_subtype'] = 'invalid (parent-group subtype mismatch)';
        }
        
        if (!$this->data['title']['value']) {
            $errors['title'] = 'invalid';
        }
        
        if ($this->data['idx']['value'] && !preg_match('/^\d+$/', $this->data['idx']['value'])) {
            $errors['idx'] = 'invalid';
        }
        
        if (!in_array($this->data['status']['value'], array('normal', 'unavailable', 'hidden'))) {
            $errors['status'] = 'invalid';
        }
        
        if (isset($this->data['fset_id']['modified']) && $this->data['fset_id']['value']) {
            if (!@mysql_result(mysql_query("SELECT 1 FROM type_review__fsets WHERE fset_id = '" . mysql_real_escape_string($this->data['fset_id']['value']) . "'"), 0)) {
                $errors['fset_id'] = 'invalid';
            }
        }
        
        if (isset($this->data['coupons']['modified']) && $this->data['coupons']['value']) {
            if (count($this->data['coupons']['value']) != @mysql_result(mysql_query("SELECT COUNT(*) FROM type_coupon WHERE coupon_id IN ('" . @implode("','", @array_map('mysql_real_escape_string', $this->data['coupons']['value'])) . "')"), 0)) {
                $errors['coupons'] = 'invalid';
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
            
            //mysql_query("REPLACE type_group (group_id, parent_group_id, title, description, seo_title, seo_description, seo_keywords, idx, status) VALUES ('" . mysql_real_escape_string($this->data['doc_id']['value']) . "', " . SQLNULL(mysql_real_escape_string($this->data['parent']['value'] ? $this->data['parent']['value']->doc_id : false)) . ", " . SQLNULL(mysql_real_escape_string($this->data['title']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['description']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_title']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_description']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_keywords']['value'])) . ", '" . mysql_real_escape_string($this->data['idx']['value']) . "', '" . mysql_real_escape_string($this->data['status']['value']) . "')") || $sql_errors[] = mysql_error();
            mysql_query("REPLACE type_group (group_id, parent_group_id, title, description, seo_title, seo_description, seo_keywords, idx, status) VALUES ('" . mysql_real_escape_string($this->data['doc_id']['value']) . "', " . SQLNULL(mysql_real_escape_string($this->data['parent_group_id']['value'] ?: false)) . ", " . SQLNULL(mysql_real_escape_string($this->data['title']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['description']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_title']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_description']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['seo_keywords']['value'])) . ", '" . mysql_real_escape_string($this->data['idx']['value']) . "', '" . mysql_real_escape_string($this->data['status']['value']) . "')") || $sql_errors[] = mysql_error();

            #
            
            if (isset($this->data['fset_id']['modified'])) {
                mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
                if ($this->data['fset_id']['value']) {
                    mysql_query("INSERT INTO type_review__fsets_associated_docs (fset_id, associated_doc_id) VALUES ('" . mysql_real_escape_string($this->data['fset_id']['value']) . "', '" . mysql_real_escape_string($this->data['doc_id']['value']) . "')") || $errors[] = mysql_error();
                }
            }
            
            #
            
            if (isset($this->data['coupons']['modified'])) {
                mysql_query("DELETE FROM type_coupon__groups WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'") || $errors[] = mysql_error();
                for ($i = 0; $i != count($this->data['coupons']['value']); $i++) {
                    mysql_query("INSERT INTO type_coupon__groups (coupon_id, group_id) VALUES ('" . mysql_real_escape_string($this->data['coupons']['value'][$i]) . "', '" . mysql_real_escape_string($this->data['doc_id']['value']) . "')") || $errors[] = mysql_error();
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
            
            // Recursively delete all descendant groups.
            $sql_query = mysql_query("SELECT group_id FROM type_group WHERE parent_group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                $group = new Group($sql['group_id']);
                $group->delete();
            }
            
            mysql_query("DELETE FROM type_group WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_group__paragraphs WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_group__associated_docs WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_review__fsets_associated_docs WHERE associated_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("DELETE FROM type_coupon__groups WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            mysql_query("UPDATE type_memberzone SET group_id = NULL WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
            
            return true;
        }
        
        return false;
    }
    
    ##
    
// Add support for $docSubtype.
    public function getDocs($docType, $docSubtype = null) {
        $tmp = array();
        
        if ($docType == 'group') {
            // A better alternative to this is $this->children, which returns an array of objects.
            $sql_query = mysql_query("SELECT group_id AS doc_id FROM type_group WHERE parent_group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' ORDER BY idx ASC");
        } elseif ($docType == 'review') {
            $sql_query = mysql_query("SELECT review_id AS doc_id FROM type_review WHERE parent_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' ORDER BY timestamp DESC");
        } elseif ($docType == 'talkback') {
            $sql_query = mysql_query("SELECT talkback_id AS doc_id FROM type_talkback WHERE parent_doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' ORDER BY timestamp DESC");
        } else {
            $sql_query = mysql_query("SELECT associated_doc_id AS doc_id FROM type_group__associated_docs LEFT JOIN sys_docs ON associated_doc_id = doc_id WHERE group_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' AND doc_type = '" . mysql_real_escape_string($docType) . "' " . ($docSubtype ? " AND doc_subtype = '" . mysql_real_escape_string($docSubtype) . "'" : null) . " ORDER BY idx ASC");
        }
        while ($sql = mysql_fetch_assoc($sql_query)) {
            $tmp[] = $sql['doc_id'];
        }
        return $tmp;
    }
}

?>