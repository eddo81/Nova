<?php
namespace Nova\Core;

/**
 * Class Template
 * @package Nova
 * @author "eddo81 <eduardo_jonnerstig@live.com>"
 */

class Template {
  /** 
   * @var string 
   */
  private $root_uri;

  /** 
   * @var string 
   */
  private $extension = '.php';

  /**
   * @param string $root_uri The root uri for the theme templates.
   * @return self 
   */
  public function __construct(string $root_uri = '') {
    $this->root_uri = (!empty($root_uri)) ? rtrim($root_uri, '/') . '/' : $root_uri;
  }

  /**
   * Like ***get_template_part()*** but lets you pass args to the template file.
   * Args are available in the template as regular variables named after their corresponding key in the ***$vars*** array.
   * 
   * @param string $template_path
   * @param array $vars 
   * @return void
   */
  public function render($template_path, array $vars = []) : void {
    $template_path = (substr($template_path, -4) !== $this->extension) ? $template_path . $this->extension : $template_path; 
    $template_file = $this->root_uri . $template_path;

    if (!file_exists($template_file)) {
      return;
    }

    ob_start();

    if(!empty($vars)) {
      extract($vars);
    }

    require($template_file);
    echo $data = ob_get_clean();
  }
}