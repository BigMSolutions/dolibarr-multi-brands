<?php
/* MultiBrands Module for Dolibarr - v1.2.1
 * http://www.atlasbase.net
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * ---- BULLETPROOF INCLUDE ----
 * Dolibarr installations vary in directory structure.
 * We try multiple relative paths to locate main.inc.php.
 */

$res = 0;
if (!$res && file_exists(__DIR__.'/../../main.inc.php')) {
    $res = @include_once __DIR__.'/../../main.inc.php';
}
if (!$res && file_exists(__DIR__.'/../../../main.inc.php')) {
    $res = @include_once __DIR__.'/../../../main.inc.php';
}
if (!$res && file_exists(__DIR__.'/../../../../main.inc.php')) {
    $res = @include_once __DIR__.'/../../../../main.inc.php';
}
if (!$res && !empty($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'].'/../main.inc.php')) {
    $res = @include_once $_SERVER['DOCUMENT_ROOT'].'/../main.inc.php';
}
if (!$res && !empty($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'].'/main.inc.php')) {
    $res = @include_once $_SERVER['DOCUMENT_ROOT'].'/main.inc.php';
}
if (!$res) {
    http_response_code(500);
    header('Content-Type: text/plain');
    die("FATAL: Cannot locate main.inc.php. Tried paths:\n"
        . __DIR__.'/../../main.inc.php'."\n"
        . __DIR__.'/../../../main.inc.php'."\n"
        . __DIR__.'/../../../../main.inc.php'."\n"
        . (empty($_SERVER['DOCUMENT_ROOT']) ? '' : $_SERVER['DOCUMENT_ROOT'].'/../main.inc.php')."\n"
        . (empty($_SERVER['DOCUMENT_ROOT']) ? '' : $_SERVER['DOCUMENT_ROOT'].'/main.inc.php')."\n"
        . "Current __DIR__: ".__DIR__."\n"
        . "DOCUMENT_ROOT: ".(empty($_SERVER['DOCUMENT_ROOT']) ? '(empty)' : $_SERVER['DOCUMENT_ROOT'])."\n"
    );
}

// If we got here but $db is not defined, something is very wrong
if (empty($db) || !is_object($db)) {
    http_response_code(500);
    header('Content-Type: text/plain');
    die("FATAL: main.inc.php loaded but \$db is not initialized.\n"
        . "This usually means main.inc.php was found but is not the real Dolibarr main.inc.php.\n"
        . "Current __DIR__: ".__DIR__."\n"
    );
}

// Now load module-specific files
$moduleRoot = __DIR__.'/../';
require_once $moduleRoot.'class/multibrand.class.php';

// Load admin libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Load translations
$langs->loadLangs(array("admin", "multibrands@multibrands"));



// ---- PERMISSION CHECK ----
if (!$user->admin && empty($user->rights->multibrands->write)) {
    accessforbidden($langs->trans('NotAllowed'));
}

// ---- PARAMETERS ----
$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$confirm = GETPOST('confirm', 'alpha');

$brand = new MultiBrand($db);
$form = new Form($db);

// ---- ACTIONS ----

// ADD new brand
if ($action == 'add') {
    if (!GETPOSTISSET('token') || GETPOST('token', 'alpha') != newToken()) {
        setEventMessages($langs->trans('SecurityTokenHasExpired'), null, 'errors');
        $action = 'create';
    } else {
        $error = 0;

        $brand->label = GETPOST('label', 'alphanohtml');
        $brand->code = preg_replace('/[^a-z0-9_]/', '_', strtolower(GETPOST('code', 'alphanohtml')));
        $brand->company_name = GETPOST('company_name', 'alphanohtml');
        $brand->address = GETPOST('address', 'alphanohtml');
        $brand->zip = GETPOST('zip', 'alphanohtml');
        $brand->town = GETPOST('town', 'alphanohtml');
        $brand->country_id = GETPOST('country_id', 'int');
        $brand->phone = GETPOST('phone', 'alphanohtml');
        $brand->email = GETPOST('email', 'alphanohtml');
        $brand->url = GETPOST('url', 'alphanohtml');
        $brand->bank_account = GETPOST('bank_account', 'int');
        $brand->legal_text = GETPOST('legal_text', 'restricthtml');
        $brand->footer_text = GETPOST('footer_text', 'restricthtml');
        $brand->color_primary = GETPOST('color_primary', 'alphanohtml') ?: '#000000';
        $brand->color_secondary = GETPOST('color_secondary', 'alphanohtml') ?: '#666666';
        $brand->is_default = GETPOST('is_default', 'int') ? 1 : 0;
        $brand->active = GETPOST('active', 'int') ? 1 : 0;

        // Logo upload
        if (!empty($_FILES['logo']['tmp_name'])) {
            if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = array(
                    UPLOAD_ERR_INI_SIZE => 'ErrorUploadIniSize',
                    UPLOAD_ERR_FORM_SIZE => 'ErrorUploadFormSize',
                    UPLOAD_ERR_PARTIAL => 'ErrorUploadPartial',
                    UPLOAD_ERR_NO_FILE => 'ErrorUploadNoFile',
                    UPLOAD_ERR_NO_TMP_DIR => 'ErrorUploadNoTmpDir',
                    UPLOAD_ERR_CANT_WRITE => 'ErrorUploadCantWrite',
                    UPLOAD_ERR_EXTENSION => 'ErrorUploadExtension',
                );
                $errKey = isset($uploadErrors[$_FILES['logo']['error']]) ? $uploadErrors[$_FILES['logo']['error']] : 'ErrorFileUploadFailed';
                setEventMessages($langs->trans($errKey), null, 'errors');
                $error++;
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
                finfo_close($finfo);
                $allowed = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

                if (!in_array($mime, $allowed) || !in_array(strtolower($ext), $allowed_ext)) {
                    setEventMessages($langs->trans("ErrorInvalidImageFile"), null, 'errors');
                    $error++;
                } elseif ($_FILES['logo']['size'] > 2097152) {
                    setEventMessages($langs->trans("ErrorFileTooLarge"), null, 'errors');
                    $error++;
                } else {
                    $dir = DOL_DATA_ROOT.'/multibrands/brands/logos';
                    if (!file_exists($dir)) {
                        dol_mkdir($dir);
                    }
                    if (!is_writable($dir)) {
                        setEventMessages($langs->trans("ErrorDirectoryNotWritable").": ".$dir, null, 'errors');
                        $error++;
                    } else {
                        $destfile = $dir.'/'.$brand->code.'_'.dol_sanitizeFileName($_FILES['logo']['name']);
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destfile)) {
                            $brand->logo = $brand->code.'_'.dol_sanitizeFileName($_FILES['logo']['name']);
                        } else {
                            setEventMessages($langs->trans("ErrorFileUploadFailed"), null, 'errors');
                            $error++;
                        }
                    }
                }
            }
        }

        if (!$error) {
            $result = $brand->create($user);
            if ($result > 0) {
                // Generate PDF model classes for this brand
                $pdfResult = $brand->generatePdfModels();
                if (!empty($pdfResult['failed'])) {
                    setEventMessages($langs->trans("PdfModelsFailed").': '.implode(', ', $pdfResult['failed']), null, 'warnings');
                }
                setEventMessages($langs->trans("BrandCreated"), null, 'mesgs');
                header("Location: ".$_SERVER["PHP_SELF"]);
                exit;
            } else {
                setEventMessages($brand->error, $brand->errors, 'errors');
                $action = 'create';
            }
        } else {
            $action = 'create';
        }
    }
}

// UPDATE brand
if ($action == 'update' && $id > 0) {
    if (!GETPOSTISSET('token') || GETPOST('token', 'alpha') != newToken()) {
        setEventMessages($langs->trans('SecurityTokenHasExpired'), null, 'errors');
        $action = 'edit';
    } else {
        $result = $brand->fetch($id);
        if ($result <= 0) {
            setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
            $action = '';
        } else {
            $error = 0;

            $old_logo = $brand->logo;
            $old_code = $brand->code;

            $brand->label = GETPOST('label', 'alphanohtml');
            $brand->code = preg_replace('/[^a-z0-9_]/', '_', strtolower(GETPOST('code', 'alphanohtml')));
            $brand->company_name = GETPOST('company_name', 'alphanohtml');
            $brand->address = GETPOST('address', 'alphanohtml');
            $brand->zip = GETPOST('zip', 'alphanohtml');
            $brand->town = GETPOST('town', 'alphanohtml');
            $brand->country_id = GETPOST('country_id', 'int');
            $brand->phone = GETPOST('phone', 'alphanohtml');
            $brand->email = GETPOST('email', 'alphanohtml');
            $brand->url = GETPOST('url', 'alphanohtml');
            $brand->bank_account = GETPOST('bank_account', 'int');
            $brand->legal_text = GETPOST('legal_text', 'restricthtml');
            $brand->footer_text = GETPOST('footer_text', 'restricthtml');
            $brand->color_primary = GETPOST('color_primary', 'alphanohtml') ?: '#000000';
            $brand->color_secondary = GETPOST('color_secondary', 'alphanohtml') ?: '#666666';
            $brand->is_default = GETPOST('is_default', 'int') ? 1 : 0;
            $brand->active = GETPOST('active', 'int') ? 1 : 0;

            // If code changed and there's an existing logo but no new upload, rename logo file
            if ($old_logo && $old_code != $brand->code && empty($_FILES['logo']['tmp_name'])) {
                $dir = DOL_DATA_ROOT.'/multibrands/brands/logos';
                $old_path = $dir.'/'.$old_logo;
                $new_logo_name = preg_replace('/^'.preg_quote($old_code.'_', '/').'/', $brand->code.'_', $old_logo);
                if (file_exists($old_path) && $old_logo != $new_logo_name) {
                    @rename($old_path, $dir.'/'.$new_logo_name);
                    $brand->logo = $new_logo_name;
                    $old_logo = $new_logo_name;
                }
            }

            if (!empty($_FILES['logo']['tmp_name'])) {
                if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = array(
                        UPLOAD_ERR_INI_SIZE => 'ErrorUploadIniSize',
                        UPLOAD_ERR_FORM_SIZE => 'ErrorUploadFormSize',
                        UPLOAD_ERR_PARTIAL => 'ErrorUploadPartial',
                        UPLOAD_ERR_NO_FILE => 'ErrorUploadNoFile',
                        UPLOAD_ERR_NO_TMP_DIR => 'ErrorUploadNoTmpDir',
                        UPLOAD_ERR_CANT_WRITE => 'ErrorUploadCantWrite',
                        UPLOAD_ERR_EXTENSION => 'ErrorUploadExtension',
                    );
                    $errKey = isset($uploadErrors[$_FILES['logo']['error']]) ? $uploadErrors[$_FILES['logo']['error']] : 'ErrorFileUploadFailed';
                    setEventMessages($langs->trans($errKey), null, 'errors');
                    $error++;
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
                    finfo_close($finfo);
                    $allowed = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');

                    if (!in_array($mime, $allowed) || !in_array(strtolower($ext), $allowed_ext)) {
                        setEventMessages($langs->trans("ErrorInvalidImageFile"), null, 'errors');
                        $error++;
                    } elseif ($_FILES['logo']['size'] > 2097152) {
                        setEventMessages($langs->trans("ErrorFileTooLarge"), null, 'errors');
                        $error++;
                    } else {
                        $dir = DOL_DATA_ROOT.'/multibrands/brands/logos';
                        if (!file_exists($dir)) dol_mkdir($dir);
                        if (!is_writable($dir)) {
                            setEventMessages($langs->trans("ErrorDirectoryNotWritable").": ".$dir, null, 'errors');
                            $error++;
                        } else {
                            $destfile = $dir.'/'.$brand->code.'_'.dol_sanitizeFileName($_FILES['logo']['name']);
                            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destfile)) {
                                $brand->logo = $brand->code.'_'.dol_sanitizeFileName($_FILES['logo']['name']);
                                if ($old_logo && $old_logo != $brand->logo) {
                                    @unlink($dir.'/'.$old_logo);
                                }
                            } else {
                                setEventMessages($langs->trans("ErrorFileUploadFailed"), null, 'errors');
                                $error++;
                            }
                        }
                    }
                }
            }

            if (!$error) {
                $result = $brand->update($user);
                if ($result > 0) {
                    // Regenerate PDF model classes (code may have changed)
                    $brand->deletePdfModels();
                    $pdfResult = $brand->generatePdfModels();
                    if (!empty($pdfResult['failed'])) {
                        setEventMessages($langs->trans("PdfModelsFailed").': '.implode(', ', $pdfResult['failed']), null, 'warnings');
                    }
                    setEventMessages($langs->trans("BrandUpdated"), null, 'mesgs');
                    header("Location: ".$_SERVER["PHP_SELF"]);
                    exit;
                } else {
                    setEventMessages($brand->error, $brand->errors, 'errors');
                    $action = 'edit';
                }
            } else {
                $action = 'edit';
            }
        }
    }
}

// REPAIR EXTRAFIELDS (one-click fix for missing "Additional attributes" tabs)
if ($action == 'repair_extrafields') {
    if (!GETPOSTISSET('token') || !verifCsrfToken()) {
        setEventMessages($langs->trans('SecurityTokenHasExpired'), null, 'errors');
    } else {
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($db);
        $param = array('options' => array('multibrands_brands:label:code::active=1 AND entity=$ENTITY$' => ''));
        $targets = array(
            'propal' => 'Brand',
            'societe' => 'Default Brand',
            'facture' => 'Brand',
            'commande' => 'Brand'
        );
        $repaired = array();
        $failed = array();
        foreach ($targets as $element => $label) {
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."extrafields WHERE name = 'brand_code' AND elementtype = '".$db->escape($element)."'";
            $resql = $db->query($sql);
            $exists = ($resql && $db->num_rows($resql) > 0);
            if ($exists) {
                $sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."extrafields"
                    ." SET param = '".$db->escape(json_encode($param))."',"
                    ." type = 'sellist',"
                    ." label = '".$db->escape($label)."',"
                    ." list = '1',"
                    ." printable = 1,"
                    ." langfile = 'multibrands@multibrands'"
                    ." WHERE name = 'brand_code' AND elementtype = '".$db->escape($element)."'";
                $resUpdate = $db->query($sqlUpdate);
                if ($resUpdate) {
                    $repaired[] = $element.' (updated)';
                } else {
                    $failed[] = $element.' (update error: '.$db->lasterror().')';
                }
            } else {
                $result = $extrafields->addExtraField(
                    'brand_code', $label, 'sellist', 1, '', $element,
                    0, 0, '', $param, 1, '', '', 0, '', '', 'multibrands@multibrands'
                );
                if ($result > 0) {
                    $repaired[] = $element.' (created)';
                } else {
                    $failed[] = $element.' (create error: '.$extrafields->error.')';
                }
            }
        }
        // Clear extrafields cache
        $cacheFile = DOL_DATA_ROOT.'/extrafields/cache_' . $conf->entity . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
        if (!empty($repaired)) {
            setEventMessages($langs->trans("ExtrafieldsRepaired").': '.implode(', ', $repaired), null, 'mesgs');
        }
        if (!empty($failed)) {
            setEventMessages($langs->trans("ExtrafieldsRepairFailed").': '.implode(', ', $failed), null, 'errors');
        }
    }
    // PRG pattern: redirect to avoid re-submission on F5
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}

// DELETE brand
if ($action == 'confirm_delete' && $confirm == 'yes' && $id > 0) {
    if (!GETPOSTISSET('token') || GETPOST('token', 'alpha') != newToken()) {
        setEventMessages($langs->trans('SecurityTokenHasExpired'), null, 'errors');
    } else {
        $brand->fetch($id);
        // Delete PDF model classes first
        $brand->deletePdfModels();
        $result = $brand->delete($user);
        if ($result > 0) {
            setEventMessages($langs->trans("BrandDeleted"), null, 'mesgs');
        } else {
            setEventMessages($brand->error, $brand->errors, 'errors');
        }
    }
    $action = '';
}

// ---- VIEW ----

llxHeader('', $langs->trans("MultiBrandsSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MultiBrandsSetup"), $linkback, 'title_setup');

$head = array();
$h = 0;
$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("Settings");
$head[$h][2] = 'settings';
$head[$h][3] = '';

print dol_get_fiche_head($head, 'settings', $langs->trans("MultiBrands"), -1, "multibrands@multibrands");

// CREATE / EDIT FORM
if ($action == 'create' || $action == 'edit') {
    if ($action == 'edit' && $id > 0) {
        $brand->fetch($id);
    }

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="'.($action == 'edit' ? 'update' : 'add').'">';
    if ($action == 'edit') print '<input type="hidden" name="id" value="'.((int)$id).'">';

    print '<table class="border centpercent">';

    print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
    print '<input type="text" name="label" class="minwidth200" value="'.dol_escape_htmltag($brand->label).'" required>';
    print '</td></tr>';

    print '<tr><td class="fieldrequired">'.$langs->trans("Code").'</td><td>';
    print '<input type="text" name="code" class="minwidth200" value="'.dol_escape_htmltag($brand->code).'" required>';
    print ' <span class="opacitymedium">'.$langs->trans("CodeInfo").'</span>';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Logo").'</td><td>';
    print '<input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp">';
    print ' <span class="opacitymedium">'.$langs->trans("LogoMaxSize").'</span>';
    if ($brand->logo) {
        $logoPath = DOL_DATA_ROOT.'/multibrands/brands/logos/'.$brand->logo;
        if (file_exists($logoPath)) {
            print '<br><img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=multibrands&file='.urlencode($brand->logo).'&cache=0&entity='.((int)$conf->entity).'" style="max-height:60px; margin-top:5px;">';
        } else {
            print '<br><span class="warning">'.$langs->trans("LogoFileMissing").': '.dol_escape_htmltag($brand->logo).'</span>';
        }
    }
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("CompanyName").'</td><td>';
    print '<input type="text" name="company_name" class="minwidth300" value="'.dol_escape_htmltag($brand->company_name).'">';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Address").'</td><td>';
    print '<textarea name="address" class="quatrevingtpercent" rows="2">'.dol_escape_htmltag($brand->address).'</textarea>';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
    print '<input type="text" name="zip" class="minwidth100" value="'.dol_escape_htmltag($brand->zip).'"> ';
    print '<input type="text" name="town" class="minwidth200" value="'.dol_escape_htmltag($brand->town).'">';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Country").'</td><td>';
    print $form->select_country($brand->country_id, 'country_id');
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Phone").'</td><td>';
    print '<input type="text" name="phone" class="minwidth200" value="'.dol_escape_htmltag($brand->phone).'">';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Email").'</td><td>';
    print '<input type="email" name="email" class="minwidth200" value="'.dol_escape_htmltag($brand->email).'">';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Url").'</td><td>';
    print '<input type="url" name="url" class="minwidth300" value="'.dol_escape_htmltag($brand->url).'">';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("BankAccount").'</td><td>';
    print '<select name="bank_account" class="flat minwidth200">';
    print '<option value="0">-- '.$langs->trans("None").' --</option>';
    $sql_bank = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account WHERE entity = ".((int)$conf->entity)." AND clos = 0 ORDER BY label";
    $resql_bank = $db->query($sql_bank);
    if ($resql_bank) {
        while ($obj_bank = $db->fetch_object($resql_bank)) {
            print '<option value="'.((int)$obj_bank->rowid).'"'.($brand->bank_account == $obj_bank->rowid ? ' selected' : '').'>'.dol_escape_htmltag($obj_bank->label).'</option>';
        }
    }
    print '</select>';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("PrimaryColor").'</td><td>';
    print '<input type="color" name="color_primary" value="'.dol_escape_htmltag($brand->color_primary ?: '#000000').'">';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("SecondaryColor").'</td><td>';
    print '<input type="color" name="color_secondary" value="'.dol_escape_htmltag($brand->color_secondary ?: '#666666').'">';
    print '</td></tr>';

    print '<tr><td class="tdtop">'.$langs->trans("LegalText").'</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor = new DolEditor('legal_text', $brand->legal_text, '', 100, 'dolibarr_notes', 'In', false, false, true, ROWS_3, '90%');
    $doleditor->Create();
    print '</td></tr>';

    print '<tr><td class="tdtop">'.$langs->trans("FooterText").'</td><td>';
    $doleditor2 = new DolEditor('footer_text', $brand->footer_text, '', 100, 'dolibarr_notes', 'In', false, false, true, ROWS_2, '90%');
    $doleditor2->Create();
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("DefaultBrand").'</td><td>';
    print '<input type="checkbox" name="is_default" value="1"'.($brand->is_default ? ' checked' : '').'>';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Active").'</td><td>';
    print '<input type="checkbox" name="active" value="1"'.($brand->active || $action == 'create' ? ' checked' : '').'>';
    print '</td></tr>';

    print '</table>';

    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
    print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onclick="window.location=\''.$_SERVER["PHP_SELF"].'\'">';
    print '</div>';

    print '</form>';
} else {
    // HOW TO USE INFO BOX
    print '<div class="fichecenter">';
    print '<div class="info clearboth">';
    print '<strong>'.$langs->trans("HowToUse").'</strong><br>';
    print $langs->trans("HowToUseStep1").'<br>';
    print $langs->trans("HowToUseStep2").'<br>';
    print $langs->trans("HowToUseStep3").'<br>';
    print $langs->trans("HowToUseStep4");
    print '</div>';
    print '</div><br>';

    // DIAGNOSTIC PANEL
    print '<div class="fichecenter">';
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Diagnostics").'</td></tr>';

    $diagDir = DOL_DATA_ROOT.'/multibrands/brands/logos';
    $diagDirOk = file_exists($diagDir) && is_writable($diagDir);
    print '<tr><td>'.$langs->trans("LogoDirectory").'</td><td>'.($diagDirOk ? '<span class="ok">'.$langs->trans("Writable").'</span>' : '<span class="error">'.$langs->trans("NotWritable").': '.dol_escape_htmltag($diagDir).'</span>').'</td></tr>';

    $sqlDiag = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."multibrands_brands WHERE entity = ".((int)$conf->entity);
    $resqlDiag = $db->query($sqlDiag);
    $brandCount = 0;
    if ($resqlDiag) {
        $objDiag = $db->fetch_object($resqlDiag);
        $brandCount = (int)$objDiag->cnt;
    }
    print '<tr><td>'.$langs->trans("BrandsDefined").'</td><td>'.((int)$brandCount).'</td></tr>';

    $extrafieldElements = array('propal', 'societe', 'facture', 'commande');
    foreach ($extrafieldElements as $elem) {
        $sqlEF = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."extrafields WHERE name = 'brand_code' AND elementtype = '".$db->escape($elem)."'";
        $resqlEF = $db->query($sqlEF);
        $hasEF = false;
        if ($resqlEF) {
            $objEF = $db->fetch_object($resqlEF);
            $hasEF = ((int)$objEF->cnt > 0);
        }
        print '<tr><td>'.$langs->trans("Extrafield").' <code>brand_code</code> @ '.$elem.'</td><td>'.($hasEF ? '<span class="ok">'.$langs->trans("Installed").'</span>' : '<span class="error">'.$langs->trans("Missing").'</span>').'</td></tr>';
    }

    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    print '<tr><td>'.$langs->trans("PhpUploadMaxFilesize").'</td><td>'.dol_escape_htmltag($uploadMax).'</td></tr>';
    print '<tr><td>'.$langs->trans("PhpPostMaxSize").'</td><td>'.dol_escape_htmltag($postMax).'</td></tr>';

    print '</table>';
    print '</div>';
    print '</div>';

    // REPAIR EXTRAFIELDS BUTTON
    print '<div class="tabsAction">';
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display:inline;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="repair_extrafields">';
    print '<button type="submit" class="butAction">'.$langs->trans("RepairExtrafields").'</button>';
    print '</form>';
    print '</div><br>';

    // DELETE CONFIRMATION (must render before list to overlay properly)
    if ($action == 'confirm_delete') {
        print $form->formconfirm(
            $_SERVER["PHP_SELF"]."?id=".((int)$id),
            $langs->trans("DeleteBrand"),
            $langs->trans("ConfirmDeleteBrand"),
            "confirm_delete",
            '',
            0,
            1
        );
    }

    // LIST VIEW
    print '<div class="fichecenter">';
    print '<div class="div-table-responsive">';
    print '<table class="tagtable nobottom liste">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Logo").'</td>';
    print '<td>'.$langs->trans("Label").'</td>';
    print '<td>'.$langs->trans("Code").'</td>';
    print '<td>'.$langs->trans("CompanyName").'</td>';
    print '<td>'.$langs->trans("DefaultBrand").'</td>';
    print '<td>'.$langs->trans("Active").'</td>';
    print '<td class="right">'.$langs->trans("Actions").'</td>';
    print '</tr>';

    $sql = "SELECT rowid, label, code, logo, company_name, is_default, active";
    $sql .= " FROM ".MAIN_DB_PREFIX."multibrands_brands";
    $sql .= " WHERE entity = ".((int) $conf->entity);
    $sql .= " ORDER BY is_default DESC, label";
    $resql = $db->query($sql);

    if ($resql) {
        $num = $db->num_rows($resql);
        if ($num == 0) {
            print '<tr><td colspan="7" class="opacitymedium">'.$langs->trans("NoBrandsDefined").'</td></tr>';
        } else {
            while ($obj = $db->fetch_object($resql)) {
                print '<tr class="oddeven">';
                print '<td>';
                if ($obj->logo) {
                    $logoPath = DOL_DATA_ROOT.'/multibrands/brands/logos/'.$obj->logo;
                    if (file_exists($logoPath)) {
                        print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=multibrands&file='.urlencode($obj->logo).'&cache=0&entity='.((int)$conf->entity).'" style="max-height:40px;">';
                    } else {
                        print '<span class="warning">'.$langs->trans("LogoFileMissing").'</span>';
                    }
                } else {
                    print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
                }
                print '</td>';
                print '<td>'.dol_escape_htmltag($obj->label).'</td>';
                print '<td><code>'.dol_escape_htmltag($obj->code).'</code></td>';
                print '<td>'.($obj->company_name ? dol_escape_htmltag($obj->company_name) : '<span class="opacitymedium">'.$langs->trans("None").'</span>').'</td>';
                print '<td>'.($obj->is_default ? '<span class="badge badge-status4">'.$langs->trans("Yes").'</span>' : '<span class="badge badge-status8">'.$langs->trans("No").'</span>').'</td>';
                print '<td>'.($obj->active ? '<span class="badge badge-status4">'.$langs->trans("Yes").'</span>' : '<span class="badge badge-status8">'.$langs->trans("No").'</span>').'</td>';
                print '<td class="right">';
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.((int)$obj->rowid).'&token='.newToken().'">'.img_edit().'</a>';
                print '&nbsp;';
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=confirm_delete&id='.((int)$obj->rowid).'&token='.newToken().'">'.img_delete().'</a>';
                print '</td>';
                print '</tr>';
            }
        }
    } else {
        print '<tr><td colspan="7" class="error">'.$langs->trans('ErrorDatabaseOperation').'</td></tr>';
    }

    print '</table>';
    print '</div>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=create&token='.newToken().'">'.$langs->trans("NewBrand").'</a>';
    print '</div>';
    print '</div>';
}



print dol_get_fiche_end();
llxFooter();
if (is_object($db)) $db->close();
