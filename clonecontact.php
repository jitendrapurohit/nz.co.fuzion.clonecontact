<?php

require_once 'clonecontact.civix.php';
use CRM_Clonecontact_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function clonecontact_civicrm_config(&$config) {
  _clonecontact_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function clonecontact_civicrm_xmlMenu(&$files) {
  _clonecontact_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function clonecontact_civicrm_install() {
  _clonecontact_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function clonecontact_civicrm_postInstall() {
  _clonecontact_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function clonecontact_civicrm_uninstall() {
  _clonecontact_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function clonecontact_civicrm_enable() {
  _clonecontact_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function clonecontact_civicrm_disable() {
  _clonecontact_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function clonecontact_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _clonecontact_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function clonecontact_civicrm_managed(&$entities) {
  _clonecontact_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function clonecontact_civicrm_caseTypes(&$caseTypes) {
  _clonecontact_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function clonecontact_civicrm_angularModules(&$angularModules) {
  _clonecontact_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function clonecontact_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _clonecontact_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function clonecontact_civicrm_summaryActions(&$actions, $contactID) {
  $actions['otherActions']['clonecontact'] = array(
    'title' => 'Clone Contact',
    'weight' => 999,
    'ref' => 'clone-contact',
    'class' => 'user-add',
    'key' => 'otherActions',
    'icon' => 'crm-i fa-user-plus',
    'href' => CRM_Utils_System::url('civicrm/clonecontact', array('cid' => $contactID, 'reset' => 1)),
  );
}


// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function clonecontact_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function clonecontact_civicrm_navigationMenu(&$menu) {
  _clonecontact_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _clonecontact_civix_navigationMenu($menu);
} // */
