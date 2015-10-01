<?php
/**
 * sfRichTextEditorRedactor implements the Redactor rich text editor.
 * 
 * `redactor_dir` and `redactor_plugins` (optional) must be set in `apps/admin/config/settings.yml`.
 * 
 * `redactor_dir` : path where the Redactor source files are located under the web directory.
 * `redactor_plugins` : an array containing the plugins we wanted to use in Redactor.
 * 
 * For example:
 *     redactor_dir:      js/admin/redactor
 *     redactor_plugins:  [fontcolor, clips, fontfamily, fontsize, imagemanager, pluginName]
 * 
 * 
 * How to configure the `generator.yml` of the admin modules to use Redactor rich text editor?
 * 
 * You would need to set 'rich=redactor' at 'params' in order to use Redactor rich
 * text editor for the specified field. Optionally, you can also set
 * 'plugins:[pluginName1,pluginName2]' to install plugin into the specified
 * Redactor rich text editor.
 * 
 * For example:
 * fields:
 *   intro:
 *     name:     Intro
 *     type:     textarea_tag
 *     params:   rich=redactor plugins=[fontfamily,clips,fontsize] toggle=true tool=Minimal height=150 width=650
 *     help:     Only used on Weekenders and Roundups.
 */
class sfRichTextEditorRedactor extends sfRichTextEditor
{
  public function toHTML()
  {
    $options = $this->options;

    $id = _get_option($options, 'id', get_id_from_name($this->name, null));

    $redactor_js = '/redactor.js';
    $redactor_css = '/redactor.css';
    $redactor_plugins = sfConfig::get('sf_redactor_plugins');
    $choosen_plugins = array();

    $redactor_js_path = sfConfig::get('sf_redactor_dir') ? '/'.sfConfig::get('sf_redactor_dir').$redactor_js : null;
    $redactor_css_path = sfConfig::get('sf_redactor_dir') ? '/'.sfConfig::get('sf_redactor_dir').$redactor_css : null;

    if (!is_readable(sfConfig::get('sf_web_dir').$redactor_js_path) && !is_readable(sfConfig::get('sf_web_dir').$redactor_css_path))
    {
        throw new sfConfigurationException('You must install Redactor to user this helper (see redactor_dir).');
    }

    sfContext::getInstance()->getResponse()->addJavascript($redactor_js_path);
    sfContext::getInstance()->getResponse()->addStyleSheet($redactor_css_path);

    foreach ($redactor_plugins as $plugin)
    {

        $plugin_js_path = sfConfig::get('sf_redactor_dir') ? '/'.sfConfig::get('sf_redactor_dir').'/plugins/'.$plugin.'.js' : null;
        $plugin_css_path = sfConfig::get('sf_redactor_dir') ? '/'.sfConfig::get('sf_redactor_dir').'/plugins/'.$plugin.'.css' : null;

        if (is_readable(sfConfig::get('sf_web_dir').$plugin_js_path))
        {
          sfContext::getInstance()->getResponse()->addJavascript($plugin_js_path);
        }

        if (is_readable(sfConfig::get('sf_web_dir').$plugin_css_path))
        {
          sfContext::getInstance()->getResponse()->addStyleSheet($plugin_css_path);
        }
    }

    use_helper('Javascript');

    if ($options['plugins'])
    {
        /*
        * $options['plugins'] returns [fontfamily, fontcolor] in string instead
        * of an array. Therefore, we will convert it into an array manually.
        */
        $choosen_plugins = explode(',', substr($options['plugins'], 1, -1));

        //Make sure that the plugins are properly installed.
        $missing_plugins = array_diff($choosen_plugins, $redactor_plugins);

        if (!empty($missing_plugins))
        {
            $err_msg = 'You must install '.implode(',', $missing_plugins).' at sf_redactor_plugins.';

            throw new sfConfigurationException($err_msg);
        }
    }

    $redactor_js = "
      jQuery_redactor(function(){
          jQuery_redactor('#".$id."').redactor({
              formattingAdd: [
              {
                tag: 'q',
                title: 'Inline Quote'
              }],
              minHeight: 150,
              linebreaks: true,
              plugins: ". json_encode($choosen_plugins) .",
              tabKey: false,
              paragraphize: false,
              replaceTags: [
                  ['strike', 'del'],
                  ['i', 'em'],
                  ['b', 'strong'],
                  ['big', 'strong'],
                  ['strike', 'del']
              ]
          });
      });";

    return
      content_tag('textarea', $this->content, array_merge(array('name' => $this->name, 'id' => $id), _convert_options($options))).
      content_tag('script', $redactor_js, array('type' => 'text/javascript'));
  }
}
?>
