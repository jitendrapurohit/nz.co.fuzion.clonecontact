<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * This class glues together the various parts of the extension
 * system.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 */
class CRM_Clonecontact_BAO_Clonecontact {

  /**
   * Clone a contact based on contact id.
   *
   * @param integer $contactId
   */
  public static function cloneContact() {
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    $clonedContactId = self::cloneContactValues($contactId);

    self::cloneGroupsTagsAndNotes($contactId, $clonedContactId);

    self::cloneBlocks($contactId, $clonedContactId);

    self::cloneCustomValue($contactId, $clonedContactId);

    self::cloneRelationships($contactId, $clonedContactId);

    self::cloneMemberships($contactId, $clonedContactId);

    self::cloneParticipants($contactId, $clonedContactId);

    self::clonePledges($contactId, $clonedContactId);

    self::cloneContributions($contactId, $clonedContactId);

    $params = array(
      'reset' => 1,
      'cid' => $clonedContactId,
    );
    CRM_Core_Session::setStatus(
      ts('The contact has been cloned. <a href="%1">Click here</a> to view.',
      array(1 => CRM_Utils_System::url('civicrm/contact/view', $params))),
      ts('Saved'), 'success');
    $params['cid'] = $contactId;
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', $params));
  }

  public static function cloneContactValues($contactId) {
    $params = civicrm_api3('Contact', 'getsingle', array(
      'sequential' => 1,
      'id' => $contactId,
    ));
    $params['source'] = "Cloned from contact id {$params['id']}";
    unset($params['id'], $params['contact_id']);

    $result = civicrm_api3('Contact', 'create', $params);
    return $result['id'];
  }

  public static function cloneRelationships($contactId, $clonedContactId) {
    $relationshipContactkeys = array('contact_id_a', 'contact_id_b');
    foreach ($relationshipContactkeys as $contactKey) {
      $result = civicrm_api3('Relationship', 'get', array(
        'sequential' => 1,
        $contactKey => $contactId,
        'options' => array('limit' => 0),
      ));
      if (empty($result['count'])) {
        return;
      }
      foreach ($result['values'] as $params) {
        $params[$contactKey] = $clonedContactId;
        $startDate = CRM_Utils_Array::value('start_date', $params);
        $endDate = CRM_Utils_Array::value('end_date', $params);
        unset($params['id'], $params['start_date'], $params['end_date']);
        if (CRM_Contact_BAO_Relationship::checkDuplicateRelationship($params, $params['contact_id_a'], $params['contact_id_b'])) {
          continue;
        }
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
        civicrm_api3('Relationship', 'create', $params);
      }
    }
  }

  public static function cloneCustomValue($contactId, $clonedContactId) {
    $contactTypes = CRM_Contact_BAO_ContactType::basicTypes();

    $result = civicrm_api3('CustomGroup', 'get', array(
      'sequential' => 1,
      'return' => array("table_name", "id"),
      'extends' => array('IN' => array_merge(array("Contact"), $contactTypes)),
      'options' => array('limit' => 0),
    ));
    if (empty($result['count'])) {
      return;
    }

    foreach ($result['values'] as $val) {
      $cf = civicrm_api3('CustomField', 'get', array(
        'sequential' => 1,
        'return' => array("column_name"),
        'custom_group_id' => $val['id'],
      ));
      if (empty($cf['count'])) {
        continue;
      }
      $cfColumnNames = implode(', ', CRM_Utils_Array::collect('column_name', $cf['values']));

      CRM_Core_DAO::executeQuery("
        INSERT INTO {$val['table_name']} (entity_id, {$cfColumnNames})
        SELECT {$clonedContactId}, {$cfColumnNames}
        FROM {$val['table_name']}
        WHERE entity_id={$contactId}"
      );
    }
  }

  public static function cloneBlocks($contactId, $clonedContactId) {
    //Clone address and phone
    $blocks = array('email', 'address', 'phone', 'website', 'im');
    foreach ($blocks as $block) {
      $result = civicrm_api3($block, 'get', array(
        'sequential' => 1,
        'contact_id' => $contactId,
        'options' => array('limit' => 0),
      ));
      if (empty($result['count'])) {
        return;
      }

      foreach ($result['values'] as $params) {
        unset($params['id']);
        if (!empty($params[$block])) {
          $exist = civicrm_api3($block, 'getcount', array(
            'contact_id' => $clonedContactId,
            $block => $params[$block],
          ));
          if ($exist) {
            continue;
          }
        }
        $params['contact_id'] = $clonedContactId;
        civicrm_api3($block, 'create', $params);
      }
    }
  }

  public static function cloneGroupsTagsAndNotes($contactId, $clonedContactId) {
    //Clone groups
    $groupContact = new CRM_Contact_DAO_GroupContact();
    $groupContact->contact_id = $contactId;
    $groupContact->find();
    while ($groupContact->fetch()) {
      $grpParams = array(
        "group_id" => $groupContact->group_id,
        'contact_id' => $clonedContactId,
        'status' => $groupContact->status,
        'location_id' => $groupContact->location_id,
        'email_id' => $groupContact->email_id,
      );
      civicrm_api3('GroupContact', 'create', $grpParams);
    }

    //Clone tags and notes
    $entities = array('EntityTag', 'Note');
    foreach ($entities as $entity) {
      $result = civicrm_api3($entity, 'get', array(
        'sequential' => 1,
        'entity_table' => "civicrm_contact",
        'entity_id' => $contactId,
      ));
      if (empty($result['count'])) {
        return;
      }

      foreach ($result['values'] as $params) {
        unset($params['id']);
        $params['entity_id'] = $clonedContactId;
        civicrm_api3($entity, 'create', $params);
      }
    }
  }


  public static function cloneMemberships($contactId, $clonedContactId) {
    $memberships = civicrm_api3('Membership', 'get', array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'options' => array('limit' => 0),
    ));
    if (empty($memberships['count'])) {
      return;
    }

    foreach ($memberships['values'] as $memParams) {
      $mainMembershipId = $memParams['id'];
      $memParams['contact_id'] = $clonedContactId;
      unset($memParams['id'], $memParams['membership_name'], $memParams['related_contact_id'], $memParams['relationship_name']);
      $result = civicrm_api3('Membership', 'create', $memParams);
      self::cloneMembershipPayments($mainMembershipId, $result['id'], $clonedContactId);
    }
  }

  public static function cloneMembershipPayments($mainMembershipId, $clonedMembershipId, $clonedContactId) {
    $membershipPayments = civicrm_api3('MembershipPayment', 'get', array(
      'sequential' => 1,
      'membership_id' => $mainMembershipId,
    ));
    if (empty($membershipPayments['count'])) {
      return;
    }

    foreach ($membershipPayments['values'] as $paymentParams) {
      $paymentParams['membership_id'] = $clonedMembershipId;
      $contriParams = civicrm_api3('Contribution', 'getsingle', array(
        'id' => $paymentParams['contribution_id'],
      ));
      $contriParams['contact_id'] = $clonedContactId;
      unset($contriParams['id'], $contriParams['trxn_id'], $contriParams['invoice_id'], $contriParams['contribution_id']);
      $contribution = civicrm_api3('Contribution', 'create', $contriParams);

      $paymentParams['contribution_id'] = $contribution['id'];
      unset($paymentParams['id']);
      civicrm_api3('MembershipPayment', 'create', $paymentParams);
    }
  }

  public static function cloneParticipants($contactId, $clonedContactId) {
    $participants = civicrm_api3('Participant', 'get', array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'options' => array('limit' => 0),
    ));
    if (empty($participants['count'])) {
      return;
    }

    foreach ($participants['values'] as $participantParams) {
      $mainParticipantId = $participantParams['id'];
      $participantParams['contact_id'] = $clonedContactId;
      if (is_array($participantParams['participant_fee_level'])) {
        $participantParams['participant_fee_level'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $participantParams['participant_fee_level']);
      }
      unset($participantParams['id'], $participantParams['participant_id'], $participantParams['contact_sub_type']);
      $result = civicrm_api3('Participant', 'create', $participantParams);
      self::cloneParticipantPayments($mainParticipantId, $result['id'], $clonedContactId);
    }
  }

  public static function cloneParticipantPayments($mainParticipantId, $clonedParticipantId, $clonedContactId) {
    $participantPayments = civicrm_api3('ParticipantPayment', 'get', array(
      'sequential' => 1,
      'participant_id' => $mainParticipantId,
      'options' => array('limit' => 0),
    ));
    if (empty($participantPayments['count'])) {
      return;
    }

    foreach ($participantPayments['values'] as $paymentParams) {
      $paymentParams['participant_id'] = $clonedParticipantId;
      $contriParams = civicrm_api3('Contribution', 'getsingle', array(
        'id' => $paymentParams['contribution_id'],
      ));
      $contriParams['contact_id'] = $clonedContactId;
      unset($contriParams['id'], $contriParams['trxn_id'], $contriParams['invoice_id'], $contriParams['contribution_id']);
      $contribution = civicrm_api3('Contribution', 'create', $contriParams);

      $paymentParams['contribution_id'] = $contribution['id'];
      unset($paymentParams['id']);
      civicrm_api3('ParticipantPayment', 'create', $paymentParams);
    }
  }

  public static function clonePledges($contactId, $clonedContactId) {
    $pledgeParams = array_flip(array_keys(CRM_Pledge_DAO_Pledge::fields()));
    foreach ($pledgeParams as $k => $v) {
      if (strpos($k, 'pledge_') !== false) {
        $temp = str_replace('pledge_', '', $k);
        $pledgeParams[$temp] = NULL;
        unset($pledgeParams[$k]);
      }
      $pledgeParams[$k] = NULL;
    }
    $pledge = new CRM_Pledge_DAO_Pledge();
    $pledge->contact_id = $contactId;
    $pledge->find();
    while ($pledge->fetch()) {
      foreach ($pledgeParams as $key => $val) {
        if (!isset($pledge->$key)) {
          continue;
        }
        $pledgeParams[$key] = $pledge->$key;
      }
      unset($pledgeParams['id'], $pledgeParams['pledge_id']);
      $pledgeParams['contact_id'] = $clonedContactId;
      $result = civicrm_api3('Pledge', 'create', $pledgeParams);
      self::clonePledgePayments($pledge->id, $result['id'], $clonedContactId);
    }
  }

  public static function clonePledgePayments($mainPledgeId, $clonedPledgeId, $clonedContactId) {
    $pledgePayments = civicrm_api3('PledgePayment', 'get', array(
      'sequential' => 1,
      'pledge_id' => $mainPledgeId,
      'options' => array('limit' => 0),
    ));
    if (empty($pledgePayments['count'])) {
      return;
    }

    foreach ($pledgePayments['values'] as $paymentParams) {
      if (empty($paymentParams['contribution_id'])) {
        continue;
      }
      $paymentParams['pledge_id'] = $clonedPledgeId;
      $contriParams = civicrm_api3('Contribution', 'getsingle', array(
        'id' => $paymentParams['contribution_id'],
      ));
      $contriParams['contact_id'] = $clonedContactId;
      unset($contriParams['id'], $contriParams['trxn_id'], $contriParams['invoice_id'], $contriParams['contribution_id']);
      $contribution = civicrm_api3('Contribution', 'create', $contriParams);

      $paymentParams['contribution_id'] = $contribution['id'];
      unset($paymentParams['id']);
      civicrm_api3('PledgePayment', 'create', $paymentParams);
    }
  }

  public static function cloneContributions($contactId, $clonedContactId) {
    $contributions = civicrm_api3('Contribution', 'get', array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'options' => array('limit' => 0),
    ));
    if (empty($contributions['count'])) {
      return;
    }

    foreach ($contributions['values'] as $contriParams) {
      $isMembershipPayment = civicrm_api3('MembershipPayment', 'getcount', array(
        'contribution_id' => $contriParams['id'],
      ));
      $isParticipantPayment = civicrm_api3('ParticipantPayment', 'getcount', array(
        'contribution_id' => $contriParams['id'],
      ));
      $isPledgePayment = civicrm_api3('PledgePayment', 'getcount', array(
        'contribution_id' => $contriParams['id'],
      ));
      if ($isMembershipPayment || $isParticipantPayment || $isPledgePayment) {
        continue;
      }
      $contriParams['contact_id'] = $clonedContactId;
      unset($contriParams['id'], $contriParams['trxn_id'], $contriParams['invoice_id'], $contriParams['contribution_id']);
      civicrm_api3('Contribution', 'create', $contriParams);
    }
  }

}
