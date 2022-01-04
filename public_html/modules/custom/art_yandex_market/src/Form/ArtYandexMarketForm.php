<?php

/**
 * @file
 * Contains \Drupal\art_yandex_market\Form\ArtYandexMarketForm.
 */

namespace Drupal\art_yandex_market\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\yandex_yml\YandexYml\Shop\Shop;
use Drupal\yandex_yml\YandexYml\Currency\Currencies;
use Drupal\yandex_yml\YandexYml\Currency\Currency;
use Drupal\yandex_yml\YandexYml\Category\Categories;
use Drupal\yandex_yml\YandexYml\Category\Category;
use Drupal\yandex_yml\YandexYml\Delivery\DeliveryOptions;
use Drupal\yandex_yml\YandexYml\Delivery\DeliveryOption;
use Drupal\yandex_yml\YandexYml\Offer\Offers;
use Drupal\yandex_yml\YandexYml\Offer\OfferSimple;

use Drupal\commerce_product\Entity\ProductVariation;

use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;


class ArtYandexMarketForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'art_yandex_market_form';
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

    $store = \Drupal\commerce_store\Entity\Store::load(1);
    $order_type = 'default';
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $cart = $cart_provider->getCart($order_type, $store);

//    foreach ($cart-> getItems() as $item) {
//      foreach($item->get('purchased_entity')->getValue() as $purchased_entity){
//        print_r($purchased_entity);
//      }
//    }
    var_dump($cart->getItems()[0]->getPurchasedEntityId());
//    var_dump(get_class_methods($cart->getItems()[0]));

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run batch'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

//  /**
//   * {@inheritdoc}
//   */
//  public function validateForm(array &$form, FormStateInterface $form_state) {
//      parent::validateForm($form, $form_state);
//  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->createPriceList();

  }

  public function createPriceList () {

    $config = $this->config('art_yandex_market.settings');

    $shop_info = new Shop("Art", "IP Saenko" );
    $shop_info->setPlatform('Drupal');


    // Add currencies first.
    $currencies = new Currencies();
    $currencies
      ->addCurrency(new Currency('RUB', 1));
    $shop_info->setCurrencies($currencies);

    // Then categories.
    $categories = new Categories();

    //    $terms = $this->getTerms('catalog');
    $terms = $config->get('catalog_terms');
    $terms = $this->getTerms($terms);
    //    print_r($terms);

    foreach ($terms as $term) {
      $term_name = Term::load($term)->getName();
      $categories->addCategory(new Category($term, $term_name));
    }
    $shop_info->setCategories($categories);

    // If you want, add delivery options.

    $delivery_options = new DeliveryOptions();
    $delivery_options->addOption(new DeliveryOption('0', '2-4'));
    $shop_info->setDeliveryOptions($delivery_options);

    //    // And pickup options.
    //    $shop_info->setPickupOptions($pickup_options);
    //
    // Wants enable auto discounts for products in Yandex.Market?
    $shop_info->setEnableAutoDiscounts(TRUE);

    $offers = new Offers();

    $products = \Drupal::entityTypeManager()->getStorage('commerce_product')->loadByProperties(['field_catalog'=>$terms]);
//    var_dump($products);

    foreach ($products as $key => $product) {
      $this->createOffersList($offers, $product);

    }

    $shop_info->setOffers($offers);

    /** @var \Drupal\yandex_yml\YandexYmlGeneratorInterface $yml_generator */
    $yml_generator = \Drupal::service('yandex_yml.generator');
    $yml_generator->setShop($shop_info);
    $yml_generator->generateFile($filename = 'products.xml', $destination_path = 'public://');

  }


  public function createOffersList ($offers, $product) {

    $variation_date = $this->getCommercePrice($product->get('variations')->target_id);

    $offer_id = $product->id();
    $offer_url = $product->toUrl()->setAbsolute()->toString();
    $offer_price = $variation_date['price_number'];
    $offer_currency_id = 'RUR';
    $offer_category_id = $product->get('field_catalog')->target_id;
    $offer_name = $product->label();

    //      $offer_description = <<<HTML
    //  <h3>Мороженица Brand 3811</h3>
    //  <p>Это прибор, который придётся по вкусу всем любителям десертов и сладостей, ведь с его помощью вы сможете делать вкусное домашнее мороженое из натуральных ингредиентов.</p>
    //HTML;
    $offer_description = $product->get('body')->value;

    //    $delivery_options = new DeliveryOptions();
    //    $delivery_option = new DeliveryOption('300', '1');
    //    $delivery_option->setOrderBefore(18);
    //    $delivery_options->addOption($delivery_option);

    $offer_simple = new OfferSimple(
      $offer_id,
      $offer_url,
      $offer_price,
      $offer_currency_id,
      $offer_category_id,
      $offer_name
    );

    $offer_simple
      //      ->setBid(80)
      ->setVendor($this->getTermName($product->get('field_brand')->target_id))
      ->setVendorCode($variation_date['sku_m'])
      //      ->setOldPrice(9900)
      ->setPicture($this->getMediaImage($product->get('field_photo')->target_id))
      ->setStore(FALSE)
      ->setPickup(FALSE)
      ->setDelivery(TRUE)
      //      ->setDeliveryOptions($delivery_options)
      ->setDescription($offer_description, TRUE)
      //      ->addParam(new Param('Цвет', 'белый'))
//      ->setSalesNotes('Необходимо предоплата.')
      ->setManufacturerWarranty(TRUE)
      ->setCountryOfOrigin($this->getTermName($product->get('field_country')->target_id))
      ->setBarcode($variation_date['barcode']);

    $offers->addOffer($offer_simple);

    return $offers;

  }

  public function getMediaImage ($mid) {

//    $mid = $product->get('field_photo')->target_id;

    if ($mid) {
      $media = Media::load($mid);
      $fid = $media->get('field_media_image')->target_id;
      $file = File::load($fid);

      $url = file_create_url($file->getFileUri());
    }
    else
    {
      $url = "test";
    }
    return $url;

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

  public function getCommercePrice ($pid) {
    $result = [];
    $product_variation = ProductVariation::load($pid);
    $result['price_number'] = round($product_variation->get('price')->number,2);
    //$price_currency = $product_variation->get('price')-
    if ($product_variation->get('field_barcode')->value) {
      $result['barcode'] = (int)$product_variation->get('field_barcode')->value;
    }
    else {
      $result['barcode'] = $this->t("No name");
    }

    if ($product_variation->get('field_sku_m')->value) {
      $result['sku_m'] = $product_variation->get('field_sku_m')->value;
    }
    else {
      $result['sku_m'] = $this->t("No name");
    }

    return $result;

  }

  public function getTerms($terms) {
    foreach ($terms as $k => $v)
      if (empty($v)) {
        unset($terms[$k]);
      }
    return $terms;
  }

}
