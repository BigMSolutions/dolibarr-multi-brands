<?php
/* MultiBrands Module for Dolibarr - v1.1.1
 * http://www.atlasbase.net
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/doc/pdf_eratosthene.modules.php';
dol_include_once('/multi-brands/class/multibrand.class.php');
dol_include_once('/multi-brands/lib/multibrands.lib.php');

class pdf_eratosthene_branded extends pdf_eratosthene
{
    public $activeBrand = null;
    public $origMysoc = array();

    public function __construct($db)
    {
        parent::__construct($db);
        $this->name = "eratosthene_branded";
        global $langs;
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

    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        $this->loadBrand($object);
        try {
            $result = parent::write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref);
        } finally {
            $this->restoreMysoc();
        }
        return $result;
    }
}
