<?php

namespace Drupal\name;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Converts a name from an array of components into a defined format.
 */
class NameFormatParser {

  use StringTranslationTrait;

  /**
   * Markup style for decorating name components.
   *
   * @var string
   */
  protected $markup = 'none';

  /**
   * First separator.
   *
   * @var string
   */
  protected $sep1 = ' ';

  /**
   * Second separator.
   *
   * @var string
   */
  protected $sep2 = ', ';

  /**
   * Third separator.
   *
   * @var string
   */
  protected $sep3 = '';

  /**
   * Used to seperate words using the "b" and "B" modifiers.
   *
   * @var string
   */
  protected $boundaryRegExp = '/[\b,\s]/';

  /**
   * Parses a name component array into the given format.
   *
   * @param array $name_components
   *   Keyed array of name components.
   * @param string $format
   *   The name format pattern to generate the name.
   * @param array $settings
   *   Additional settings to control the parser.
   *   - sep1 (string): first separator.
   *   - sep2 (string): second separator.
   *   - sep3 (string): third separator.
   *   - markup (string): key of the markup type.
   *   - boundary (string): regexp for word boundary.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   A renderable object representing the name.
   */
  public function parse(array $name_components, $format = '', array $settings = []) {
    foreach (['sep1', 'sep2', 'sep3'] as $sep_key) {
      if (isset($settings[$sep_key])) {
        $this->{$sep_key} = (string) $settings[$sep_key];
      }
    }
    $this->markup = !empty($settings['markup']) ? $settings['markup'] : 'none';
    if (isset($settings['boundary']) && strlen($settings['boundary'])) {
      $this->boundary = $settings['boundary'];
    }

    $name_string = $this->format($name_components, $format);
    switch ($this->markup) {
      // Component values are already escaped.
      case 'simple':
      case 'rdfa':
      case 'microdata':
        return new FormattableMarkup($name_string, []);

      // Unescaped text.
      case 'raw':
        return $name_string;

      // Raw component values.
      case 'none':
      default:
        return new HtmlEscapedText($name_string);

    }
  }

  /**
   * Formats an array of name components into the supplied format.
   *
   * @param array $name_components
   *   A keyed array of the components.
   * @param string $format
   *   The name format string or segment to parse.
   * @param array $tokens
   *   The generated tokens.
   *
   * @return string
   *   The formatted string.
   */
  protected function format(array $name_components, $format = '', array $tokens = NULL) {
    if (empty($format)) {
      return '';
    }

    if (!isset($tokens)) {
      $tokens = $this->generateTokens($name_components);
    }

    // Neutralise any escaped backslashes.
    $format = str_replace('\\\\', "\t", $format);

    $pieces = [];
    $modifiers = '';
    $conditions = '';
    for ($i = 0; $i < strlen($format); $i++) {
      $char = $format[$i];
      $last_char = ($i > 0) ? $format[$i - 1] : FALSE;

      // Handle escaped letters.
      if ($char == '\\') {
        continue;
      }
      if ($last_char == '\\') {
        $pieces[] = $this->addComponent($char, $modifiers, $conditions);
        continue;
      }

      switch ($char) {
        case 'L':
        case 'U':
        case 'F':
        case 'T':
        case 'S':
        case 'G':
        case 'B':
        case 'b':
          $modifiers .= $char;
          break;

        case '=':
        case '^':
        case '|':
        case '+':
        case '-':
        case '~':
          $conditions .= $char;
          break;

        case '(':
        case ')':
          $remaining_string = substr($format, $i);
          if ($char == '(' && $closing_bracket = $this->closingBracketPosition($remaining_string)) {
            $sub_string = $this->format($tokens, substr($format, $i + 1, $closing_bracket - 1), $tokens);

            // Increment the counter past the closing bracket.
            $i += $closing_bracket;
            $pieces[] = $this->addComponent($sub_string, $modifiers, $conditions);
          }
          else {
            // Unmatched, add it.
            $pieces[] = $this->addComponent($char, $modifiers, $conditions);
          }
          break;

        default:
          if (array_key_exists($char, $tokens)) {
            $char = is_string($tokens[$char]) ? $tokens[$char] : '';
          }
          $pieces[] = $this->addComponent($char, $modifiers, $conditions);
          break;
      }
    }

    $parsed_pieces = [];
    for ($i = 0; $i < count($pieces); $i++) {
      $component = $pieces[$i]['value'];
      $conditions = $pieces[$i]['conditions'];

      $last_component = ($i > 0) ? $pieces[$i - 1]['value'] : FALSE;
      $next_component = ($i < count($pieces) - 1) ? $pieces[$i + 1]['value'] : FALSE;

      if (empty($conditions)) {
        $parsed_pieces[$i] = $component;
      }
      else {
        // Modifier: Conditional insertion. Insert if both the surrounding
        // tokens are not empty.
        if (strpos($conditions, '+') !== FALSE && !empty($last_component) && !empty($next_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Conditional insertion. Insert if the previous token is
        // not empty.
        if (strpos($conditions, '-') !== FALSE && !empty($last_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Conditional insertion. Insert if the previous token is
        // empty.
        if (strpos($conditions, '~') !== FALSE && empty($last_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Insert the token if the next token is empty.
        if (strpos($conditions, '^') !== FALSE && empty($next_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Insert the token if the next token is not empty.
        // This overrides the above two settings.
        if (strpos($conditions, '=') !== FALSE && !empty($next_component)) {
          $parsed_pieces[$i] = $component;
        }

        // Modifier: Conditional insertion. Uses the previous token unless
        // empty, otherwise insert this token.
        if (strpos($conditions, '|') !== FALSE) {
          if (empty($last_component)) {
            $parsed_pieces[$i] = $component;
          }
          else {
            unset($parsed_pieces[$i]);
          }
        }

      }
    }
    return str_replace('\\\\', "\t", implode('', $parsed_pieces));
  }

  /**
   * Adds a component.
   *
   * @param string $string
   *   The token string to process.
   * @param string $modifiers
   *   The modifiers to apply.
   * @param string $conditions
   *   The conditional flags.
   *
   * @return array
   *   The processed piece.
   */
  protected function addComponent($string, &$modifiers = '', &$conditions = '') {
    $string = $this->applyModifiers($string, $modifiers);
    $piece = [
      'value' => $string,
      'conditions' => $conditions,
    ];
    $conditions = '';
    $modifiers = '';
    return $piece;
  }

  /**
   * Applies the specified modifiers to the string.
   *
   * @param string $string
   *   The token string to process.
   * @param string $modifiers
   *   The modifiers to apply.
   *
   * @return string
   *   The processed string.
   */
  protected function applyModifiers($string, $modifiers) {
    if (strlen($string)) {
      if ($modifiers) {
        $prefix = '';
        $suffix = '';
        if (preg_match('/^(<span[^>]*>)(.*)(<\/span>)$/i', $string, $matches)) {
          $prefix = $matches[1];
          $string = $matches[2];
          $suffix = $matches[3];
        }

        for ($j = 0; $j < strlen($modifiers); $j++) {
          switch ($modifiers[$j]) {
            case 'L':
              $string = mb_strtolower($string);
              break;

            case 'U':
              $string = mb_strtoupper($string);
              break;

            case 'F':
              $string = Unicode::ucfirst($string);
              break;

            case 'G':
              $string = UniCode::ucwords($string);
              break;

            case 'T':
              $string = trim(preg_replace('/\s+/', ' ', $string));
              break;

            case 'S':
              $string = Html::escape($string);
              break;

            case 'B':
              $parts = preg_split($this->boundaryRegExp, $string);
              $string = (string) array_shift($parts);
              break;

            case 'b':
              $parts = preg_split($this->boundaryRegExp, $string);
              $string = (string) array_pop($parts);
              break;

          }
        }
        $string = $prefix . $string . $suffix;
      }
    }
    return $string;
  }

  /**
   * Helper function to put out the first matched bracket position.
   *
   * @param string $string
   *   Accepts strings in the format, ^ marks the matched bracket.
   *
   *   i.e. '(xxx^)xxx(xxxx)xxxx' or '(xxx(xxx(xxxx))xxx^)'.
   *
   * @return mixed
   *   The closing bracket position or FALSE if not found.
   */
  protected function closingBracketPosition($string) {
    // Simplify the string by removing escaped brackets.
    $depth = 0;
    $string = str_replace(['\(', '\)'], ['__', '__'], $string);
    for ($i = 0; $i < strlen($string); $i++) {
      $char = $string[$i];
      if ($char == '(') {
        $depth++;
      }
      elseif ($char == ')') {
        $depth--;
        if ($depth == 0) {
          return $i;
        }
      }
    }
    return FALSE;
  }

  /**
   * Generates the tokens from the name item.
   *
   * @param array $name_components
   *   The array of name components.
   *
   * @return array
   *   The keyed tokens generated for the given name.
   */
  protected function generateTokens(array $name_components) {
    $name_components = (array) $name_components;
    $name_components += [
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'credentials' => '',
      'generational' => '',
      'preferred' => '',
      'alternative' => '',
    ];
    $tokens = [
      't' => $this->renderComponent($name_components['title'], 'title'),
      'g' => $this->renderComponent($name_components['given'], 'given'),
      'p' => $this->renderFirstComponent([$name_components['preferred'], $name_components['given']], 'given'),
      'q' => $this->renderComponent($name_components['preferred'], 'preferred'),
      'm' => $this->renderComponent($name_components['middle'], 'middle'),
      'f' => $this->renderComponent($name_components['family'], 'family'),
      'c' => $this->renderComponent($name_components['credentials'], 'credentials'),
      'a' => $this->renderComponent($name_components['alternative'], 'alternative'),
      's' => $this->renderComponent($name_components['generational'], 'generational'),
      'v' => $this->renderComponent($name_components['preferred'], 'preferred', 'initial'),
      'w' => $this->renderFirstComponent([$name_components['preferred'], $name_components['given']], 'given', 'initial'),
      'x' => $this->renderComponent($name_components['given'], 'given', 'initial'),
      'y' => $this->renderComponent($name_components['middle'], 'middle', 'initial'),
      'z' => $this->renderComponent($name_components['family'], 'family', 'initial'),
      'A' => $this->renderComponent($name_components['alternative'], 'alternative', 'initial'),
      'I' => $this->renderComponent($name_components['given'] . ' ' . $name_components['family'], 'initials', 'initials'),
      'J' => $this->renderComponent($name_components['given'] . ' ' . $name_components['middle'] . ' ' . $name_components['family'], 'initials', 'initials'),
      'K' => $this->renderComponent($name_components['given'], 'initials', 'initials'),
      'M' => $this->renderComponent($name_components['given'] . ' ' . $name_components['middle'], 'initials', 'initials'),
      'i' => $this->sep1,
      'j' => $this->sep2,
      'k' => $this->sep3,
    ];
    $preferred = $tokens['p'];
    $given = $tokens['g'];
    $family = $tokens['f'];
    if ($preferred || $family) {
      $tokens += [
        'd' => $preferred ? $preferred : $family,
        'D' => $family ? $family : $preferred,
      ];
    }
    if ($given || $family) {
      $tokens += [
        'e' => $given ? $given : $family,
        'E' => $family ? $family : $given,
      ];
    }
    $tokens += [
      'd' => NULL,
      'D' => NULL,
      'e' => NULL,
      'E' => NULL,
    ];
    return $tokens;
  }

  /**
   * Finds and renders the first renderable name component value.
   *
   * This function does not by default sanitize the output unless the markup
   * flag is set. If this is set, it runs the component through check_plain()
   * and wraps the component in a span with the component name set as the class.
   *
   * @param array $values
   *   An array of walues to find the first to render.
   * @param string $component_key
   *   The component context.
   * @param string $modifier
   *   Internal flag for processing.
   *
   * @return string
   *   The rendered component.
   */
  protected function renderFirstComponent(array $values, $component_key, $modifier = NULL) {
    foreach ($values as $value) {
      $output = $this->renderComponent($value, $component_key, $modifier);
      if (isset($output) && strlen($output)) {
        return $output;
      }
    }

    return NULL;
  }

  /**
   * Renders a name component value.
   *
   * This function does not by default sanitize the output unless the markup
   * flag is set. If set, it runs the component through Html::escape() and
   * wraps the component in a span with the component name set as the class.
   *
   * @param string $value
   *   A value to render.
   * @param string $component_key
   *   The componenet context.
   * @param string $modifier
   *   Internal flag for processing.
   *
   * @return string
   *   The rendered componenet.
   */
  protected function renderComponent($value, $component_key, $modifier = NULL) {
    if (empty($value) || !mb_strlen($value)) {
      return NULL;
    }
    switch ($modifier) {
      // First letter first word.
      case 'initial':
        $value = mb_substr($value, 0, 1);
        break;

      // First letter all words.
      case 'initials':
        // First letter all words.
        $value = NameUnicodeExtras::initials($value);
        break;

    }

    // Based on http://schema.org/Person that doesn't cover generational suffix
    // or preferred names directly.
    $map = [
      'title' => 'honorificPrefix',
      'given' => 'givenName',
      'middle' => 'additionalName',
      'family' => 'familyName',
      'credential' => 'honorificSuffix',
      'alternative' => 'alternateName',
    ];

    switch ($this->markup) {
      case 'simple':
        return '<span class="' . Html::escape($component_key) . '">' . Html::escape($value) . '</span>';

      case 'microdata':
        return '<span class="' . Html::escape($component_key) . '"'
          . (isset($map[$component_key]) ? ' itemprop="' . $map[$component_key] . '"' : '')
          . '>' . Html::escape($value) . '</span>';

      case 'rdfa':
        return '<span class="' . Html::escape($component_key) . '"'
          . (isset($map[$component_key]) ? ' property="schema:' . $map[$component_key] . '"' : '')
          . '>' . Html::escape($value) . '</span>';

      case 'none':
      default:
        return $value;
    }
  }

  /**
   * Supported markup options.
   *
   * @return array
   *   A keyed array of markup options.
   */
  public function getMarkupOptions() {
    return [
      // Raw component values.
      'none' => $this->t('No markup'),
      // Unescaped text.
      'raw' => $this->t('Raw, unescaped text'),
      // Escaped component values.
      'simple' => $this->t('Component classes'),
      // Escaped component values.
      'microdata' => $this->t('Microdata itemprop components'),
      // Escaped component values.
      'rdfa' => $this->t('RDFa property components'),
    ];
  }

  /**
   * Supported tokens.
   *
   * @param bool $describe
   *   Appends the description of the letter to the description.
   *
   * @return string[]
   *   An array of strings keyed by the token.
   */
  public function tokenHelp($describe = TRUE) {
    $tokens = [
      't' => $this->t('Title.'),
      'p' => $this->t('Preferred name, use given name if not set.'),
      'q' => $this->t('Preferred name.'),
      'g' => $this->t('Given name.'),
      'm' => $this->t('Middle name(s).'),
      'f' => $this->t('Family name.'),
      'c' => $this->t('Credentials.'),
      's' => $this->t('Generational suffix.'),
      'a' => $this->t('Alternative value.'),
      'v' => $this->t('First letter preferred name.'),
      'w' => $this->t('First letter preferred or given name.'),
      'x' => $this->t('First letter given.'),
      'y' => $this->t('First letter middle.'),
      'z' => $this->t('First letter family.'),
      'A' => $this->t('First letter of alternative value.'),
      'I' => $this->t('Initials (all) from given and family.'),
      'J' => $this->t('Initials (all) from given, middle and family.'),
      'K' => $this->t('Initials (all) from given.'),
      'M' => $this->t('Initials (all) from given and middle.'),
      'd' => $this->t('Conditional: Either the preferred given or family name. Preferred name is given preference over given or family names.'),
      'D' => $this->t('Conditional: Either the preferred given or family name. Family name is given preference over preferred or given names.'),
      'e' => $this->t('Conditional: Either the given or family name. Given name is given preference.'),
      'E' => $this->t('Conditional: Either the given or family name. Family name is given preference.'),
      'i' => $this->t('Separator 1.'),
      'j' => $this->t('Separator 2.'),
      'k' => $this->t('Separator 3.'),
      '\\' => $this->t('You can prevent a character in the format string from being expanded by escaping it with a preceding backslash.'),
      'L' => $this->t('Modifier: Converts the next token to all lowercase.'),
      'U' => $this->t('Modifier: Converts the next token to all uppercase.'),
      'F' => $this->t('Modifier: Converts the first letter to uppercase.'),
      'G' => $this->t('Modifier: Converts the first letter of ALL words to uppercase.'),
      'T' => $this->t('Modifier: Trims whitespace around the next token.'),
      'S' => $this->t('Modifier: Ensures that the next token is safe for the display.'),
      'B' => $this->t('Modifier: Use the first word of the next token.'),
      'b' => $this->t('Modifier: Use the last word of the next token.'),
      '+' => $this->t('Conditional: Insert the token if both the surrounding tokens are not empty.'),
      '-' => $this->t('Conditional: Insert the token if the previous token is not empty.'),
      '~' => $this->t('Conditional: Insert the token if the previous token is empty.'),
      '=' => $this->t('Conditional: Insert the token if the next token is not empty.'),
      '^' => $this->t('Conditional: Insert the token if the next token is empty.'),
      '|' => $this->t('Conditional: Uses the previous token unless empty, otherwise it uses this token.'),
      '(' => $this->t('Group: Start of token grouping.'),
      ')' => $this->t('Group: End of token grouping.'),
    ];

    if ($describe) {
      foreach ($tokens as $letter => $description) {
        if (preg_match('/^[a-z]+$/', $letter)) {
          $tokens[$letter] = $this->t('@description<br><small>(lowercase @letter)</small>', [
            '@description' => $description,
            '@letter' => mb_strtoupper($letter),
          ]);
        }
        elseif (preg_match('/^[A-Z]+$/', $letter)) {
          $tokens[$letter] = $this->t('@description<br><small>(uppercase @letter)</small>', [
            '@description' => $description,
            '@letter' => mb_strtoupper($letter),
          ]);
        }
      }
    }

    return $tokens;
  }

  /**
   * Helper function to provide name format token help.
   *
   * @return array
   *   A renderable array of tokens in a details element..
   */
  public function renderableTokenHelp() {
    return [
      '#type' => 'details',
      '#title' => $this->t('Format string help'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#parents' => [],
      'format_parameters' => [
        '#theme' => 'name_format_parameter_help',
        '#tokens' => $this->tokenHelp(),
      ],
    ];
  }

}
