<?php
/* MultiBrands Module for Dolibarr - v1.1.3
 * http://www.atlasbase.net
 */

/**
 * \file    multi-brands/core/modules/modMultiBrands.class.php
 * \ingroup multi-brands
 * \brief   MultiBrands module descriptor v1.1.3
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modMultiBrands extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 501000;
        $this->rights_class = 'multibrands';
        $this->family = "products";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Issue quotations and documents under multiple brands / DBAs from a single company";
        $this->descriptionlong = "Define multiple brand identities (logo, name, colors, address, legal text) and dynamically apply them to proposals, invoices, orders and other PDF documents based on a custom field selection.";
        $this->version = '1.1.3';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'label';

        $this->module_parts = array(
            'triggers' => 1,
            'substitutions' => 1,
            'modulepart' => array('multibrands' => array('dir' => 'multibrands/brands')),
        );

        $this->dirs = array(
            '/multibrands/brands',
            '/multibrands/temp',
        );

        $this->config_page_url = array("setup.php@multi-brands");
        $this->depends = array('modSociete');
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array("multibrands@multi-brands");
        $this->need_dolibarr_version = array(17, 0);

        $this->const = array(
            array('MULTIBRANDS_MAIN_LOGO_PATH', 'chaine', 'multibrands/brands', 'Logo storage directory', 1),
        );

        $this->rights = array();
        $r = 0;
        $this->rights[$r][0] = 501001;
        $this->rights[$r][1] = 'Read brand configurations';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 501002;
        $this->rights[$r][1] = 'Create/Modify brand configurations';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->menu = array();
        $r = 0;
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=tools',
            'type' => 'left',
            'titre' => 'MultiBrands',
            'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
            'mainmenu' => 'tools',
            'leftmenu' => 'multibrands',
            'url' => '/multi-brands/admin/setup.php',
            'langs' => 'multibrands@multi-brands',
            'position' => 100,
            'enabled' => '$conf->multibrands->enabled',
            'perms' => '$user->rights->multibrands->read',
            'target' => '',
            'user' => 2
        );
    }

    public function init($options = '')
    {
        global $conf;

        $sql = array();

        if ($this->db->type == 'pgsql') {
            $sql[] = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."multibrands_brands (
                rowid serial PRIMARY KEY,
                entity integer DEFAULT 1 NOT NULL,
                label varchar(128) NOT NULL,
                code varchar(32) NOT NULL,
                logo varchar(255),
                company_name varchar(128),
                address text,
                zip varchar(32),
                town varchar(128),
                country_id integer,
                phone varchar(32),
                email varchar(128),
                url varchar(255),
                bank_account integer,
                legal_text text,
                footer_text text,
                color_primary varchar(7) DEFAULT '#000000',
                color_secondary varchar(7) DEFAULT '#666666',
                is_default smallint DEFAULT 0,
                active smallint DEFAULT 1,
                datec timestamp,
                tms timestamp DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uk_multibrands_code UNIQUE (code)
            );";
            $sql[] = "CREATE INDEX IF NOT EXISTS idx_multibrands_entity ON ".MAIN_DB_PREFIX."multibrands_brands(entity);";
            $sql[] = "CREATE INDEX IF NOT EXISTS idx_multibrands_active ON ".MAIN_DB_PREFIX."multibrands_brands(active);";

            $sql[] = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."multibrands_brands_extrafields (
                rowid serial PRIMARY KEY,
                tms timestamp DEFAULT CURRENT_TIMESTAMP,
                fk_object integer NOT NULL,
                import_key varchar(14)
            );";
        } else {
            $sql[] = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."multibrands_brands (
                rowid integer AUTO_INCREMENT PRIMARY KEY,
                entity integer DEFAULT 1 NOT NULL,
                label varchar(128) NOT NULL,
                code varchar(32) NOT NULL,
                logo varchar(255),
                company_name varchar(128),
                address text,
                zip varchar(32),
                town varchar(128),
                country_id integer,
                phone varchar(32),
                email varchar(128),
                url varchar(255),
                bank_account integer,
                legal_text text,
                footer_text text,
                color_primary varchar(7) DEFAULT '#000000',
                color_secondary varchar(7) DEFAULT '#666666',
                is_default tinyint DEFAULT 0,
                active tinyint DEFAULT 1,
                datec datetime,
                tms timestamp,
                UNIQUE KEY uk_multibrands_code (code),
                INDEX idx_entity (entity),
                INDEX idx_active (active)
            ) ENGINE=innodb;";

            $sql[] = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."multibrands_brands_extrafields (
                rowid integer AUTO_INCREMENT PRIMARY KEY,
                tms timestamp,
                fk_object integer NOT NULL,
                import_key varchar(14)
            ) ENGINE=innodb;";
        }

        if (!empty($conf->multibrands->enabled)) {
            $dir = DOL_DATA_ROOT.'/multibrands/brands';
            if (!file_exists($dir)) {
                dol_mkdir($dir);
            }
        }

        $this->addExtrafields();

        // Clear extrafields cache so new/updated fields appear immediately
        $cacheFile = DOL_DATA_ROOT.'/extrafields/cache_' . $conf->entity . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }

        // Run _init first to create tables (important on fresh install)
        $result = $this->_init($sql, $options);

        // Regenerate PDF model classes for all existing brands (after table exists)
        dol_include_once('/multi-brands/class/multibrand.class.php');
        $sqlBrands = "SELECT code FROM ".MAIN_DB_PREFIX."multibrands_brands WHERE entity = ".((int)$conf->entity)." AND active = 1";
        $resqlBrands = $this->db->query($sqlBrands);
        if ($resqlBrands) {
            while ($objBrand = $this->db->fetch_object($resqlBrands)) {
                $brand = new MultiBrand($this->db);
                if ($brand->fetch(0, $objBrand->code) > 0) {
                    $brand->generatePdfModels();
                }
            }
        }

        return $result;
    }

    private function addExtrafields()
    {
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);

        // Use 'sellist' type so the dropdown is populated dynamically from the brands table
        $param = array('options' => array('multibrands_brands:label:code::active=1 AND entity=$ENTITY$' => ''));

        $targets = array(
            'propal' => 'Brand',
            'societe' => 'Default Brand',
            'facture' => 'Brand',
            'commande' => 'Brand'
        );

        foreach ($targets as $element => $label) {
            // Check if extrafield already exists
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."extrafields"
                ." WHERE name = 'brand_code' AND elementtype = '".$this->db->escape($element)."'";
            $resql = $this->db->query($sql);
            $exists = ($resql && $this->db->num_rows($resql) > 0);

            if ($exists) {
                // Force update param to ensure the sellist query is correct
                $sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."extrafields"
                    ." SET param = '".$this->db->escape(json_encode($param))."',"
                    ." type = 'sellist',"
                    ." label = '".$this->db->escape($label)."'"
                    ." WHERE name = 'brand_code' AND elementtype = '".$this->db->escape($element)."'";
                $resUpdate = $this->db->query($sqlUpdate);
                if (!$resUpdate) {
                    dol_syslog('MultiBrands::addExtrafields update error for '.$element.': '.$this->db->lasterror(), LOG_ERR);
                }
            } else {
                $result = $extrafields->addExtraField(
                    'brand_code',
                    $label,
                    'sellist',
                    1,
                    '',
                    $element,
                    0,
                    0,
                    '',
                    $param,
                    1,
                    '',
                    '',
                    0,
                    '',
                    '',
                    'multibrands@multi-brands'
                );
                if ($result < 0) {
                    dol_syslog('MultiBrands::addExtrafields error for '.$element.': '.$extrafields->error, LOG_ERR);
                }
            }
        }
    }

    public function remove($options = '')
    {
        // Clean up generated PDF model files before dropping tables
        dol_include_once('/multi-brands/class/multibrand.class.php');
        MultiBrand::cleanupAllPdfModels();

        $sql = array();
        $sql[] = "DROP TABLE IF EXISTS ".MAIN_DB_PREFIX."multibrands_brands;";
        $sql[] = "DROP TABLE IF EXISTS ".MAIN_DB_PREFIX."multibrands_brands_extrafields;";

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        $res = $extrafields->delete('brand_code', 'propal');
        if ($res < 0) dol_syslog('MultiBrands::remove extrafield delete error: '.$extrafields->error, LOG_ERR);
        $res = $extrafields->delete('brand_code', 'societe');
        if ($res < 0) dol_syslog('MultiBrands::remove extrafield delete error: '.$extrafields->error, LOG_ERR);
        $res = $extrafields->delete('brand_code', 'facture');
        if ($res < 0) dol_syslog('MultiBrands::remove extrafield delete error: '.$extrafields->error, LOG_ERR);
        $res = $extrafields->delete('brand_code', 'commande');
        if ($res < 0) dol_syslog('MultiBrands::remove extrafield delete error: '.$extrafields->error, LOG_ERR);

        return $this->_remove($sql, $options);
    }
}
