<?php
namespace Drupal\iish_conference_preregistration\Form\Page;

use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\CRUDApiClient;
use Drupal\iish_conference\API\CRUDApiMisc;
use Drupal\iish_conference\API\Domain\KeywordApi;
use Drupal\iish_conference\API\Domain\PaperKeywordApi;
use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\API\SendEmailApi;
use Drupal\iish_conference\API\CachedConferenceApi;

use Drupal\iish_conference\API\Domain\ExtraApi;
use Drupal\iish_conference\API\Domain\VolunteeringApi;
use Drupal\iish_conference\API\Domain\ParticipantDateApi;
use Drupal\iish_conference\API\Domain\ParticipantStateApi;
use Drupal\iish_conference\API\Domain\ParticipantVolunteeringApi;
use Drupal\iish_conference\API\Domain\CombinedSessionParticipantApi;

use Drupal\iish_conference\ConferenceMisc;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference_preregistration\Form\PreRegistrationState;
use Drupal\iish_conference_preregistration\Form\PreRegistrationUtils;

/**
 * The confirm page.
 */
class ConfirmPage extends PreRegistrationPage {
  use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'iish_conference_preregistration_confirm';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();

    $state = new PreRegistrationState($form_state);
    $user = $state->getUser();
    $participant = $state->getParticipant();

    $showChairDiscussantPool = SettingsApi::getSetting(SettingsApi::SHOW_CHAIR_DISCUSSANT_POOL, 'bool');
    $showLanguageCoaching = SettingsApi::getSetting(SettingsApi::SHOW_LANGUAGE_COACH_PUPIL, 'bool');

    // + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + +
    // PERSONAL INFO

    $personalInfoContent = array(array('header' => iish_t('Personal Info')));

    $personalInfoContent[] = array(
      'label' => 'First name',
      'value' => $user->getFirstName()
    );
    $personalInfoContent[] = array(
      'label' => 'Last name',
      'value' => $user->getLastName()
    );
    $personalInfoContent[] = array(
      'label' => 'Gender',
      'value' => ConferenceMisc::getGender($user->getGender())
    );
    $personalInfoContent[] = array(
      'label' => 'Organisation',
      'value' => $user->getOrganisation()
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_DEPARTMENT, 'bool')) {
      $personalInfoContent[] = array(
        'label' => 'Department',
        'value' => $user->getDepartment()
      );
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_EDUCATION, 'bool')) {
      $personalInfoContent[] = array(
        'label' => 'Education',
        'value' => $user->getEducation()
      );
    }

    $personalInfoContent[] = array(
      'label' => 'E-mail',
      'value' => $user->getEmail()
    );

    if (SettingsApi::getSetting(SettingsApi::SHOW_AGE_RANGE, 'bool')) {
      $personalInfoContent[] = array(
        'label' => 'Age',
        'value' => $participant->getAgeRange()
      );
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_STUDENT, 'bool')) {
      $personalInfoContent[] = array(
        'label' => '(PhD) Student?',
        'value' => ConferenceMisc::getYesOrNo($participant->getStudent())
      );
    }

    if (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool')) {
      $personalInfoContent[] = array(
        'label' => 'Curriculum Vitae',
        'value' => $user->getCv(),
        'newLine' => TRUE
      );
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // ADDRESS

    $addressContent = array(array('header' => iish_t('Address')));

    $addressContent[] = array(
      'label' => 'City',
      'value' => $user->getCity()
    );
    $addressContent[] = array(
      'label' => 'Country',
      'value' => $user->getCountry()->__toString()
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // COMMUNICATION MEANS

    $communicationContent =
      array(array('header' => iish_t('Communication Means')));

    $communicationContent[] = array(
      'label' => 'Phone number',
      'value' => $user->getPhone()
    );
    $communicationContent[] = array(
      'label' => 'Mobile number',
      'value' => $user->getMobile()
    );

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // EXTRA'S

    $extrasContent = array();
    $extras = ExtraApi::getOnlyPreRegistration(CachedConferenceApi::getExtras());
    if (count($extras) > 0) {
      $extrasContent = array('header' => '');

      $extrasParticipant = $participant->getExtrasOfPreRegistration();
      foreach ($extras as $extra) {
        $extrasContent[] = array(
          'label' => $extra->getDescription(),
          'value' => ConferenceMisc::getYesOrNo(array_search($extra, $extrasParticipant) !== FALSE)
        );
      }
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // CHAIR / DISCUSSANT POOL

    $chairDiscussantContent = array();
    $allVolunteering = PreRegistrationUtils::getAllVolunteeringOfUser($state);

    if ($showChairDiscussantPool) {
      $chairVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::CHAIR);
      $discussantVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::DISCUSSANT);

      $chairDiscussantContent =
        array(array('header' => iish_t('Chair / discussant pool')));

      $chairDiscussantContent[] = array(
        'label' => 'I would like to volunteer as Chair?',
        'value' => ConferenceMisc::getYesOrNo(count($chairVolunteering) > 0)
      );

      if (PreRegistrationUtils::showNetworks() && (count($chairVolunteering) > 0)) {
        $chairDiscussantContent[] = array(
          'label' => 'Networks',
          'value' => implode(', ', $chairVolunteering)
        );
      }

      $chairDiscussantContent[] = array(
        'label' => 'I would like to volunteer as Discussant?',
        'value' => ConferenceMisc::getYesOrNo(count($discussantVolunteering) > 0)
      );

      if (PreRegistrationUtils::showNetworks() && (count($discussantVolunteering) > 0)) {
        $chairDiscussantContent[] = array(
          'label' => 'Networks',
          'value' => implode(', ', $discussantVolunteering)
        );
      }
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // ENGLISH LANGUAGE COACH

    $englishCoachingContent = array();
    if ($showLanguageCoaching) {
      $coachVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::COACH);
      $pupilVolunteering =
        ParticipantVolunteeringApi::getAllNetworksForVolunteering($allVolunteering, VolunteeringApi::PUPIL);

      $englishCoachingContent =
        array(array('header' => iish_t('English Language Coach')));

      $englishCoachingContent[] = array(
        'label' => ConferenceMisc::getLanguageCoachPupil('coach'),
        'value' => ConferenceMisc::getYesOrNo(count($coachVolunteering) > 0)
      );

      if (PreRegistrationUtils::showNetworks() && (count($coachVolunteering) > 0)) {
        $englishCoachingContent[] = array(
          'label' => 'Networks',
          'value' => implode(', ', $coachVolunteering)
        );
      }

      $englishCoachingContent[] = array(
        'label' => ConferenceMisc::getLanguageCoachPupil('pupil'),
        'value' => ConferenceMisc::getYesOrNo(count($pupilVolunteering) > 0)
      );

      if (PreRegistrationUtils::showNetworks() && (count($pupilVolunteering) > 0)) {
        $englishCoachingContent[] = array(
          'label' => 'Networks',
          'value' => implode(', ', $pupilVolunteering)
        );
      }
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // PAPERS

    $papersContent = array();
    $papers = PreRegistrationUtils::getPapersOfUser($state);

    foreach ($papers as $i => $paper) {
      $paperContent = array(
        array(
          'header' => iish_t('Paper @count of @total',
            array('@count' => $i + 1, '@total' => count($papers)))
        )
      );

      $paperContent[] = array(
        'label' => 'Title',
        'value' => $paper->getTitle()
      );

      $paperContent[] = array(
        'label' => 'Abstract',
        'value' => $paper->getAbstr(),
        'newLine' => TRUE
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_PAPER_TYPES, 'bool')) {
        $paperContent[] = array(
          'label' => 'Paper type',
          'value' => ($paper->getType() !== NULL) ? $paper->getType() : $paper->getDifferentType(),
        );
      }

      $paperContent[] = array(
        'label' => 'Co-author(s)',
        'value' => $paper->getCoAuthors()
      );

      if (PreRegistrationUtils::useSessions()) {
        $paperContent[] = array(
          'label' => 'Proposed session',
          'value' => $paper->getSession()
        );
      }

      if (SettingsApi::getSetting(SettingsApi::SHOW_AWARD, 'bool') && $participant->getStudent()) {
        $paperContent[] = array(
          'label' => SettingsApi::getSetting(SettingsApi::AWARD_NAME) . '?',
          'value' => ConferenceMisc::getYesOrNo($participant->getAward()),
          'html' => TRUE
        );
      }

      foreach (KeywordApi::getGroups() as $group) {
        $keywords = PaperKeywordApi::getKeywordsForPaperInGroup($paper, $group);

        if (count($keywords) > 0) {
          $plainKeywords = CRUDApiClient::getForMethod($keywords, 'getKeyword');

          $paperContent[] = [
            'label' => ConferenceMisc::replaceKeyword('Keywords', $group),
            'value' => implode(', ', $plainKeywords)
          ];
        }
      }

      if (SettingsApi::getSetting(SettingsApi::SHOW_EQUIPMENT, 'bool')) {
        $paperContent[] = array(
          'label' => 'Audio/visual equipment',
          'value' => implode(', ', $paper->getEquipment())
        );
        $paperContent[] = array(
          'label' => 'Extra audio/visual request',
          'value' => $paper->getEquipmentComment()
        );
      }

      $papersContent[] = $paperContent;
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // SESSIONS

    $sessionsContent = array();
    $sessionParticipants = PreRegistrationUtils::getSessionParticipantsAddedByUser($state);
    $sessions = CombinedSessionParticipantApi::getAllSessions($sessionParticipants);

    foreach ($sessions as $i => $session) {
      $networks = $session->getNetworks();

      $sessionParticipants = PreRegistrationUtils::getSessionParticipantsAddedByUserForSession($state, $session);
      $users = CombinedSessionParticipantApi::getAllUsers($sessionParticipants);

      // + + + + + + + + + + + + + + + + + + + + + + + +

      $sessionContent =
        array(
          array(
            'header' => iish_t('Session @count of @total',
              array('@count' => $i + 1, '@total' => count($sessions)))
          )
        );

      $sessionContent[] = array(
        'label' => 'Session name',
        'value' => $session->getName()
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_TYPES, 'bool')) {
        $sessionContent[] = array(
          'label' => 'Session type',
          'value' => ($session->getType() !== NULL) ? $session->getType() : $session->getDifferentType(),
        );
      }

      $sessionContent[] = array(
        'label' => 'Abstract',
        'value' => $session->getAbstr(),
        'newLine' => TRUE
      );

      if (SettingsApi::getSetting(SettingsApi::SHOW_SESSION_EXTRA_INFO, 'bool')) {
        $sessionContent[] = array(
          'label' => 'Extra information',
          'value' => $session->getExtraInfo(),
          'newLine' => TRUE
        );
      }

      if (PreRegistrationUtils::showNetworks()) {
        $sessionContent[] = array(
          'label' => 'Network',
          'value' => isset($networks[0]) ? $networks[0] : NULL
        );
      }

      foreach ($users as $user) {
        $participantInSession = $user->getParticipantDate();
        $roles = CombinedSessionParticipantApi::getAllTypesOfUserForSession(
          $sessionParticipants,
          $user->getId(),
          $session->getId()
        );
        $paper = PreRegistrationUtils::getPaperForSessionAndUser($state, $session, $user);

        $sessionContent[] = array(
          '#markup' => '<br />'
        );
        $sessionContent[] = array(
          'label' => 'E-mail',
          'value' => $user->getEmail()
        );
        $sessionContent[] = array(
          'label' => 'First name',
          'value' => $user->getFirstName()
        );
        $sessionContent[] = array(
          'label' => 'Last name',
          'value' => $user->getLastName()
        );

        if (SettingsApi::getSetting(SettingsApi::SHOW_STUDENT, 'bool')) {
          $sessionContent[] = array(
            'label' => '(PhD) Student?',
            'value' => ConferenceMisc::getYesOrNo($participantInSession->getStudent())
          );
        }

        if (SettingsApi::getSetting(SettingsApi::SHOW_CV, 'bool')) {
          $sessionContent[] = array(
            'label' => 'Curriculum Vitae',
            'value' => $user->getCv(),
            'newLine' => TRUE
          );
        }

        $sessionContent[] = array(
          'label' => 'Country',
          'value' => $user->getCountry()->__toString()
        );
        $sessionContent[] = array(
          'label' => 'Type(s)',
          'value' => implode(', ', $roles),
        );

        if ($paper->isUpdate()) {
          $sessionContent[] = array(
            'label' => 'Paper title',
            'value' => $paper->getTitle()
          );
          $sessionContent[] = array(
            'label' => 'Paper abstract',
            'value' => $paper->getAbstr(),
            'newLine' => TRUE
          );
        }
      }

      $sessionsContent[] = $sessionContent;
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // SESSION PARTICIPANT TYPES

    $sessionParticipantTypesContent = array();
    $participantTypes = PreRegistrationUtils::getParticipantTypesForUser();

    foreach ($participantTypes as $participantType) {
      $sessionParticipants = PreRegistrationUtils::getSessionParticipantsOfUserWithType($state, $participantType);
      $sessions = CombinedSessionParticipantApi::getAllSessions($sessionParticipants);

      if (count($sessionParticipants) > 0) {
        $sessionParticipantTypeContent = array(
          array('header' => iish_t('@type in sessions', array('@type' => $participantType)))
        );

        $sessionParticipantTypeContent[] = array(
          '#theme' => 'item_list',
          '#items' => $sessions
        );

        $sessionParticipantTypesContent[] = $sessionParticipantTypeContent;
      }
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +
    // GENERAL COMMENTS

    $generalComments = array();
    if (SettingsApi::getSetting(SettingsApi::SHOW_GENERAL_COMMENTS, 'bool') &&
      (strlen($participant->getExtraInfo()) > 0)
    ) {
      $generalComments[] = array('header' => iish_t('General comments'));

      $generalComments[] = array(
        'label' => '',
        'value' => $participant->getExtraInfo(),
      );
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $message = iish_t('Please check your data, scroll down, and confirm and finish your pre-registration.');
    if (SettingsApi::getSetting(SettingsApi::SHOW_FINISH_LATER_BUTTON, 'bool')) {
      $message .= ' ' . iish_t('Or save your registration to update your registration info at a later moment.');
    }
    $messenger->addMessage(iish_t($message),'warning');

    $form['confirm'] = array();

    $form['confirm'][] = array(
      '#theme' => 'iish_conference_container',
      '#fields' => $personalInfoContent
    );
    $form['confirm'][] = array(
      '#theme' => 'iish_conference_container',
      '#fields' => $addressContent
    );
    $form['confirm'][] = array(
      '#theme' => 'iish_conference_container',
      '#fields' => $communicationContent
    );

    if (count($extrasContent) > 0) {
      $form['confirm'][] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $extrasContent
      );
    }
    if (count($chairDiscussantContent) > 0) {
      $form['confirm'][] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $chairDiscussantContent
      );
    }
    if (count($englishCoachingContent) > 0) {
      $form['confirm'][] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $englishCoachingContent
      );
    }

    foreach ($papersContent as $paperContent) {
      $form['confirm'][] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $paperContent
      );
    }
    foreach ($sessionsContent as $sessionContent) {
      $form['confirm'][] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $sessionContent
      );
    }
    foreach ($sessionParticipantTypesContent as $sessionParticipantTypeContent) {
      $form['confirm'][] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $sessionParticipantTypeContent
      );
    }

    if (count($generalComments) > 0) {
      $form['confirm'][] = array(
        '#theme' => 'iish_conference_container',
        '#fields' => $generalComments
      );
    }

    // + + + + + + + + + + + + + + + + + + + + + + + +

    $this->buildPrevButton($form, 'confirm_back');
    if (SettingsApi::getSetting(SettingsApi::SHOW_FINISH_LATER_BUTTON, 'bool')) {
      $this->buildPrevButton($form, 'confirm_back_personal_page', iish_t('Save and finish pre-registration later'));
    }
    $this->buildNextButton($form, 'confirm_next', iish_t('Confirm and finish pre-registration'));

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = new PreRegistrationState($form_state);
    $user = $state->getUser();

    $participant = $state->getParticipant();
    $participant->setState(ParticipantStateApi::NEW_PARTICIPANT);
    $participant->save();

    // Also set the state of all session participants we added to 0
    $sessionParticipants =
      CRUDApiMisc::getAllWherePropertyEquals(new CombinedSessionParticipantApi(), 'addedBy_id', $user->getId())->getResults();
    $users = CombinedSessionParticipantApi::getAllUsers($sessionParticipants);
    foreach ($users as $addedUser) {
      $participant =
        CRUDApiMisc::getFirstWherePropertyEquals(new ParticipantDateApi(), 'user_id', $addedUser->getId());
      if ($participant->getStateId() == ParticipantStateApi::DID_NOT_FINISH_REGISTRATION) {
        $participant->setState(ParticipantStateApi::NEW_PARTICIPANT);
        $participant->save();
      }
    }

    $sendEmailApi = new SendEmailApi();
    $sendEmailApi->sendPreRegistrationFinishedEmail($state->getUser());

    $form_state->setRedirect('iish_conference_preregistration.completed');
  }

  /**
   * Form back button submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function backForm(array &$form, FormStateInterface $form_state) {
    // Now find out if to which step we have to go to
    $submitName = $form_state->getTriggeringElement()['#name'];

    if ((SettingsApi::getSetting(SettingsApi::SHOW_FINISH_LATER_BUTTON, 'bool'))
      && ($submitName === 'confirm_back_personal_page')) {
      $this->formRedirectToPersonalPage($form_state);
    }
    else {
      $typeOfRegistrationPage = new TypeOfRegistrationPage();
      $commentsPage = new CommentsPage();

      if ($commentsPage->isOpen()) {
        $this->nextPageName = PreRegistrationPage::COMMENTS;
        return;
      }

      if ($typeOfRegistrationPage->isOpen()) {
        $this->nextPageName = PreRegistrationPage::TYPE_OF_REGISTRATION;
        return;
      }

      $this->nextPageName = PreRegistrationPage::PERSONAL_INFO;
    }
  }
}
