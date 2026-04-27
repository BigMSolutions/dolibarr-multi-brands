<?php
/* MultiBrands Module for Dolibarr - v1.2.8
 * http://www.atlasbase.net
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/multibrands/class/multibrand.class.php');

/**
 * Trigger class
 */
class InterfaceMultiBrandsWorkflow extends DolibarrTriggers
{
    public $name = 'MultiBrandsWorkflow';
    public $family = 'multibrands';
    public $description = "Auto-assign brand to proposals, invoices, orders from thirdparty default";
    public $version = '1.2.8';
    public $picto = 'label';
    // Handled events: PROPAL_CREATE, PROPAL_MODIFY, FACTURE_CREATE, FACTURE_MODIFY, COMMANDE_CREATE, COMMANDE_MODIFY

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        global $db;
        if (empty($conf->multibrands->enabled)) return 0;

        switch ($action) {
            case 'PROPAL_CREATE':
            case 'BILL_CREATE':
            case 'FACTURE_CREATE':
            case 'ORDER_CREATE':
            case 'COMMANDE_CREATE':
                break;
            default:
                return 0;
        }

        if (!empty($object->socid)) {
            if (empty($object->array_options) || empty($object->array_options['options_brand_code'])) {
                $thirdparty = new Societe($db);
                if ($thirdparty->fetch($object->socid) > 0) {
                    $thirdparty->fetch_optionals();
                    if (!empty($thirdparty->array_options['options_brand_code'])) {
                        if (empty($object->array_options)) {
                            $object->array_options = array();
                        }
                        $object->array_options['options_brand_code'] = $thirdparty->array_options['options_brand_code'];
                        $res = $object->insertExtraFields();
                        if ($res < 0) {
                            dol_syslog('InterfaceMultiBrandsWorkflow::runTrigger insertExtraFields failed: '.$object->error, LOG_ERR);
                        }
                    }
                }
            }
        }

        return 0;
    }
}
