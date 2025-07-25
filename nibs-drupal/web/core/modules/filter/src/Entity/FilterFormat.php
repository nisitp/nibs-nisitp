<?php

namespace Drupal\filter\Entity;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\FilterFormatAccessControlHandler;
use Drupal\filter\FilterFormatAddForm;
use Drupal\filter\FilterFormatEditForm;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\FilterFormatListBuilder;
use Drupal\filter\FilterPluginCollection;
use Drupal\filter\Form\FilterDisableForm;
use Drupal\filter\Form\FilterEnableForm;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\user\Entity\Role;

/**
 * Represents a text format.
 */
#[ConfigEntityType(
  id: 'filter_format',
  label: new TranslatableMarkup('Text format'),
  label_collection: new TranslatableMarkup('Text formats'),
  label_singular: new TranslatableMarkup('text format'),
  label_plural: new TranslatableMarkup('text formats'),
  config_prefix: 'format',
  entity_keys: [
    'id' => 'format',
    'label' => 'name',
    'weight' => 'weight',
    'status' => 'status',
  ],
  handlers: [
    'form' => [
      'add' => FilterFormatAddForm::class,
      'edit' => FilterFormatEditForm::class,
      'disable' => FilterDisableForm::class,
      'enable' => FilterEnableForm::class,
    ],
    'list_builder' => FilterFormatListBuilder::class,
    'access' => FilterFormatAccessControlHandler::class,
  ],
  links: [
    'edit-form' => '/admin/config/content/formats/manage/{filter_format}',
    'disable' => '/admin/config/content/formats/manage/{filter_format}/disable',
    'enable' => '/admin/config/content/formats/manage/{filter_format}/enable',
  ],
  admin_permission: 'administer filters',
  label_count: [
    'singular' => '@count text format',
    'plural' => '@count text formats',
  ],
  config_export: [
    'name',
    'format',
    'weight',
    'roles',
    'filters',
  ],
)]
class FilterFormat extends ConfigEntityBase implements FilterFormatInterface, EntityWithPluginCollectionInterface {

  /**
   * Unique machine name of the format.
   *
   * @var string
   *
   * @todo Rename to $id.
   */
  protected $format;

  /**
   * Unique label of the text format.
   *
   * Since text formats impact a site's security, two formats with the same
   * label but different filter configuration would impose a security risk.
   * Therefore, each text format label must be unique.
   *
   * @var string
   *
   * @todo Rename to $label.
   */
  protected $name;

  /**
   * Weight of this format in the text format selector.
   *
   * The first/lowest text format that is accessible for a user is used as
   * default format.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * List of role IDs to grant access to use this format on initial creation.
   *
   * This property is always empty and unused for existing text formats.
   *
   * Default configuration objects of modules and installation profiles are
   * allowed to specify a list of user role IDs to grant access to.
   *
   * This property only has an effect when a new text format is created and the
   * list is not empty. By default, no user role is allowed to use a new format.
   *
   * @var array
   */
  protected $roles;

  /**
   * Configured filters for this text format.
   *
   * An associative array of filters assigned to the text format, keyed by the
   * instance ID of each filter and using the properties:
   * - id: The plugin ID of the filter plugin instance.
   * - provider: The name of the provider that owns the filter.
   * - status: (optional) A Boolean indicating whether the filter is
   *   enabled in the text format. Defaults to FALSE.
   * - weight: (optional) The weight of the filter in the text format. Defaults
   *   to 0.
   * - settings: (optional) An array of configured settings for the filter.
   *
   * Use FilterFormat::filters() to access the actual filters.
   *
   * @var array
   */
  protected $filters = [];

  /**
   * Holds the collection of filters that are attached to this format.
   *
   * @var \Drupal\filter\FilterPluginCollection
   */
  protected $filterCollection;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function filters($instance_id = NULL) {
    if (!isset($this->filterCollection)) {
      $this->filterCollection = new FilterPluginCollection(\Drupal::service('plugin.manager.filter'), $this->filters);
      $this->filterCollection->sort();
    }
    if (isset($instance_id)) {
      return $this->filterCollection->get($instance_id);
    }
    return $this->filterCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['filters' => $this->filters()];
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Sets configuration for a filter plugin'))]
  public function setFilterConfig($instance_id, array $configuration) {
    $this->filters[$instance_id] = $configuration;
    if (isset($this->filterCollection)) {
      $this->filterCollection->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    // The 'roles' property is only used during install and should never
    // actually be saved.
    unset($properties['roles']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    if ($this->isFallbackFormat()) {
      throw new \LogicException("The fallback text format '{$this->id()}' cannot be disabled.");
    }

    parent::disable();

    // Allow modules to react on text format deletion.
    \Drupal::moduleHandler()->invokeAll('filter_format_disable', [$this]);

    // Clear the filter cache whenever a text format is disabled.
    filter_formats_reset();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->isSyncing() && $this->hasTrustedData()) {
      // Filters are sorted by keys to ensure config export diffs are easy to
      // read and there is a minimal changeset. If the save is not trusted then
      // the configuration will be sorted by StorableConfigBase.
      ksort($this->filters);
      // Ensure the filter configuration is well-formed.
      array_walk($this->filters, function (array &$config, string $filter): void {
        $config['id'] ??= $filter;
        $config['provider'] ??= $this->filters($filter)->getPluginDefinition()['provider'];
      });
    }

    assert(is_string($this->label()), 'Filter format label is expected to be a string.');
    $this->name = trim($this->label());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Clear the static caches of filter_formats() and others.
    filter_formats_reset();

    if (!$update && !$this->isSyncing()) {
      // Default configuration of modules and installation profiles is allowed
      // to specify a list of user roles to grant access to for the new format;
      // apply the defined user role permissions when a new format is inserted
      // and has a non-empty $roles property.
      // Note: user_role_change_permissions() triggers a call chain back into
      // \Drupal\filter\FilterPermissions::permissions() and lastly
      // filter_formats(), so its cache must be reset upfront.
      if (($roles = $this->get('roles')) && $permission = $this->getPermissionName()) {
        foreach (Role::loadMultiple() as $rid => $role) {
          $enabled = in_array($rid, $roles, TRUE);
          user_role_change_permissions($rid, [$permission => $enabled]);
        }
      }
    }
  }

  /**
   * Returns if this format is the fallback format.
   *
   * The fallback format can never be disabled. It must always be available.
   *
   * @return bool
   *   TRUE if this format is the fallback format, FALSE otherwise.
   */
  public function isFallbackFormat() {
    $fallback_format = \Drupal::config('filter.settings')->get('fallback_format');
    return $this->id() == $fallback_format;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionName() {
    return !$this->isFallbackFormat() ? 'use text format ' . $this->id() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterTypes() {
    $filter_types = [];

    $filters = $this->filters();
    foreach ($filters as $filter) {
      if ($filter->status) {
        $filter_types[] = $filter->getType();
      }
    }

    return array_unique($filter_types);
  }

  /**
   * {@inheritdoc}
   */
  public function getHtmlRestrictions() {
    // Ignore filters that are disabled or don't have HTML restrictions.
    $filters = array_filter($this->filters()->getAll(), function ($filter) {
      if (!$filter->status) {
        return FALSE;
      }
      if ($filter->getType() === FilterInterface::TYPE_HTML_RESTRICTOR && $filter->getHTMLRestrictions() !== FALSE) {
        return TRUE;
      }
      return FALSE;
    });

    if (empty($filters)) {
      return FALSE;
    }
    else {
      // From the set of remaining filters (they were filtered by array_filter()
      // above), collect the list of tags and attributes that are allowed by all
      // filters, i.e. the intersection of all allowed tags and attributes.
      $restrictions = array_reduce($filters, function ($restrictions, $filter) {
        $new_restrictions = $filter->getHTMLRestrictions();

        // The first filter with HTML restrictions provides the initial set.
        if (!isset($restrictions)) {
          return $new_restrictions;
        }
        // Subsequent filters with an "allowed html" setting must be intersected
        // with the existing set, to ensure we only end up with the tags that
        // are allowed by *all* filters with an "allowed html" setting.
        else {
          // Track the intersection of allowed tags.
          if (isset($restrictions['allowed'])) {
            $intersection = $restrictions['allowed'];
            foreach ($intersection as $tag => $attributes) {
              // If the current tag is not allowed by the new filter, then it's
              // outside of the intersection.
              if (!array_key_exists($tag, $new_restrictions['allowed'])) {
                // The exception is the asterisk (which applies to all tags): it
                // does not need to be allowed by every filter in order to be
                // used; not every filter needs attribute restrictions on all
                // tags.
                if ($tag === '*') {
                  continue;
                }
                unset($intersection[$tag]);
              }
              // The tag is in the intersection, but now we must calculate the
              // intersection of the allowed attributes.
              else {
                $current_attributes = $intersection[$tag];
                $new_attributes = $new_restrictions['allowed'][$tag];
                // The current intersection does not allow any attributes, never
                // allow.
                if (!is_array($current_attributes) && $current_attributes == FALSE) {
                  continue;
                }
                // The new filter allows less attributes (all -> list or none).
                elseif (!is_array($current_attributes) && $current_attributes == TRUE && ($new_attributes == FALSE || is_array($new_attributes))) {
                  $intersection[$tag] = $new_attributes;
                }
                // The new filter allows less attributes (list -> none).
                elseif (is_array($current_attributes) && $new_attributes == FALSE) {
                  $intersection[$tag] = $new_attributes;
                }
                // The new filter allows more attributes; retain current.
                elseif (is_array($current_attributes) && $new_attributes == TRUE) {
                  continue;
                }
                // The new filter allows the same attributes; retain current.
                elseif ($current_attributes == $new_attributes) {
                  continue;
                }
                // Both list an array of attribute values; do an intersection,
                // where we take into account that a value of:
                // - TRUE means the attribute value is allowed;
                // - FALSE means the attribute value is forbidden;
                // hence we keep the ANDed result.
                else {
                  $intersection[$tag] = array_intersect_key($intersection[$tag], $new_attributes);
                  foreach (array_keys($intersection[$tag]) as $attribute_value) {
                    $intersection[$tag][$attribute_value] = $intersection[$tag][$attribute_value] && $new_attributes[$attribute_value];
                  }
                }
              }
            }
            $restrictions['allowed'] = $intersection;
          }

          // Simplification: if the only remaining allowed tag is the asterisk
          // (which contains attribute restrictions that apply to all tags),
          // then effectively nothing is allowed.
          if (count($restrictions['allowed']) === 1 && array_key_exists('*', $restrictions['allowed'])) {
            $restrictions['allowed'] = [];
          }

          return $restrictions;
        }
      }, NULL);

      return $restrictions;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeFilter($instance_id) {
    unset($this->filters[$instance_id]);
    $this->filterCollection->removeInstanceId($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $filters = $this->filters();
    foreach ($filters as $filter) {
      // Remove disabled filters, so that this FilterFormat config entity can
      // continue to exist.
      if (!$filter->status && in_array($filter->provider, $dependencies['module'])) {
        $this->removeFilter($filter->getPluginId());
        $changed = TRUE;
      }
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  protected function calculatePluginDependencies(PluginInspectionInterface $instance) {
    // Only add dependencies for plugins that are actually configured. This is
    // necessary because the filter plugin collection will return all available
    // filter plugins.
    // @see \Drupal\filter\FilterPluginCollection::getConfiguration()
    if (isset($this->filters[$instance->getPluginId()])) {
      parent::calculatePluginDependencies($instance);
    }
  }

}
