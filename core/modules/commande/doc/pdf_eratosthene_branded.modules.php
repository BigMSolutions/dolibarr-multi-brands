<?php
/* MultiBrands Module for Dolibarr - v1.2.1
 * http://www.atlasbase.net
 */

// Load abstract parent so it's always available during model discovery
require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/doc/pdf_eratosthene.modules.php';

dol_include_once('/multibrands/class/multibrand.class.php');
dol_include_once('/multibrands/lib/multibrands.lib.php');

if (!class_exists('pdf_eratosthene')) return;

class pdf_eratosthene_branded extends pdf_eratosthene
{
    /** @var string Required by Dolibarr model scanner - must match document type */
    public $type = 'pdf';

    public $activeBrand = null;
    public $origMysoc = array();

    public function __construct($db)
    {
        parent::__construct($db);
        $this->name = "eratosthene_branded";
        global $langs;
        $langs->loadLangs(array("multibrands@multibrands"));
        $this->description .= ' '.$langs->trans("PDFMultiBrandsSupport");
    }

    protected function loadBrand($object)
    {
        global $conf;
        if (empty($conf->multibrands->enabled)) return;
        $brand = multibrands_get_brand_for_object($this->db, $object);
        if ($brand && $brand->id > 0) {
            $this->activeBrand = $brand;
            $this->origMysoc = multibrands_apply_brand_to_mysoc($brand);
        }
    }

    protected function restoreMysoc()
    {
        if (!empty($this->origMysoc)) {
            multibrands_restore_mysoc($this->origMysoc);
        }
    }

    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hideref = 0)
    {
        $this->loadBrand($object);
        try {
            $result = parent::write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hideref);
        } finally {
            $this->restoreMysoc();
        }
        return $result;
    }
}
