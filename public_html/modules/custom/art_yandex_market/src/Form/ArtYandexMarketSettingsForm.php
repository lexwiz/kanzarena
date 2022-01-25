<?php

/**
 * @file
 * Contains \Drupal\art_yandex_market\Form\ArtYandexMarketSettingsForm.
 */

namespace Drupal\art_yandex_market\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\commerce_product\Entity\ProductType;

use Drupal\taxonomy\Entity\Term;


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

    $products = \Drupal::entityTypeManager()->getStorage('commerce_product')->loadByProperties(['field_yml'=>1]);

    $detail_terms = [];

    foreach ($products as $product) {
      $tid = $product->get('field_catalog')->target_id;
      $detail_terms[$tid][] = $product->label();
    }

    $options = [];

    foreach ($detail_terms as $key_details => $detail_term) {
      $options[$key_details] = $this->getTermName($key_details);
      $form[$key_details] = [
        '#type' => 'details',                                        // тип элемента формы
        '#title' => t($this->getTermName($key_details)) . ' (' . count($detail_term) . ')',                    // заголовок fieldset

      ];
      foreach ($detail_term as $key_product => $product_name) {
        $form[$key_details][$key_product] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $product_name,
        ];
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

    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select product types for export'),
      '#options' => $this->getProductTypes(),
      '#default_value' => $config->get('types'),
      '#required' => TRUE,
    ];

    $form['stock'] = [
      '#type' => 'select',
      '#title' => $this->t('Stock settings'),
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
      ->save();
  }

  /**
   * Get all product types
   * @return array
   */
  public function getProductTypes() {
    $product_types = ProductType::loadMultiple();
    return array_keys($product_types);
  }

  public function getTermName ($tid) {
    if (is_null($tid)){
      $term_name = $this->t("No name");
    }
    else {
      $term_name = Term::load($tid)->getName();
    }

    return $term_name;

  }

}
