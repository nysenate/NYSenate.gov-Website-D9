<?php

namespace Drupal\Tests\media_migration\Kernel\Plugin\migrate\source\d7;

/**
 * Tests the file entiy item source plugin.
 *
 * @covers \Drupal\media_migration\Plugin\migrate\source\d7\FilePlain
 *
 * @group media_migration
 */
class FilePlainTest extends FilePlainSourceFieldInstanceTest {

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    return [
      'No filtering' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => 1,
            'uid' => 1,
            'filename' => 'Blue PNG',
            'uri' => 'public://blue.png',
            'filemime' => 'image/png',
            'filesize' => 9061,
            'status' => 1,
            'timestamp' => 1587725909,
            'scheme' => 'public',
            'mime' => 'image',
            'bundle' => 'image',
          ],
          [
            'fid' => 3,
            'uid' => 1,
            'filename' => 'red.jpeg',
            'uri' => 'public://red.jpeg',
            'filemime' => 'image/jpeg',
            'filesize' => 19098,
            'status' => 1,
            'timestamp' => 1587726037,
            'scheme' => 'public',
            'mime' => 'image',
            'bundle' => 'image',
          ],
          [
            'fid' => 2,
            'uid' => 1,
            'filename' => 'green.jpg',
            'uri' => 'private://field/image/green.jpg',
            'filemime' => 'image/jpeg',
            'filesize' => 11050,
            'status' => 1,
            'timestamp' => 1587730322,
            'scheme' => 'private',
            'mime' => 'image',
            'bundle' => 'image_private',
          ],
          [
            'fid' => 6,
            'uid' => 1,
            'filename' => 'LICENSE.txt',
            'uri' => 'public://LICENSE.txt',
            'filemime' => 'text/plain',
            'filesize' => 18002,
            'status' => 1,
            'timestamp' => 1587731111,
            'scheme' => 'public',
            'mime' => 'text',
            'bundle' => 'document',
          ],
          [
            'fid' => 7,
            'uid' => 1,
            'filename' => 'yellow.jpg',
            'uri' => 'public://field/image/yellow.jpg',
            'filemime' => 'image/jpeg',
            'filesize' => 5363,
            'status' => 1,
            'timestamp' => 1588600435,
            'scheme' => 'public',
            'mime' => 'image',
            'bundle' => 'image',
          ],
          [
            'fid' => 8,
            'uid' => 1,
            'filename' => 'video.webm',
            'uri' => 'public://video.webm',
            'filemime' => 'video/webm',
            'filesize' => 18123,
            'status' => 1,
            'timestamp' => 1594037784,
            'scheme' => 'public',
            'mime' => 'video',
            'bundle' => 'video',
          ],
          [
            'fid' => 9,
            'uid' => 1,
            'filename' => 'video.mp4',
            'uri' => 'public://video.mp4',
            'filemime' => 'video/mp4',
            'filesize' => 18011,
            'status' => 1,
            'timestamp' => 1594117700,
            'scheme' => 'public',
            'mime' => 'video',
            'bundle' => 'video',
          ],
          [
            'fid' => 10,
            'uid' => 1,
            'filename' => 'yellow.webp',
            'uri' => 'public://yellow.webp',
            'filemime' => 'image/webp',
            'filesize' => 3238,
            'status' => 1,
            'timestamp' => 1594191582,
            'scheme' => 'public',
            'mime' => 'image',
            'bundle' => 'image',
          ],
          [
            'fid' => 11,
            'uid' => 1,
            'filename' => 'audio.m4a',
            'uri' => 'public://audio.m4a',
            'filemime' => 'audio/mpeg',
            'filesize' => 10711,
            'status' => 1,
            'timestamp' => 1594193701,
            'scheme' => 'public',
            'mime' => 'audio',
            'bundle' => 'audio',
          ],
          [
            'fid' => 12,
            'uid' => 1,
            'filename' => 'document.odt',
            'uri' => 'public://document.odt',
            'filemime' => 'application/vnd.oasis.opendocument.text',
            'filesize' => 8089,
            'status' => 1,
            'timestamp' => 1594201103,
            'scheme' => 'public',
            'mime' => 'application',
            'bundle' => 'document',
          ],
        ],
        'count' => NULL,
        'config' => [],
      ],
      'Filtering for mime "application"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => 12,
            'uid' => 1,
            'filename' => 'document.odt',
            'uri' => 'public://document.odt',
            'filemime' => 'application/vnd.oasis.opendocument.text',
            'filesize' => 8089,
            'status' => 1,
            'timestamp' => 1594201103,
            'scheme' => 'public',
            'mime' => 'application',
            'bundle' => 'document',
          ],
        ],
        'count' => NULL,
        'config' => [
          'mime' => 'application',
        ],
      ],
      'Filtering for scheme "private" and mime "image"' => [
        'source' => self::SOURCE_DATABASE,
        'expected' => [
          [
            'fid' => 2,
            'uid' => 1,
            'filename' => 'green.jpg',
            'uri' => 'private://field/image/green.jpg',
            'filemime' => 'image/jpeg',
            'filesize' => 11050,
            'status' => 1,
            'timestamp' => 1587730322,
            'scheme' => 'private',
            'mime' => 'image',
            'bundle' => 'image_private',
          ],
        ],
        'count' => NULL,
        'config' => [
          'scheme' => 'private',
          'mime' => 'image',
        ],
      ],
    ];
  }

}
