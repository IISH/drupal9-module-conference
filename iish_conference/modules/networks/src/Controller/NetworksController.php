<?php
namespace Drupal\iish_conference_networks\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\iish_conference\API\Domain\NetworkApi;
use Drupal\iish_conference\API\CachedConferenceApi;

use Symfony\Component\HttpFoundation\Response;

/**
 * The controller for networks.
 */
class NetworksController extends ControllerBase {

  /**
   * List all networks.
   * @return array Render array.
   */
  public function listNetworks() {
    return array(
      '#theme' => 'iish_conference_networks_list',
      '#networks' => CachedConferenceApi::getNetworks(),
    );
  }

  /**
   * Show the network details.
   * @param NetworkApi $network The network.
   * @return array|Response The render array or a redirect response.
   */
  public function network($network) {
    $messenger = \Drupal::messenger();

    if (!empty($network)) {
      return array(
        '#theme' => 'iish_conference_networks_item',
        '#network' => $network,
      );
    }

    $messenger->addMessage(iish_t('The network could unfortunately not be found!'), 'error');
    return $this->redirect('iish_conference_networks.index');
  }

  /**
   * The networks title.
   * @return string The networks title.
   */
  public function getNetworksTitle() {
    try {
      return iish_t('Networks');
    }
    catch (\Exception $exception) {
      return '';
    }
  }

  /**
   * The network title.
   * @param NetworkApi $network The network.
   * @return string The network title.
   */
  public function getNetworkTitle($network) {
    try {
      return $network->getName();
    }
    catch (\Exception $exception) {
      return '';
    }
  }
}
