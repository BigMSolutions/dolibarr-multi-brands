<?php
/* MultiBrands Module for Dolibarr - v1.2.8
 * http://www.atlasbase.net
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/doc/pdf_cyan.modules.php';

dol_include_once('/multibrands/class/multibrand.class.php');
dol_include_once('/multibrands/lib/multibrands.lib.php');

if (!class_exists('pdf_cyan')) return;

class pdf_cyan_branded extends pdf_cyan
{
    /** @var string Required by Dolibarr model scanner */
    public $type = 'pdf';

    public $activeBrand = null;
    public $origMysoc = array();

    public function __construct($db)
    {
        parent::__construct($db);
        $this->name = "cyan_branded";
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
