<?php

/*
Types:
    string
    integer
    boolean
    decimal
    float
    date
    datetime
    array
    param
*/

abstract class GyroDoc {
    private $docData = array(
        'doc' => array('type' => 'string', 'value' => null, 'fetch' => 'fetchDoc', 'protected' => true),
        'doc_type' => array('type' => 'string', 'value' => null, 'protected' => true),
        'doc_subtype' => array('type' => 'string', 'value' => null),
        'doc_id' => array('type' => 'integer', 'value' => null, 'protected' => true),
        'doc_name' => array('type' => 'string', 'value' => null),
        'doc_note' => array('type' => 'string', 'value' => null),
        'doc_active' => array('type' => 'boolean', 'value' => null),
        'parameters' => array('type' => 'array', 'value' => array(), 'fetch' => 'fetchParameters'), /* deprecated */
    );
    
    protected $parameters = array();
    
    public function __construct($docType) {
        // Prepend the $docData variable map to that of the inheriting object and unset it.
        $tmp = $this->docData;
        foreach ($this->data as $key => $val) {
            $tmp[$key] = $val;
        }
        $this->data = $tmp;
        unset($this->docData, $tmp);
        
        $this->data['doc_type']['value'] = $docType;
        
        // Add all parameters to the doc scheme, each with a fetch function that fetches the specified parameter.
        $sql_query = mysql_query("SELECT param_name FROM sys_docs_types_params WHERE doc_type = '" . $this->data['doc_type']['value'] . "'");
        while ($sql = mysql_fetch_assoc($sql_query)) {
            if (!isset($this->data[$sql['param_name']])) {
                $this->data[$sql['param_name']] = array(
                    'type' => 'param',
                    'value' => null,
                    'fetch' => 'fetchDocParameter'
                );
                
                $this->parameters[] = $sql['param_name'];
            }
        }
        
/* TEMPORARY COMPATIBILITY MODE (WORKING):
        if ($_SESSION['user']['user_id'] == 500) {
            unset($this->data['parameters']);
        }
*/
    }
    
    // When not using "&__get", arrays cannot be changed directly (e.g. $product->parameters['name'] = 'value') because the change isn't done via "__set",
    // and therefore the `modified` flag is not set after the change (handled by "__set").
    // By using "__get" instead, arrays cannot be updated directly and a temporary variable is required
    // (e.g. $params = $product->parameters; $params['name'] = 'value'; $product->parameters = $params;)
    // This is the preferred solution because it forces the change to be done via "__set" and the `modified` flag is therefore properly set.
    public function __get($name) {
        if (isset($this->data[$name])) {
            if (isset($this->data[$name]['fetch'])) {
                $this->{$this->data[$name]['fetch']}($name);
                unset($this->data[$name]['fetch']);
            }
            
            return $this->data[$name]['value'];
        } else {
            return null;
        }
    }
    
    public function __set($name, $value) {
        if (isset($this->data[$name]) && !isset($this->data[$name]['protected'])) {
            if ($this->data[$name]['value'] != $value || !$this->data[$name]['value']) {
                $this->data[$name]['value'] = $value;
                $this->data[$name]['modified'] = true;
            }
            unset($this->data[$name]['fetch']);
        } elseif (!isset($this->data[$name])) {
            $this->data[$name] = array(
                'value' => $value,
                'modified' => true
            );
        }
    }
    
    public function __isset($name) {
        if (isset($this->data[$name])) {
            if (isset($this->data[$name]['fetch'])) {
                $this->{$this->data[$name]['fetch']}($name);
                unset($this->data[$name]['fetch']);
            }
            
            return isset($this->data[$name]['value']);
        }
        
        return false;
    }
    
    public function __unset($name) {
        if (isset($this->data[$name]) && !isset($this->data[$name]['protected'])) {
            $this->data[$name]['value'] = null;
            $this->data[$name]['modified'] = true;
        }
    }
    
    public function __toString() {
        foreach ($this->data as $name => &$value) {
            if (isset($this->data[$name]['fetch'])) {
                $this->{$this->data[$name]['fetch']}($name);
                unset($this->data[$name]['fetch']);
            }
            
            $definition = $name . ':' . $this->data[$name]['type'];
            
            if (isset($this->data[$name]['protected'])) {
                $definition .= ':protected';
            }
            
            $array[$definition] = $value['value'];
        }
        
        return print_r($array, 1);
    }
    
    /*
    public function struct() {
        return $this->definitions($this->data);
    }
    
    protected function definitions($data = array(), $level = 0) {
        ob_start();
        
        if ($data) {
            foreach ($data as $name => $value) {
                echo '<div style="font-size: 13px; font-family: Courier New; color: #000000; white-space: pre;">';
                echo str_repeat('    ', $level);
                echo '<span style="font-style: italic; color: ' . (isset($data[$name]['protected']) ? 'red' : 'green') . ';">' . str_pad($data[$name]['type'], 8, ' ', STR_PAD_LEFT) . '</span> ';
                echo '<b>' . $name . '</b>';
                
                if ($data[$name]['type'] == 'array') {
                    echo " {";
                    echo $this->definitions($data[$name]['definition'], $level + 1);
                    echo '}';
                }
                
                echo '</div>';
            }
        }
        
        return ob_get_clean();
    }
    */
    
    ##
    
    protected function fetchDoc() {
        if (isset($this->data['doc_name']['value'])) {
            $this->data['doc']['value'] = $this->data['doc_name']['value'];
        } elseif (isset($this->data['doc_id']['value'])) {
            $this->data['doc']['value'] = $this->data['doc_id']['value'];
        }
    }
    
    /* deprecated */
    protected function fetchParameters() {
        if (isset($this->data['doc_id']['value'])) {
            if ($parameters = getDocParameters($this->data['doc_id']['value'])) {
                $this->data['parameters']['value'] = $parameters;
            } else {
                $this->data['parameters']['value'] = array();
            }
        }
    }
    
    ##
    
    protected function fetchDocParameter($paramName) {
        if (isset($this->data['doc_id']['value'])) {
            // There are four kinds of parameters: single-data, single-data-iterated, multiple-data, multiple-data-iterated.
            // The kind of each parameter can be directly asserted from the 'sys_docs_params' db table.
            // The 'iteration' column is NULL if the parameter is not-iterated.
            // The 'param_data_name' column is NULL if the parameter is single-data.
            
            $sql_query = mysql_query("SELECT type AS param_type, iteration, sys_docs_params.param_data_name, IFNULL(value_short, value_long) AS value FROM sys_docs_params LEFT JOIN sys_docs USING (doc_id) LEFT JOIN sys_docs_types_params_data USING (doc_type, param_name) WHERE doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "' AND param_name = '" . mysql_real_escape_string($paramName) . "' AND sys_docs_params.param_data_name <=> sys_docs_types_params_data.param_data_name ORDER BY iteration ASC");
            while ($sql = mysql_fetch_assoc($sql_query)) {
                if ($sql['param_type'] == 'htmlarea') {
                    $sql['value'] = href_HTMLArea($sql['value']);
                }
/*
if ($_SESSION['user']['user_id'] == 500) {
                if ($sql['iteration'] == '' && $sql['param_data_name'] == '') {
                    $this->data[$paramName]['value'] = $sql['value'];
                } elseif ($sql['iteration'] == '') {
                    $this->data[$paramName]['value']->$sql['param_data_name'] = $sql['value'];
                } elseif ($sql['param_data_name'] == '') {
                    $this->data[$paramName]['value'][$sql['iteration']] = $sql['value'];
                } else {
                    $this->data[$paramName]['value'][$sql['iteration']]->$sql['param_data_name'] = $sql['value'];
                }
*/
//} else {
                if ($sql['iteration'] == '' && $sql['param_data_name'] == '') {
                    $this->data[$paramName]['value'] = $sql['value'];
                } elseif ($sql['iteration'] == '') {
                    $this->data[$paramName]['value'][$sql['param_data_name']] = $sql['value'];
                } elseif ($sql['param_data_name'] == '') {
                    $this->data[$paramName]['value'][$sql['iteration']] = $sql['value'];
                } else {
                    $this->data[$paramName]['value'][$sql['iteration']][$sql['param_data_name']] = $sql['value'];
                }
//}
            }
        }
    }
    
    ##
    
    protected function validate(&$errors = null) {
        if (isset($this->data['doc_name']['modified']) && $this->data['doc_name']['value']) {
            $this->data['doc_name']['value'] = strtolower($this->data['doc_name']['value']);
            
            $docNameDefaultPrefix = @mysql_result(mysql_query("SELECT doc_name_default_prefix FROM sys_docs_types WHERE type = '" . mysql_real_escape_string($this->data['doc_type']['value']) . "' AND doc_name_default_prefix IS NOT NULL"), 0);
            
            if (!preg_match('/^' . preg_quote($docNameDefaultPrefix, '/') . '(.+)$/', $this->data['doc_name']['value'], $match)) {
                $errors['doc_name'] = 'must begin with ' . $docNameDefaultPrefix;
            } elseif (!is_valid_doc_name($this->data['doc_name']['value'], $docNameDefaultPrefix)) {
                $errors['doc_name'] = 'contains illegal characters';
            } elseif (@mysql_result(mysql_query("SELECT 1 FROM sys_docs WHERE doc_name = '" . mysql_real_escape_string($this->data['doc_name']['value']) . "' AND doc_id != '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'"), 0)) {
                $errors['doc_name'] = 'already in use';
            }
        }
        
        // If this doc-type support subtypes, validate the subtype.
        if (@mysql_result(mysql_query("SELECT 1 FROM sys_docs_subtypes WHERE doc_type = '" . mysql_real_escape_string($this->data['doc_type']['value']) . "' GROUP BY doc_type"), 0)) {
            if (!$this->data['doc_subtype']['value'] || (isset($this->data['doc_subtype']['modified']) && !@mysql_result(mysql_query("SELECT 1 FROM sys_docs_subtypes WHERE doc_type = '" . mysql_real_escape_string($this->data['doc_type']['value']) . "' AND doc_subtype = '" . mysql_real_escape_string($this->data['doc_subtype']['value']) . "'"), 0))) {
                $errors['doc_subtype'] = 'invalid';
            }
        }
        
        if (isset($this->data['parameters']['modified'])) {
            /* deprecated */
            validateDocParameters($this->data['doc_type']['value'], $this->data['parameters']['value'], $errors);
        } else {
            $parameters = array();
            for ($i = 0; $i != count($this->parameters); $i++) {
                $parameters[$this->parameters[$i]] = $this->data[$this->parameters[$i]]['value'];
            }
            if ($parameters) {
                validateDocParameters($this->data['doc_type']['value'], $parameters, $errors);
            }
        }
        
        return $errors ? false : true;
    }
    
    ##
    
    protected function save(&$errors = null) {
        mysql_query("REPLACE sys_docs (doc_id, doc_name, doc_type, doc_subtype, doc_note) VALUES ('" . mysql_real_escape_string($this->data['doc_id']['value']) . "', " . SQLNULL(mysql_real_escape_string($this->data['doc_name']['value'])) . ", '" . mysql_real_escape_string($this->data['doc_type']['value']) . "', " . SQLNULL(mysql_real_escape_string($this->data['doc_subtype']['value'])) . ", " . SQLNULL(mysql_real_escape_string($this->data['doc_note']['value'])) . ")") || $errors[] = mysql_error();
        $this->data['doc_id']['value'] = mysql_insert_id();
        
        if (isset($this->data['parameters']['modified'])) {
            /* deprecated */
            $parameters = $this->data['parameters']['value'];
            recursive('mysql_real_escape_string', $parameters);
            saveDocParameters($this->data['doc_type']['value'], $this->data['doc_id']['value'], $parameters, $errors);
        } else {
            $parameters = array();
            
/*
The following is a temporary solution to pasrameters being cleared because they are not submitted (only those updated are actually "submitted").
The solution is to fetch all existing parameters' values and then overwrite the ones "submitted".
*/
            if (isset($this->data['doc_id']['value'])) {
                if (!$parameters = getDocParameters($this->data['doc_id']['value'])) {
                    $parameters = array();
                }
            }
            
            for ($i = 0; $i != count($this->parameters); $i++) {
                if (isset($this->data[$this->parameters[$i]]['modified'])) {
                    $parameters[$this->parameters[$i]] = $this->data[$this->parameters[$i]]['value'];
                }
            }
            if ($parameters) {
                recursive('mysql_real_escape_string', $parameters);
                saveDocParameters($this->data['doc_type']['value'], $this->data['doc_id']['value'], $parameters, $errors);
            }
        }
        
        return $this->data['doc_id']['value'];
    }
    
    ##
    
    protected function delete() {
        mysql_query("DELETE FROM sys_docs WHERE doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
        mysql_query("DELETE FROM sys_docs_params WHERE doc_id = '" . mysql_real_escape_string($this->data['doc_id']['value']) . "'");
    }
}

?>