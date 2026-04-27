<?php
/* MultiBrands Module for Dolibarr - v1.2.8
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
        if (is_numeric($brandCode)) {
            if ($brand->fetch((int)$brandCode) > 0) {
                return $brand;
            }
        } else {
            if ($brand->fetch(0, $brandCode) > 0) {
                return $brand;
            }
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
        'logo_small' => !empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL) ? $conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL : '',
        'logo_mini' => !empty($conf->global->MAIN_INFO_SOCIETE_LOGO_MINI) ? $conf->global->MAIN_INFO_SOCIETE_LOGO_MINI : '',
        'logo_mysoc' => $mysoc->logo,
        // Back up $mysoc object logo properties — these are what _pagehead() actually reads
        // via $this->emetteur->logo_small / logo_squarish (NOT the $conf globals)
        'logo_small_mysoc' => isset($mysoc->logo_small) ? $mysoc->logo_small : '',
        'logo_squarish_mysoc' => isset($mysoc->logo_squarish) ? $mysoc->logo_squarish : '',
        'use_large_logo' => getDolGlobalString('MAIN_PDF_USE_LARGE_LOGO'),
        'mycompany_dir_output' => (isset($conf->mycompany->dir_output) ? $conf->mycompany->dir_output : DOL_DATA_ROOT.'/mycompany'),
        'temp_logo' => '',
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
        // Copy brand logo to standard mycompany/logos/ directory so all PDF models find it
        $src = $brand->getLogoPath();
        $stdDir = (isset($conf->mycompany->dir_output) ? $conf->mycompany->dir_output : DOL_DATA_ROOT.'/mycompany').'/logos';
        if (file_exists($src) && is_dir($stdDir) && is_writable($stdDir)) {
            $ext = pathinfo($brand->logo, PATHINFO_EXTENSION);
            $tempName = 'multibrands_tmp_'.uniqid().'.'.$ext;
            if (@copy($src, $stdDir.'/'.$tempName)) {
                $backup['temp_logo'] = $tempName;
                $conf->global->MAIN_INFO_SOCIETE_LOGO = $tempName;
                $mysoc->logo = $tempName;
                // Clear small/mini logo constants
                if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL)) {
                    $conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL = '';
                }
                if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_MINI)) {
                    $conf->global->MAIN_INFO_SOCIETE_LOGO_MINI = '';
                }
                // CRITICAL FIX: Dolibarr's _pagehead() reads $this->emetteur->logo_small
                // (which is $mysoc->logo_small), NOT $conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL.
                // By default (when MAIN_PDF_USE_LARGE_LOGO is not set), _pagehead() builds
                // the logo path as: $logodir.'/logos/thumbs/'.$this->emetteur->logo_small
                // If logo_small still points to the original company's thumbnail, the original
                // logo is rendered instead of the brand logo.
                //
                // Fix: Clear logo_small/logo_squarish on $mysoc to prevent thumbnail lookup,
                // and force MAIN_PDF_USE_LARGE_LOGO so the code path uses the full-size
                // $this->emetteur->logo (which we set to the brand's temp copy).
                $mysoc->logo_small = '';
                $mysoc->logo_squarish = '';
                $conf->global->MAIN_PDF_USE_LARGE_LOGO = '1';
                dol_syslog('MultiBrands: copied brand logo to '.$stdDir.'/'.$tempName.', forced MAIN_PDF_USE_LARGE_LOGO');
            } else {
                // Fallback: redirect dir_output to brand logos directory
                $conf->global->MAIN_INFO_SOCIETE_LOGO = $brand->logo;
                $mysoc->logo = $brand->logo;
                $mysoc->logo_small = '';
                $mysoc->logo_squarish = '';
                $conf->global->MAIN_PDF_USE_LARGE_LOGO = '1';
                if (isset($conf->mycompany)) {
                    $conf->mycompany->dir_output = DOL_DATA_ROOT.'/multibrands/brands';
                }
                dol_syslog('MultiBrands: failed to copy logo, fell back to dir_output override + forced large logo');
            }
        } else {
            // Fallback: set dir_output to brand logos directory
            $conf->global->MAIN_INFO_SOCIETE_LOGO = $brand->logo;
            $mysoc->logo = $brand->logo;
            $mysoc->logo_small = '';
            $mysoc->logo_squarish = '';
            $conf->global->MAIN_PDF_USE_LARGE_LOGO = '1';
            if (isset($conf->mycompany)) {
                $conf->mycompany->dir_output = DOL_DATA_ROOT.'/multibrands/brands';
            }
            dol_syslog('MultiBrands: mycompany/logos not writable, fell back to dir_output override + forced large logo');
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
    $conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL = $backup['logo_small'];
    $conf->global->MAIN_INFO_SOCIETE_LOGO_MINI = $backup['logo_mini'];
    $mysoc->logo = $backup['logo_mysoc'];
    // Restore $mysoc object logo properties that _pagehead() reads
    $mysoc->logo_small = $backup['logo_small_mysoc'];
    $mysoc->logo_squarish = $backup['logo_squarish_mysoc'];
    // Restore MAIN_PDF_USE_LARGE_LOGO to its original value
    if (!empty($backup['use_large_logo'])) {
        $conf->global->MAIN_PDF_USE_LARGE_LOGO = $backup['use_large_logo'];
    } else {
        unset($conf->global->MAIN_PDF_USE_LARGE_LOGO);
    }
    if (isset($conf->mycompany)) {
        $conf->mycompany->dir_output = $backup['mycompany_dir_output'];
    }
    // Clean up temp logo file
    if (!empty($backup['temp_logo'])) {
        $stdDir = (isset($conf->mycompany->dir_output) ? $conf->mycompany->dir_output : DOL_DATA_ROOT.'/mycompany').'/logos';
        @unlink($stdDir.'/'.$backup['temp_logo']);
        dol_syslog('MultiBrands: removed temp logo '.$stdDir.'/'.$backup['temp_logo']);
    }
}
