<?php

namespace Drupal\news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Render\Renderer;

/**
 * Provides a 'NewsBlock' block.
 *
 * @Block(
 *  id = "news_block",
 *  admin_label = @Translation("Latest News block"),
 * )
 */
class NewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
   
    $currentNode = \Drupal::routeMatch()->getParameter('node');

    if (!$currentNode instanceof Node) {
      return []; // User is not on a node page.
    }

    $departmentName = 'field_department'; // field name on News Article Content type
    $termIds = [];

    if ($currentNode->hasField($departmentName)) {
      foreach ($currentNode->get($departmentName)->referencedEntities() as $term) {
        if ($term instanceof Term) {
          $termIds[] = $term->id();
        }
      }
    }

    if (empty($termIds)) {     
      return []; // No taxonomy terms found.
    }

    // Query for related articles.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'news_article') // Only interested in News Articles here
      ->condition($departmentName . '.target_id', $termIds, 'IN')
      ->condition('nid', $currentNode->id(), '<>') // Exclude current New Article.
      ->sort('created', 'DESC')
      ->range(0, 3) // Get the 3 most recent News.
      ->accessCheck(TRUE); // Ensure access permissions are respected.

    $nids = $query->execute();

    if (empty($nids)) {
      return []; // No related News Articles found.
    }

    $newsArticles = Node::loadMultiple($nids);

    $items = [];
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('node');
    foreach ($newsArticles  as $newsArticle) {
     $items[] = $viewBuilder->view($newsArticle, 'teaser'); 
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Latest News:'),
      '#cache' => [
        'tags' => ['node_list', 'taxonomy_term_list'], // This ensures the block is invalidated when News articles or taxonomy terms are updated
        'contexts' => ['url.path'], // Cache based on path.
      ],
    ];
    
  }

}
