<?php
/* MultiBrands Module for Dolibarr - v1.1.3
 * http://www.atlasbase.net
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/multi-brands/class/multibrand.class.php');

/**
 * Trigger class
 */
class InterfaceMultiBrandsWorkflow extends DolibarrTriggers
{
    public $name = 'MultiBrandsWorkflow';
    public $family = 'multi-brands';
    public $description = "Auto-assign brand to proposals, invoices, orders from thirdparty default";
    public $version = '1.1.3';
    public $picto = 'label';
    // Handled events: PROPAL_CREATE, PROPAL_MODIFY, FACTURE_CREATE, FACTURE_MODIFY, COMMANDE_CREATE, COMMANDE_MODIFY

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $db;
        if (empty($conf->multibrands->enabled)) return 0;

        $brand = new MultiBrand($db);
        $element = '';

        switch ($action) {
            case 'PROPAL_CREATE':
            case 'PROPAL_MODIFY':
                $element = 'propal';
                break;
            case 'FACTURE_CREATE':
            case 'FACTURE_MODIFY':
                $element = 'facture';
                break;
            case 'COMMANDE_CREATE':
            case 'COMMANDE_MODIFY':
                $element = 'commande';
                break;
            default:
                return 0;
        }

        if (empty($object->array_options['options_brand_code']) && !empty($object->socid)) {
            $defaultBrand = $brand->getBrandForThirdparty($object->socid);
            if (!$defaultBrand) $defaultBrand = $brand->getDefaultCode();
            if ($defaultBrand) {
                $object->array_options['options_brand_code'] = $defaultBrand;
                $sql = "UPDATE ".MAIN_DB_PREFIX.$element."_extrafields SET brand_code = '".$db->escape($defaultBrand)."' WHERE fk_object = ".((int)$object->id);
                $resql = $db->query($sql);
                if (!$resql) {
                    dol_syslog('InterfaceMultiBrandsWorkflow::runTrigger error: '.$db->lasterror(), LOG_ERR);
                }
            }
        }

        return 0;
    }
}
