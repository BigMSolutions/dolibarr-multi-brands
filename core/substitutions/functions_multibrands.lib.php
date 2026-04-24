<?php
/* MultiBrands Module for Dolibarr - v1.1.0
 * http://www.atlasbase.net
 */

/**
 * Substitution functions for MultiBrands
 * @param array $substitutionarray
 * @param Translate $outputlangs
 * @param Object $object
 * @return void
 */
function multibrands_completesubstitutionarray(&$substitutionarray, $outputlangs, $object)
{
    global $db, $conf, $mysoc;
    if (empty($conf->multibrands->enabled)) return;

    dol_include_once('/multi-brands/class/multibrand.class.php');
    dol_include_once('/multi-brands/lib/multibrands.lib.php');

    if (!empty($object->table_element) && in_array($object->table_element, array('propal', 'commande', 'facture'))) {
        $brand = multibrands_get_brand_for_object($db, $object);
        if ($brand && $brand->id > 0) {
            $substitutionarray['__MYCOMPANY_NAME__'] = $brand->company_name ?: $mysoc->name;
            $substitutionarray['__MYCOMPANY_ADDRESS__'] = $brand->address ?: $mysoc->address;
            $substitutionarray['__MYCOMPANY_ZIP__'] = $brand->zip ?: $mysoc->zip;
            $substitutionarray['__MYCOMPANY_TOWN__'] = $brand->town ?: $mysoc->town;
            $substitutionarray['__MYCOMPANY_COUNTRY__'] = $brand->country_label ?: $mysoc->country_label;
            $substitutionarray['__MYCOMPANY_EMAIL__'] = $brand->email ?: $mysoc->email;
            $substitutionarray['__MYCOMPANY_PHONE__'] = $brand->phone ?: $mysoc->phone;
            $substitutionarray['__MYCOMPANY_URL__'] = $brand->url ?: $mysoc->url;
            $substitutionarray['__MYCOMPANY_LEGAL_TEXT__'] = $brand->legal_text ?: '';
            $substitutionarray['__MYCOMPANY_FOOTER_TEXT__'] = $brand->footer_text ?: '';
        }
    }
}
