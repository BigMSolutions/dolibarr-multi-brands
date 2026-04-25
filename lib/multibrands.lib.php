<?php
/* MultiBrands Module for Dolibarr - v1.1.3
 * http://www.atlasbase.net
 */

/**
 * Get active brand for an object
 * @param DoliDB $db Database handler
 * @param object $object Dolibarr object (propal, facture, commande, etc.)
 * @return MultiBrand|null
 */
function multibrands_get_brand_for_object($db, $object)
{
    global $conf;
    if (empty($conf->multibrands->enabled)) return null;

    $brand = new MultiBrand($db);
    $brandCode = '';

    // 1. Object extrafield
    if (is_object($object) && !empty($object->id)) {
        if (!empty($object->array_options['options_brand_code'])) {
            $brandCode = $object->array_options['options_brand_code'];
        } else {
            $table = $object->table_element;
            $sql = "SELECT brand_code FROM ".MAIN_DB_PREFIX.$table."_extrafields WHERE fk_object = ".((int)$object->id);
            $resql = $db->query($sql);
            if ($resql && $db->num_rows($resql)) {
                $obj = $db->fetch_object($resql);
                $brandCode = $obj->brand_code;
            }
        }
    }

    // 2. Thirdparty default
    if (empty($brandCode) && is_object($object) && !empty($object->socid)) {
        $brandCode = $brand->getBrandForThirdparty($object->socid);
    }

    // 3. Global default
    if (empty($brandCode)) {
        $brandCode = $brand->getDefaultCode();
    }

    if (!empty($brandCode)) {
        if ($brand->fetch(0, $brandCode) > 0) {
            return $brand;
        }
    }
    return null;
}

/**
 * Apply brand to mysoc temporarily (returns backup array)
 * @param MultiBrand $brand
 * @return array backup of original mysoc values
 */
function multibrands_apply_brand_to_mysoc($brand)
{
    global $mysoc, $conf;
    $backup = array(
        'name' => $mysoc->name,
        'address' => $mysoc->address,
        'zip' => $mysoc->zip,
        'town' => $mysoc->town,
        'country_id' => $mysoc->country_id,
        'country_label' => $mysoc->country_label,
        'phone' => $mysoc->phone,
        'email' => $mysoc->email,
        'url' => $mysoc->url,
        'logo' => !empty($conf->global->MAIN_INFO_SOCIETE_LOGO) ? $conf->global->MAIN_INFO_SOCIETE_LOGO : '',
        'logo_mysoc' => $mysoc->logo,
        'mycompany_dir_output' => (isset($conf->mycompany->dir_output) ? $conf->mycompany->dir_output : DOL_DATA_ROOT.'/mycompany'),
    );
    if ($brand->company_name) $mysoc->name = $brand->company_name;
    if ($brand->address) $mysoc->address = $brand->address;
    if ($brand->zip) $mysoc->zip = $brand->zip;
    if ($brand->town) $mysoc->town = $brand->town;
    if ($brand->country_id) $mysoc->country_id = $brand->country_id;
    if ($brand->country_label) $mysoc->country_label = $brand->country_label;
    if ($brand->phone) $mysoc->phone = $brand->phone;
    if ($brand->email) $mysoc->email = $brand->email;
    if ($brand->url) $mysoc->url = $brand->url;
    if ($brand->logo) {
        $conf->global->MAIN_INFO_SOCIETE_LOGO = $brand->logo;
        $mysoc->logo = $brand->logo;
        if (isset($conf->mycompany)) {
            $conf->mycompany->dir_output = DOL_DATA_ROOT.'/multibrands/brands';
        }
    }
    return $backup;
}

/**
 * Restore mysoc from backup
 * @param array $backup
 */
function multibrands_restore_mysoc($backup)
{
    global $mysoc, $conf;
    $mysoc->name = $backup['name'];
    $mysoc->address = $backup['address'];
    $mysoc->zip = $backup['zip'];
    $mysoc->town = $backup['town'];
    $mysoc->country_id = $backup['country_id'];
    $mysoc->country_label = $backup['country_label'];
    $mysoc->phone = $backup['phone'];
    $mysoc->email = $backup['email'];
    $mysoc->url = $backup['url'];
    $conf->global->MAIN_INFO_SOCIETE_LOGO = $backup['logo'];
    $mysoc->logo = $backup['logo_mysoc'];
    if (isset($conf->mycompany)) {
        $conf->mycompany->dir_output = $backup['mycompany_dir_output'];
    }
}
