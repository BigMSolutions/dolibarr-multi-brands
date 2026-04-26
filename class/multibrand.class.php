<?php
/* MultiBrands Module for Dolibarr - v1.2.1
 * http://www.atlasbase.net
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class MultiBrand
 * Represents a brand identity within the company
 */
class MultiBrand extends CommonObject
{
    public $element = 'multibrand';
    public $table_element = 'multibrands_brands';
    public $ismultientitymanaged = 1;

    public $label;
    public $code;
    public $logo;
    public $company_name;
    public $address;
    public $zip;
    public $town;
    public $country_id;
    public $country_label;
    public $phone;
    public $email;
    public $url;
    public $bank_account;
    public $legal_text;
    public $footer_text;
    public $color_primary;
    public $color_secondary;
    public $is_default;
    public $active;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function validate()
    {
        global $conf, $langs;
        $this->label = trim($this->label);
        $this->code = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($this->code)));
        if (empty($this->label) || empty($this->code)) {
            $this->errors[] = $langs->trans('ErrorFieldsRequired');
            return false;
        }
        if (strlen($this->code) > 32) {
            $this->errors[] = $langs->trans('ErrorCodeTooLong');
            return false;
        }
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE code = '".$this->db->escape($this->code)."' AND entity = ".((int)$conf->entity);
        if ($this->id > 0) $sql .= " AND rowid != ".((int)$this->id);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            dol_syslog('MultiBrand::validate error: '.$this->db->lasterror(), LOG_ERR);
            return false;
        }
        if ($this->db->num_rows($resql) > 0) {
            $this->errors[] = $langs->trans('ErrorCodeAlreadyExists');
            return false;
        }
        return true;
    }

    public function create(User $user, $notrigger = 0)
    {
        global $conf, $langs;
        $this->id = 0;
        if (!$this->validate()) return -1;

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (
            entity, label, code, logo, company_name, address, zip, town, country_id,
            phone, email, url, bank_account, legal_text, footer_text,
            color_primary, color_secondary, is_default, active, datec
        ) VALUES (
            ".((int) $conf->entity).",
            '".$this->db->escape($this->label)."',
            '".$this->db->escape($this->code)."',
            ".($this->logo ? "'".$this->db->escape($this->logo)."'" : "null").",
            ".($this->company_name ? "'".$this->db->escape($this->company_name)."'" : "null").",
            ".($this->address ? "'".$this->db->escape($this->address)."'" : "null").",
            ".($this->zip ? "'".$this->db->escape($this->zip)."'" : "null").",
            ".($this->town ? "'".$this->db->escape($this->town)."'" : "null").",
            ".($this->country_id ? (int) $this->country_id : "null").",
            ".($this->phone ? "'".$this->db->escape($this->phone)."'" : "null").",
            ".($this->email ? "'".$this->db->escape($this->email)."'" : "null").",
            ".($this->url ? "'".$this->db->escape($this->url)."'" : "null").",
            ".($this->bank_account ? (int) $this->bank_account : "null").",
            ".($this->legal_text ? "'".$this->db->escape($this->legal_text)."'" : "null").",
            ".($this->footer_text ? "'".$this->db->escape($this->footer_text)."'" : "null").",
            '".$this->db->escape($this->color_primary ?: '#000000')."',
            '".$this->db->escape($this->color_secondary ?: '#666666')."',
            ".($this->is_default ? 1 : 0).",
            ".($this->active ? 1 : 0).",
            '".$this->db->idate(dol_now())."'
        )";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

        if ($this->is_default) {
            $sql2 = "UPDATE ".MAIN_DB_PREFIX.$this->table_element
                ." SET is_default = 0 WHERE rowid != ".((int) $this->id)." AND entity = ".((int) $conf->entity);
            $this->db->query($sql2);
        }

        $this->db->commit();
        return $this->id;
    }

    public function fetch($id = 0, $code = '')
    {
        global $conf, $langs;
        $sql = "SELECT t.*, c.label as country_label";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON c.rowid = t.country_id";
        $sql .= " WHERE t.entity = ".((int) $conf->entity);
        if ($id > 0) {
            $sql .= " AND t.rowid = ".((int) $id);
        } elseif (!empty($code)) {
            $sql .= " AND t.code = '".$this->db->escape($code)."'";
        } else {
            return -1;
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->id = (int) $obj->rowid;
                $this->label = $obj->label;
                $this->code = $obj->code;
                $this->logo = $obj->logo;
                $this->company_name = $obj->company_name;
                $this->address = $obj->address;
                $this->zip = $obj->zip;
                $this->town = $obj->town;
                $this->country_id = $obj->country_id;
                $this->country_label = $obj->country_label;
                $this->phone = $obj->phone;
                $this->email = $obj->email;
                $this->url = $obj->url;
                $this->bank_account = $obj->bank_account;
                $this->legal_text = $obj->legal_text;
                $this->footer_text = $obj->footer_text;
                $this->color_primary = $obj->color_primary;
                $this->color_secondary = $obj->color_secondary;
                $this->is_default = $obj->is_default;
                $this->active = $obj->active;
                return 1;
            }
            $this->resetProperties();
            return 0;
        }
        $this->errors[] = $this->db->lasterror();
        return -1;
    }

    public function update(User $user, $notrigger = 0)
    {
        global $conf, $langs;
        if (!$this->validate()) return -1;
        if (empty($this->id)) return -1;

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET
            label = '".$this->db->escape($this->label)."',
            code = '".$this->db->escape($this->code)."',
            logo = ".($this->logo ? "'".$this->db->escape($this->logo)."'" : "null").",
            company_name = ".($this->company_name ? "'".$this->db->escape($this->company_name)."'" : "null").",
            address = ".($this->address ? "'".$this->db->escape($this->address)."'" : "null").",
            zip = ".($this->zip ? "'".$this->db->escape($this->zip)."'" : "null").",
            town = ".($this->town ? "'".$this->db->escape($this->town)."'" : "null").",
            country_id = ".($this->country_id ? (int) $this->country_id : "null").",
            phone = ".($this->phone ? "'".$this->db->escape($this->phone)."'" : "null").",
            email = ".($this->email ? "'".$this->db->escape($this->email)."'" : "null").",
            url = ".($this->url ? "'".$this->db->escape($this->url)."'" : "null").",
            bank_account = ".($this->bank_account ? (int) $this->bank_account : "null").",
            legal_text = ".($this->legal_text ? "'".$this->db->escape($this->legal_text)."'" : "null").",
            footer_text = ".($this->footer_text ? "'".$this->db->escape($this->footer_text)."'" : "null").",
            color_primary = '".$this->db->escape($this->color_primary ?: '#000000')."',
            color_secondary = '".$this->db->escape($this->color_secondary ?: '#666666')."',
            is_default = ".($this->is_default ? 1 : 0).",
            active = ".($this->active ? 1 : 0)."
            WHERE rowid = ".((int) $this->id)." AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        if ($this->is_default) {
            $sql2 = "UPDATE ".MAIN_DB_PREFIX.$this->table_element
                ." SET is_default = 0 WHERE rowid != ".((int) $this->id)." AND entity = ".((int) $conf->entity);
            $this->db->query($sql2);
        }

        $this->db->commit();
        return 1;
    }

    public function delete(User $user, $notrigger = 0)
    {
        global $conf, $langs;
        if (empty($this->id)) return -1;
        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element
            ." WHERE rowid = ".((int) $this->id)." AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        if ($this->logo) {
            $logoPath = DOL_DATA_ROOT.'/multibrands/brands/logos/'.$this->logo;
            if (file_exists($logoPath)) {
                @unlink($logoPath);
            }
            // Also check old location for cleanup
            $oldLogoPath = DOL_DATA_ROOT.'/multibrands/brands/'.$this->logo;
            if (file_exists($oldLogoPath)) {
                @unlink($oldLogoPath);
            }
        }

        $this->db->commit();
        return 1;
    }

    public function getSelectArray()
    {
        global $conf, $langs;
        $brands = array();
        $sql = "SELECT code, label FROM ".MAIN_DB_PREFIX.$this->table_element
            ." WHERE entity = ".((int) $conf->entity)." AND active = 1 ORDER BY label";
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $brands[$obj->code] = $obj->label;
            }
        } else {
            $this->errors[] = $this->db->lasterror();
            dol_syslog('MultiBrand::getSelectArray error: '.$this->db->lasterror(), LOG_ERR);
        }
        return $brands;
    }

    public function getDefaultCode()
    {
        global $conf, $langs;
        $sql = "SELECT code FROM ".MAIN_DB_PREFIX.$this->table_element
            ." WHERE entity = ".((int) $conf->entity)." AND is_default = 1 AND active = 1";
        $sql .= $this->db->plimit(1);
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            $obj = $this->db->fetch_object($resql);
            return $obj->code;
        }
        if (!$resql) {
            $this->errors[] = $this->db->lasterror();
            dol_syslog('MultiBrand::getDefaultCode error: '.$this->db->lasterror(), LOG_ERR);
        }
        return false;
    }

    public function getBrandForThirdparty($socid)
    {
        global $conf;
        $socid = (int) $socid;
        if ($socid <= 0) return false;

        $sql = "SELECT brand_code FROM ".MAIN_DB_PREFIX."societe_extrafields WHERE fk_object = ".$socid;
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql)) {
            $obj = $this->db->fetch_object($resql);
            if (!empty($obj->brand_code)) return $obj->brand_code;
        }
        return false;
    }

    public function getLogoPath()
    {
        if (empty($this->logo)) return '';
        $path = DOL_DATA_ROOT.'/multibrands/brands/logos/'.$this->logo;
        if (file_exists($path)) {
            return $path;
        }
        // Backward compatibility: check old location
        $oldPath = DOL_DATA_ROOT.'/multibrands/brands/'.$this->logo;
        if (file_exists($oldPath)) {
            return $oldPath;
        }
        return $path;
    }

    /**
     * Return the model configuration map (shared by generate and delete)
     * @return array
     */
    public static function getModelConfig()
    {
        return array(
            'propale' => array(
                array('base' => 'pdf_cyan', 'file' => 'pdf_cyan'),
                array('base' => 'pdf_azur', 'file' => 'pdf_azur'),
            ),
            'facture' => array(
                array('base' => 'pdf_crabe', 'file' => 'pdf_crabe'),
            ),
            'commande' => array(
                array('base' => 'pdf_eratosthene', 'file' => 'pdf_eratosthene'),
            ),
        );
    }

    /**
     * Generate PDF model classes for this brand
     * Creates one class per document type (propal, facture, commande)
     * @return array ['created' => [...], 'failed' => [...]]
     */
    public function generatePdfModels()
    {
        global $langs;
        $result = array('created' => array(), 'failed' => array());
        if (empty($this->code)) return $result;

        $models = self::getModelConfig();
        $safeLabel = addslashes($this->label);
        $safeCode = addslashes($this->code);

        foreach ($models as $type => $typeModels) {
            foreach ($typeModels as $info) {
                $className = $info['file'].'_'.$this->code;
                $fileName = $className.'.modules.php';
                $filePath = __DIR__.'/../core/modules/'.$type.'/doc/'.$fileName;

                $content = "<?php\n";
                $content .= "/* MultiBrands Module for Dolibarr - v1.2.1\n";
                $content .= " * Auto-generated PDF model for brand: ".$this->code."\n";
                $content .= " * http://www.atlasbase.net\n";
                $content .= " */\n\n";
                $content .= "require_once DOL_DOCUMENT_ROOT.'/core/modules/".$type."/doc/".$info['base'].".modules.php';\n";
                $content .= "if (!class_exists('".$info['base']."')) return;\n\n";
                $content .= "dol_include_once('/multibrands/class/multibrand.class.php');\n";
                $content .= "dol_include_once('/multibrands/lib/multibrands.lib.php');\n\n";
                $content .= "class ".$className." extends ".$info['base']."\n";
                $content .= "{\n";
                $content .= "    public function __construct(\$db)\n";
                $content .= "    {\n";
                $content .= "        parent::__construct(\$db);\n";
                $content .= "        \$this->name = \"".$className."\";\n";
                $content .= "        \$this->description .= ' (' . dol_escape_htmltag('".$safeLabel."') . ')';\n";
                $content .= "    }\n\n";
                $content .= "    public function write_file(\$object, \$outputlangs, \$srctemplatepath = '', \$hidedetails = 0, \$hidedesc = 0, \$hideref = 0)\n";
                $content .= "    {\n";
                $content .= "        \$brand = new MultiBrand(\$this->db);\n";
                $content .= "        if (\$brand->fetch(0, '".$safeCode."') > 0) {\n";
                $content .= "            \$backup = multibrands_apply_brand_to_mysoc(\$brand);\n";
                $content .= "            try {\n";
                $content .= "                \$result = parent::write_file(\$object, \$outputlangs, \$srctemplatepath, \$hidedetails, \$hidedesc, \$hideref);\n";
                $content .= "            } finally {\n";
                $content .= "                multibrands_restore_mysoc(\$backup);\n";
                $content .= "            }\n";
                $content .= "            return \$result;\n";
                $content .= "        }\n";
                $content .= "        return parent::write_file(\$object, \$outputlangs, \$srctemplatepath, \$hidedetails, \$hidedesc, \$hideref);\n";
                $content .= "    }\n";
                $content .= "}\n";

                $dir = dirname($filePath);
                if (!file_exists($dir)) {
                    dol_mkdir($dir);
                }

                if (@file_put_contents($filePath, $content) !== false) {
                    $result['created'][] = $type.'/'.$fileName;
                } else {
                    $result['failed'][] = $type.'/'.$fileName;
                    dol_syslog('MultiBrand::generatePdfModels failed to write '.$filePath, LOG_ERR);
                }
            }
        }

        return $result;
    }

    /**
     * Delete PDF model classes for this brand
     * @return array ['deleted' => [...], 'failed' => [...]]
     */
    public function deletePdfModels()
    {
        $result = array('deleted' => array(), 'failed' => array());
        if (empty($this->code)) return $result;

        $models = self::getModelConfig();
        foreach ($models as $type => $typeModels) {
            foreach ($typeModels as $info) {
                $fileName = $info['file'].'_'.$this->code.'.modules.php';
                $filePath = __DIR__.'/../core/modules/'.$type.'/doc/'.$fileName;

                if (file_exists($filePath)) {
                    if (@unlink($filePath)) {
                        $result['deleted'][] = $type.'/'.$fileName;
                    } else {
                        $result['failed'][] = $type.'/'.$fileName;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Delete ALL generated PDF model files (used during module removal)
     * Uses glob to find files since the DB table may already be gone
     * @return int number of files deleted
     */
    public static function cleanupAllPdfModels()
    {
        $models = self::getModelConfig();
        $deleted = 0;
        $moduleDir = __DIR__.'/../core/modules/';

        foreach ($models as $type => $typeModels) {
            foreach ($typeModels as $info) {
                $pattern = $moduleDir.$type.'/doc/'.$info['file'].'_*.modules.php';
                $files = glob($pattern);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        // Don't delete the original branded templates (pdf_azur_branded, pdf_cyan_branded)
                        if (basename($file) === $info['file'].'_branded.modules.php') continue;
                        if (@unlink($file)) {
                            $deleted++;
                        }
                    }
                }
            }
        }

        return $deleted;
    }

    private function resetProperties()
    {
        $this->id = 0;
        $this->label = null;
        $this->code = null;
        $this->logo = null;
        $this->company_name = null;
        $this->address = null;
        $this->zip = null;
        $this->town = null;
        $this->country_id = null;
        $this->country_label = null;
        $this->phone = null;
        $this->email = null;
        $this->url = null;
        $this->bank_account = null;
        $this->legal_text = null;
        $this->footer_text = null;
        $this->color_primary = null;
        $this->color_secondary = null;
        $this->is_default = null;
        $this->active = null;
    }
}
