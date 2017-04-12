<?php
/**
 * @file
 * Contains \Drupal\json_forms\Controller\JsonFormsController.
 *
 * Controller for admin paths to CRUD Articles
 */

namespace Drupal\json_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\edan_search\Controller\EdanSearchController;
use Drupal\json_forms\JsonFormsData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
//use Drupal\JsonFormsModel;

class JsonFormsController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Constructs an AdminController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * @return string
   */
  public function getTitle() {
    //@todo dynamic title based on the title
    return 'View article content';
  }

  /**
   * Article functions.
   */

  /**
   * Presents an administrative article listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param string $type
   *   The type of the overview form ('approval' or 'new') default to 'new'.
   *
   * @return array
   *   The article multiple delete confirmation form or the articles overview
   *   administration form.
   */
  public function viewAllArticlesPage(Request $request) {
    return $this->formBuilder->getForm('\Drupal\json_forms\Form\AdminArticlesForm', $request);
  }

  public function newArticlePage(Request $request) {
    return $this->formBuilder->getForm('\Drupal\json_forms\Form\AdminArticleForm', 'add');
  }

  public function editArticlePage(Request $request, $article_id, $action = 'edit') {
    return $this->formBuilder->getForm('\Drupal\json_forms\Form\AdminArticleForm', $article_id, $action);
  }

  public function deleteArticlePage(Request $request, $article_id, $action = 'delete') {
    return $this->formBuilder->getForm('\Drupal\json_forms\Form\AdminArticleDeleteForm', $article_id, $action);
  }

  public function viewArticlePage(Request $request, $article_id = NULL) {

    $js = new JsonFormsData();
    $data = (array)($js->getRecord('article', $article_id));

    $content['#theme'] = 'json_forms_article_template';
    $content['#article_id'] = $article_id;
    $content['#title'] = $data['title'];
    $content['#data'] = $data;

    return $content;
  }

}