<?php
namespace Drupal\iish_conference_login_logout\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\iish_conference\API\SettingsApi;
use Drupal\iish_conference\ConferenceTrait;
use Drupal\iish_conference\ConferenceMisc;

use Drupal\iish_conference\API\LoginApi;
use Drupal\iish_conference\API\LoggedInUserDetails;

//use Drupal\Core\Messenger\MessengerInterface;

/**
 * The login form.
 */
class LoginForm extends FormBase {
 use ConferenceTrait;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'conference_login';
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
    $moduleHandler = \Drupal::moduleHandler();

    $form['#attributes'] = array('class' => 'iishconference_container');

    $form['help-text'] = array(
      '#markup' => '<div class="bottommargin">' .
        iish_t('Please enter your e-mail address and password.') .
        '</div>',
    );

    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => iish_t('E-mail'),
      '#size' => 30,
      '#maxlength' => 255,
      '#prefix' => '<div class="container-inline bottommargin">',
      '#suffix' => '</div>',
      '#default_value' => LoggedInUserDetails::getEmail(),
      '#required' => TRUE,
    );

    $form['password'] = array(
      '#type' => 'password',
      '#title' => iish_t('Password'),
      '#size' => 30,
      '#maxlength' => 50,
      '#prefix' => '<div class="container-inline bottommargin">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    );

    $form['submit_button_next'] = array(
      '#type' => 'submit',
      '#value' => iish_t('Log in')
    );

    if ($moduleHandler->moduleExists('iish_conference_lost_password')) {
      $lostPasswordLink = Link::fromTextAndUrl(iish_t('Lost password'),
        Url::fromRoute('iish_conference_lost_password.form'));

      $form['lost-password'] = array(
        '#markup' => '<div class="largertopmargin">'
          . $lostPasswordLink->toString()
          . '</div>',
      );
    }

    if ($moduleHandler->moduleExists('iish_conference_pre_registration')) {
      $preRegistrationLink = Link::fromTextAndUrl(iish_t('Pre-registration form'),
        Url::fromRoute('iish_conference_pre_registration.form'));
      $form['pre-registration'] = array(
        '#markup' => '<div class="largertopmargin">'
          . iish_t('If you don\'t have an account please go to @link.',
            array('@link' => $preRegistrationLink->toString()))
          . '</div>',
      );
    }

    $form['info-block'] = array(
      '#markup' => ConferenceMisc::getInfoBlock()
    );

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = trim($form_state->getValue('email'));
    if (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('email', iish_t('The e-mail address appears to be invalid.'));
    }
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
    $messenger = \Drupal::messenger();

    $loginApi = new LoginApi();
    $user_status = $loginApi->login($form_state->getValue('email'), $form_state->getValue('password'));

    if ($user_status == LoggedInUserDetails::USER_STATUS_EXISTS) {
      $this->formRedirectToPersonalPage($form_state);
    }
    else {
      $form_state->setRebuild();

      switch ($user_status) {
        case LoggedInUserDetails::USER_STATUS_DISABLED:
        case LoggedInUserDetails::USER_STATUS_EMAIL_DISCONTINUED:
        $messenger->addMessage(iish_t('Account is disabled.'), 'error');
          break;
        case LoggedInUserDetails::USER_STATUS_DELETED:
          $messenger->addMessage(iish_t('Account is deleted'), 'error');
          break;
        case LoggedInUserDetails::USER_STATUS_PARTICIPANT_CANCELLED:
          $messenger->addMessage(iish_t('Account has been cancelled.'), 'error');
          break;
        case LoggedInUserDetails::USER_STATUS_PARTICIPANT_DOUBLE_ENTRY:
          $messenger->addMessage(iish_t('Your account is disabled. ' .
                '(Probably due to a double registration, please login with your other registration) ' .
                'Please contact @email', array('@email' =>
            ConferenceMisc::emailLink(
              SettingsApi::getSetting(SettingsApi::DEFAULT_ORGANISATION_EMAIL))->toString()
            )
          ), 'error');
          break;
        default:
          $messenger->addMessage( iish_t('Incorrect email / password combination.'), 'error' );
      }
    }
  }
}
