<?php

/**
 * @file
 * Definition of \Drupal\editor\Tests\EditorManagerTest.
 */

namespace Drupal\editor\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\editor\Plugin\EditorManager;


/**
 * Unit tests for the configurable text editor manager.
 */
class EditorManagerTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'editor');

  /**
   * The manager for text editor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $editorManager;

  public static function getInfo() {
    return array(
      'name' => 'Text editor manager',
      'description' => 'Tests detection of text editors and correct generation of attachments.',
      'group' => 'Text Editor',
    );
  }

  function setUp() {
    parent::setUp();

    // Install the Filter module.
    $this->enableModules(array('filter'));

    // Add text formats.
    $filtered_html_format = array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(),
    );
    $filtered_html_format = (object) $filtered_html_format;
    filter_format_save($filtered_html_format);
    $full_html_format = array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(),
    );
    $full_html_format = (object) $full_html_format;
    filter_format_save($full_html_format);
  }

  /**
   * Tests the configurable text editor manager.
   */
  function testManager() {
    $this->editorManager = new EditorManager();

    // Case 1: no text editor available:
    // - listOptions() should return an empty list of options
    // - getAttachments() should return an empty #attachments array (and not
    //   a JS settings structure that is empty)
    $this->assertIdentical(array(), $this->editorManager->listOptions(), 'When no text editor is enabled, the manager works correctly.');
    $this->assertIdentical(array(), $this->editorManager->getAttachments(array()), 'No attachments when no text editor is enabled and retrieving attachments for zero text formats.');
    $this->assertIdentical(array(), $this->editorManager->getAttachments(array('filtered_html', 'full_html')), 'No attachments when no text editor is enabled and retrieving attachments for multiple text formats.');

    // Enable the Text Editor Test module, which has the Unicorn Editor and
    // clear the editor manager's cache so it is picked up.
    $this->enableModules(array('editor_test'));
    $this->editorManager->clearCachedDefinitions();

    // Case 2: a text editor available.
    $this->assertIdentical(array('unicorn' => 'Unicorn Editor'), $this->editorManager->listOptions(), 'When some text editor is enabled, the manager works correctly.');

    // Case 3: a text editor available & associated (but associated only with
    // the 'Full HTML' text format).
    $unicorn_plugin = $this->editorManager->createInstance('unicorn');
    $default_editor_settings = $unicorn_plugin->getDefaultSettings();
    $editor = entity_create('editor', array(
      'name' => 'Full HTML',
      'format' => 'full_html',
      'editor' => 'unicorn',
    ));
    $editor->save();
    $this->assertIdentical(array(), $this->editorManager->getAttachments(array()), 'No attachments when one text editor is enabled and retrieving attachments for zero text formats.');
    $expected = array(
      'library' => array(
        0 => array('edit_test', 'unicorn'),
      ),
      'js' => array(
        0 => array(
          'type' => 'setting',
          'data' => array('editor' => array('formats' => array(
            'full_html' => array(
              'editor' => 'unicorn',
              'editorSettings' => $unicorn_plugin->getJSSettings($editor),
            )
          )))
        )
      ),
    );
    $this->assertIdentical($expected, $this->editorManager->getAttachments(array('filtered_html', 'full_html')), 'Correct attachments when one text editor is enabled and retrieving attachments for multiple text formats.');
  }

}
