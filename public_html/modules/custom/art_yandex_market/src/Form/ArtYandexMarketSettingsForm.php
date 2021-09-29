<?php

/**
 * @file
 * Contains \Drupal\art_yandex_market\Form\ArtYandexMarketSettingsForm.
 */

namespace Drupal\art_yandex_market\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\commerce_product\Entity\ProductType;



class ArtYandexMarketSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'art_yandex_market_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['art_yandex_market.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('art_yandex_market.settings');


//    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'catalog']);
    $vid = 'catalog';
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);

//    var_dump($terms);

//    foreach ($terms as $term) {
//      $term_data[] = array(
//        'id' => $term->tid,
//        'name' => $term->name
//      );
//    }
//    var_dump($term_data);

    $options = [];
    foreach ($terms as $term) {
      if ($term->depth == 1 || $term->depth == 2) {
        $options[$term->tid] = $term->name;
      }
    }

    $form['catalog_terms'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Catalog terms'),
      '#options' => $options,
      '#default_value' => $config->get('catalog_terms'),
//      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

//    $form['types'] = [
//      '#type' => 'checkboxes',
//      '#title' => t('Select product types for export'),
//      '#options' => $this->getProductTypes(),
//      '#default_value' => $config->get('types'),
//      '#required' => TRUE,
//    ];

    $form['stock'] = [
      '#type' => 'select',
      '#title' => t('Stock settings'),
      '#description' => t('Yandex.Market has "delivery" field. Select if it is enabled'),
      '#options' => ['0' => t("Any"), '1' => t("In stock"), '2' => t("Out of stock")],
      '#default_value' => $config->get('stock'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
      parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();

    $this->config('art_yandex_market.settings')
      ->set('catalog_terms', $values['catalog_terms'])
      ->set('types', $values['types'])
      ->set('stock', $values['stock'])
//      ->set('phone_number', $values['phone_number'])
      ->save();
  }

//  /**
//   * Get all product types
//   * @return array
//   */
//  public function getProductTypes() {
//    $product_types = ProductType::loadMultiple();
//    return array_keys($product_types);
//  }

}