<?php

namespace Drupal\Tests\media_migration\Unit\Plugin\migrate\process;

use Drupal\media_migration\Plugin\migrate\process\CKEditorLinkFileToLinkitFilter;

/**
 * Tests the CKEditorLinkFileToLinkitFilter migration process plugin.
 *
 * @coversDefaultClass \Drupal\media_migration\Plugin\migrate\process\CKEditorLinkFileToLinkitFilter
 * @group media_migration
 */
class CKEditorLinkFileToLinkitFilterTest extends ProcessTestBase {

  /**
   * Tests transform to linkit anchor tags.
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform($input_value, $expected_value): void {
    $plugin = new CKEditorLinkFileToLinkitFilter(
      [],
      'ckeditor_link_file_to_linkit',
      [],
      $this->migration,
      $this->uuidOracle->reveal(),
      $this->migrateLookup->reveal(),
      $this->entityTypeManager->reveal()
    );

    $this->assertEquals(
      $expected_value,
      $plugin->transform(
        $input_value,
        $this->migrateExecutable,
        $this->row,
        'destination_property'
      )
    );
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array
   *   The test cases.
   */
  public function providerTestTransform(): array {
    return [
      'Plain text only' => [
        'Input' => 'Lorem ipsum dolor sit amet.',
        'Expected' => 'Lorem ipsum dolor sit amet.',
      ],

      'Plain text with unrelated tokens and JSONs (before MediaWysiwyg)' => [
        'Input' => <<<END
Sed hendrerit.
[[{"nid":"1"}]]
Nam scelerisque viverra.
[[nid:1]] Curabitur.
END
        ,
        'Expected' => <<<END
Sed hendrerit.
[[{"nid":"1"}]]
Nam scelerisque viverra.
[[nid:1]] Curabitur.
END
        ,
      ],

      'Plain text with a single anchor tag' => [
        'Input' => <<<END
Nulla est rhoncus est?

Eleifend <a target="_blank" href="/file/1" title="Pellentesque alt text"> non nulla</a>!
Mauris efficitur metus.
END
        ,
        'Expected' => <<<END
Nulla est rhoncus est?

Eleifend <a target="_blank" href="/file/1" title="Pellentesque alt text" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="jpeg1-uuid"> non nulla</a>!
Mauris efficitur metus.
END
        ,
      ],

      'Plain text with multiple anchor- and other kind of tags' => [
        'Input' => <<<END
Fusce semper rutrum blandit. Sed nec <a href="/node">semper</a> eros.

Quisque <a href='/file/1'>molestie</a>.
This is a file in a real <a href='/file/in/a/subdirectory.txt'>subdirectory</a>.

<drupal-media data-entity-type="media" data-entity-uuid="png2-uuid" data-view-mode="default" data-align="right"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="svg3-uuid" data-view-mode="default" alt="Suspendisse alt text"></drupal-media>
<a href='/file/3'>Aliquam lacus</a> arcu!

<a href='/file/2'>Duis egestas</a>.
END
        ,
        'Expected' => <<<END
Fusce semper rutrum blandit. Sed nec <a href="/node">semper</a> eros.

Quisque <a href="/file/1" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="jpeg1-uuid">molestie</a>.
This is a file in a real <a href="/file/in/a/subdirectory.txt">subdirectory</a>.

<drupal-media data-entity-type="media" data-entity-uuid="png2-uuid" data-view-mode="default" data-align="right"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="svg3-uuid" data-view-mode="default" alt="Suspendisse alt text"></drupal-media>
<a href="/file/3" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="svg3-uuid">Aliquam lacus</a> arcu!

<a href="/file/2" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="png2-uuid">Duis egestas</a>.
END
        ,
      ],

      'HTML text with JSON tokens and with embed code' => [
        'Input' => <<<END
<p class="lead">Some text. [[{"foo":"bar"}]]</p>
Lorem ipsum dolor sit amet.
[[{"fid":"3","view_mode":"default","type":"media"}]]
Nam finibus elit nec ipsum feugiat convallis.
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="1" data-entity-embed-display="view_mode:media.wysiwyg"></drupal-entity>
Aliquam tellus nisi.
[[nid:1]]
<p>Lorem ipsum dolor sit amet.</p>
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="453" data-entity-embed-display="view_mode:media.default"></drupal-entity>
<p>Nam finibus elit nec ipsum feugiat convallis.</p>
<p>[[{"fid":"154","view_mode":"wysiwyg","type":"media"}]]</p>
<ul>
  <li>Aliquam tellus nisi.</li>
</ul>
[[tid:15]]
<p></p>
END
        ,
        'Expected' => <<<END
<p class="lead">Some text. [[{"foo":"bar"}]]</p>
Lorem ipsum dolor sit amet.
[[{"fid":"3","view_mode":"default","type":"media"}]]
Nam finibus elit nec ipsum feugiat convallis.
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="1" data-entity-embed-display="view_mode:media.wysiwyg"></drupal-entity>
Aliquam tellus nisi.
[[nid:1]]
<p>Lorem ipsum dolor sit amet.</p>
<drupal-entity data-embed-button="media" data-entity-type="media" data-entity-id="453" data-entity-embed-display="view_mode:media.default"></drupal-entity>
<p>Nam finibus elit nec ipsum feugiat convallis.</p>
<p>[[{"fid":"154","view_mode":"wysiwyg","type":"media"}]]</p>
<ul>
  <li>Aliquam tellus nisi.</li>
</ul>
[[tid:15]]
<p></p>
END
        ,
      ],

      'HTML with a single anchor tag' => [
        'Input' => <<<END
<p>Nulla est rhoncus est?</p>
<p>Eleifend <a target='_blank' href='/file/1' title='Pellentesque alt text'> non nulla</a>!</p>
<p>Mauris efficitur metus.</p>
END
        ,
        'Expected' => <<<END
<p>Nulla est rhoncus est?</p>
<p>Eleifend <a target="_blank" href="/file/1" title="Pellentesque alt text" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="jpeg1-uuid"> non nulla</a>!</p>
<p>Mauris efficitur metus.</p>
END
        ,
      ],

      'HTML with multiple anchor- and other kind of tags' => [
        'Input' => <<<END
<p>Fusce semper rutrum blandit. Sed nec <a href="/node">semper</a> eros.</p>

<p>Quisque <a href='/file/1'>molestie</a>.</p>
<p>This is a file in a real <a href='/file/in/a/subdirectory.txt'>subdirectory</a>.</p>

<drupal-media data-entity-type="media" data-entity-uuid="png2-uuid" data-view-mode="default" data-align="right"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="svg3-uuid" data-view-mode="default" alt="Suspendisse alt text"></drupal-media>
<p><a href='/file/3'>Aliquam lacus</a> arcu!</p>

<p><a href="/file/2">Duis egestas</a>.</p>
END
        ,
        'Expected' => <<<END
<p>Fusce semper rutrum blandit. Sed nec <a href="/node">semper</a> eros.</p>

<p>Quisque <a href="/file/1" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="jpeg1-uuid">molestie</a>.</p>
<p>This is a file in a real <a href="/file/in/a/subdirectory.txt">subdirectory</a>.</p>

<drupal-media data-entity-type="media" data-entity-uuid="png2-uuid" data-view-mode="default" data-align="right"></drupal-media>
<drupal-media data-entity-type="media" data-entity-uuid="svg3-uuid" data-view-mode="default" alt="Suspendisse alt text"></drupal-media>
<p><a href="/file/3" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="svg3-uuid">Aliquam lacus</a> arcu!</p>

<p><a href="/file/2" data-entity-substitution="media" data-entity-type="media" data-entity-uuid="png2-uuid">Duis egestas</a>.</p>
END
        ,
      ],
    ];
  }

}
