<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og\OgContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a global search block.
 *
 * @Block(
 *   id = "joinup_search_global_search",
 *   admin_label = @Translation("Global search")
 * )
 */
class GlobalSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OG context provider.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * Constructs a GlobalSearchBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgContextInterface $og_context) {
    $this->ogContext = $og_context;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $this->ogContext->getGroup();
    $build['content'] = [
      '#theme' => 'joinup_search_global_search',
      '#filters' => [$group ? $group->label() : 'No group'],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // This varies by group context since on group pages the search field is
    // prepopulated with a filter on the current group.
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_group_context']);
  }

}
