<?php

namespace Drupal\Register_Multiple\Controller;

//use Drupal\Register_Multiple\Utility\DescriptionTemplateTrait;

/**
 * Simple page controller for drupal.
 */
class Page {

//  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  public function getModuleName() {
    return 'register_multiple';
  }
  
  public function description() {
    $template_path = $this->getDescriptionTemplatePath();
    $template = file_get_contents($template_path);
    $build = [
        'description' => [
            '#type' => 'inline_template',
            '#template' => $template,
            '#context' => $this->getDescriptionVariables(),
        ],
    ];
    return $build;
  }
  
  /**
   * Name of our module.
   *
   * @return string
   *   A module name.
   */
  //protected function getModuleName();
  
  /**
   * Variables to act as context to the twig template file.
   *
   * @return array
   *   Associative array that defines context for a template.
   */
  protected function getDescriptionVariables() {
    $variables = [
        'module' => $this->getModuleName(),
    ];
    return $variables;
  }
  
  /**
   * Get full path to the template.
   *
   * @return string
   *   Path string.
   */
  protected function getDescriptionTemplatePath() {
    return drupal_get_path('module', $this->getModuleName()) . "/templates/description.html.twig";
  }
}
