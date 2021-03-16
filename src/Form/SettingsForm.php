<?php

namespace Drupal\graphql_search_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class implementing GraphQL Search API settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for GraphQL Search API SettingsForm.
   *
   * @param ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'graphql_search_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphql_search_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('graphql_search_api.settings');
    $indexes = $this->entityTypeManager->getStorage('search_api_index')->loadMultiple();

    $metadata = new CacheableMetadata();
    $default_cache_max_age = $metadata->getCacheMaxAge();

    $form['cache_max_age'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache Max-Age per Search API Index'),
      '#open' => TRUE,
      '#tree' => TRUE,
      'info' => [
        '#markup' => $this->t('Note: The default cache max-age is @default', [
          '@default' => $default_cache_max_age,
        ]),
      ],
    ];

    foreach ($indexes as $index_id => $index) {
      $form['cache_max_age'][$index_id] = [
        '#type' => 'number',
        '#title' => $this->t('Index @index', [
          '@index' => $index->label(),
        ]),
        '#description' => $this->t('Cache max-age in seconds: Enter -1 for permanent or 0 for no-cache.'),
        '#min' => -1,
        '#field_suffix' => $this->t('seconds'),
        '#size' => 3,
        '#default_value' => $config->get('cache_max_age.' . $index_id) ?? $default_cache_max_age,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory()->getEditable($this->getEditableConfigNames()[0])
      ->set('cache_max_age', $form_state->getValue('cache_max_age'))
      ->save();
  }

}
