<?php

namespace Drupal\twitter_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a twitter block block type.
 *
 * @Block(
 *   id = "twitter_block",
 *   admin_label = @Translation("Twitter block"),
 *   category = @Translation("Twitter"),
 * )
 */
class TwitterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $config = $this->getConfiguration();

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config['username'],
      '#required' => TRUE,
      '#field_prefix' => '@',
    ];
    $form['appearance'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Appearance'),
    ];
    $form['appearance']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#default_value' => $config['theme'],
      '#options' => [
        '' => $this->t('Default'),
        'dark' => $this->t('Dark'),
      ],
      '#description' => $this->t('Select a theme for the widget.'),
    ];
    $form['appearance']['link_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link color'),
      '#default_value' => $config['link_color'],
      '#maxlength' => 6,
      '#size' => 6,
      '#field_prefix' => '#',
      '#description' => $this->t('Change the link color used by the widget.
        Takes an %format hex format color. Note that some icons in the widget
        will also appear this color.', ['%format' => 'abc123']),
    ];
    $form['appearance']['border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border color'),
      '#default_value' => $config['border_color'],
      '#maxlength' => 6,
      '#size' => 6,
      '#field_prefix' => '#',
      '#description' => $this->t('Change the border color used by the widget.
        Takes an %format hex format color.', ['%format' => 'abc123']),
    ];
    $form['appearance']['chrome'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Chrome'),
      '#default_value' => $config['chrome'],
      '#options' => [
        'noheader' => $this->t('No header'),
        'nofooter' => $this->t('No footer'),
        'noborders' => $this->t('No borders'),
        'noscrollbar' => $this->t('No scrollbar'),
        'transparent' => $this->t('Transparent'),
      ],
      '#description' => $this->t('Control the widget layout and chrome.'),
    ];
    $form['functionality'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Functionality'),
    ];
    $form['functionality']['related'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Related users'),
      '#default_value' => $config['related'],
      '#description' => $this->t('As per the Tweet and follow buttons, you may
        provide a comma-separated list of user screen names as suggested
        followers to a user after they reply, Retweet, or favorite a Tweet in the timeline.'),
    ];
    $form['functionality']['tweet_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Tweet limit'),
      '#default_value' => $config['tweet_limit'],
      '#options' => ['' => $this->t('Auto')],
      '#description' => $this->t('Fix the size of a timeline to a preset number
        of Tweets between 1 and 20. The timeline will render the specified number
        of Tweets from the timeline, expanding the height of the widget to
        display all Tweets without scrolling. Since the widget is of a fixed
        size, it will not poll for updates when using this option.'),
    ];
    $form['functionality']['tweet_limit']['#options'] +=
      array_combine(range(1, 20), range(1, 20));

    $form['size'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Size'),
      '#description' => $this->t('Embedded timelines are flexible and adaptive,
        functioning at a variety of dimensions ranging from wide to narrow,
        and short to tall. The default dimensions for a timeline are 520×600px,
        which can be overridden to fit the dimension requirements of your page.
        Setting a width is not required, and by default the widget will shrink
        to the width of its parent element in the page.'),
    ];
    $form['size']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $config['width'],
      '#size' => 6,
      '#field_suffix' => 'px',
      '#description' => $this->t('Change the width of the widget.'),
    ];
    $form['size']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $config['height'],
      '#size' => 6,
      '#field_suffix' => 'px',
      '#description' => $this->t('Change the height of the widget.'),
    ];
    $form['size']['note'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('The minimum width of a timeline is 180px
        and the maximum is 520px. The minimum height is 200px.') . '</p>',
    ];
    $form['accessibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Accessibility'),
    ];
    $form['accessibility']['language'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $config['language'],
      '#maxlength' => 5,
      '#size' => 5,
      '#description' => $this->t('The widget language is detected from the page,
        based on the language of your content. Enter a <a href="@website">
        language code</a> to manually override the language.',
        ['@website' => 'http://www.w3.org/TR/html401/struct/dirlang.html#h-8.1.1']),
    ];
    $form['accessibility']['polite'] = [
      '#type' => 'select',
      '#title' => $this->t('ARIA politeness'),
      '#options' => [
        'polite' => $this->t('Polite'),
        'assertive' => $this->t('Assertive'),
      ],
      '#default_value' => $config['polite'],
      '#description' => $this->t('ARIA is an accessibility system that aids people
        using assistive technology interacting with dynamic web content.
        <a href="@website">Read more about ARIA on W3C\'s website</a>. By
        default, the embedded timeline uses the least obtrusive setting:
        "polite". If you\'re using an embedded timeline as a primary source of
        content on your page, you may wish to override this to the assertive
        setting, using "assertive".',
        ['@website' => 'http://www.w3.org/WAI/intro/aria.php']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('username', $form_state->getValue('username'));
    foreach (['appearance', 'functionality', 'size', 'accessibility'] as $fieldset) {
      $fieldset_values = $form_state->getValue($fieldset);
      foreach ($fieldset_values as $key => $value) {
        $this->setConfigurationValue($key, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();

    $render['block'] = [
      '#type' => 'link',
      '#title' => $this->t('Tweets by @@username', ['@username' => $config['username']]),
      '#url' => Url::fromUri('https://twitter.com/' . $config['username']),
      '#attributes' => [
        'class' => ['twitter-timeline'],
      ],
      '#attached' => [
        'library' => ['twitter_block/widgets'],
      ],
    ];

    if (!empty($config['theme'])) {
      $render['block']['#attributes']['data-theme'] = $config['theme'];
    }

    if (!empty($config['link_color'])) {
      $render['block']['#attributes']['data-link-color'] = '#' . $config['link_color'];
    }

    if (!empty($config['width'])) {
      $render['block']['#attributes']['data-width'] = $config['width'];
    }

    if (!empty($config['height'])) {
      $render['block']['#attributes']['data-height'] = $config['height'];
    }

    if (!empty($config['chrome'])) {
      $options = array_keys(array_filter($config['chrome']));

      if (count($options)) {
        $render['block']['#attributes']['data-chrome'] = implode(' ', $options);
      }
    }

    if (!empty($config['border_color'])) {
      $render['block']['#attributes']['data-border-color'] = '#' . $config['border_color'];
    }

    if (!empty($config['language'])) {
      $render['block']['#attributes']['lang'] = $config['language'];
    }

    if (!empty($config['tweet_limit'])) {
      $render['block']['#attributes']['data-tweet-limit'] = $config['tweet_limit'];
    }

    if (!empty($config['related'])) {
      $render['block']['#attributes']['data-related'] = $config['related'];
    }

    if (!empty($config['polite'])) {
      $render['block']['#attributes']['aria-polite'] = $config['polite'];
    }

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'username' => '',
      'theme' => '',
      'link_color' => '',
      'width' => '',
      'height' => '',
      'chrome' => [],
      'border_color' => '',
      'language' => '',
      'tweet_limit' => '',
      'related' => '',
      'polite' => '',
    ];
  }

}
