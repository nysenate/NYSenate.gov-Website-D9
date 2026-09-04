<?php

declare(strict_types=1);

namespace Drupal\nys_bill_classifier\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\node\NodeInterface;
use Drupal\nys_bills\BillsHelper;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Classifies bill nodes against the global_issues taxonomy via OpenAI.
 *
 * Builds a system prompt from the live global_issues vocabulary (name +
 * description, both editorially maintained on the terms themselves) so the
 * classifier vocabulary always matches whatever the vocabulary currently
 * is, without a code change - see bill-classifier-prompt.md at the repo
 * root for the prompt design this implements.
 */
class BillClassifierService {

  /**
   * The Key entity id storing the OpenAI API key (file provider).
   */
  const KEY_ID = 'openai_api';

  /**
   * Fallback model, used if nys_bill_classifier.settings has none set.
   */
  const DEFAULT_MODEL = 'gpt-5.6-luna';

  const API_URL = 'https://api.openai.com/v1/chat/completions';

  const MAX_ISSUES = 3;

  /**
   * Vocabulary terms, keyed by name: ['description' => ..., 'tid' => ...].
   *
   * Populated once per request by getVocabulary().
   *
   * @var array<string, array{tid: int, description: string}>|null
   */
  protected ?array $vocabulary = NULL;

  /**
   * Constructs the BillClassifierService object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   *   The key repository.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\nys_bills\BillsHelper $billsHelper
   *   The bills helper, used to format a bill's print number.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The nys_bill_classifier logger channel.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected KeyRepositoryInterface $keyRepository,
    protected Connection $database,
    protected BillsHelper $billsHelper,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Classifies a single bill against the global_issues vocabulary.
   *
   * Makes a synchronous OpenAI API call. Intended for the Phase 1 test
   * sample and for per-bill cron classification - the full historical
   * backfill should use OpenAI's Batch API instead, not this method in a
   * loop, both to get the batch discount and to avoid rate limits.
   *
   * @param \Drupal\node\NodeInterface $bill
   *   The bill node to classify.
   *
   * @return array{issues: string[], primary_issue: ?string, confidence: string, rationale: string}
   *   The decoded classification result.
   *
   * @throws \RuntimeException
   *   If the API key is missing, the request fails, or the response is
   *   not shaped as expected.
   */
  public function classifyBill(NodeInterface $bill): array {
    $apiKey = $this->keyRepository->getKey(self::KEY_ID)?->getKeyValue();
    if (!$apiKey) {
      throw new \RuntimeException('OpenAI API key "' . self::KEY_ID . '" is not configured.');
    }

    // No 'temperature' key: gpt-5.6-luna rejects any value other than its
    // default (1) with a 400, so determinism isn't available on this model
    // - accept run-to-run variation rather than fixed temperature.
    $payload = [
      'model' => $this->configFactory->get('nys_bill_classifier.settings')->get('model') ?: self::DEFAULT_MODEL,
      'messages' => [
        ['role' => 'system', 'content' => $this->buildSystemPrompt()],
        ['role' => 'user', 'content' => $this->buildUserMessage($bill)],
      ],
      'response_format' => $this->buildResponseFormat(),
    ];

    try {
      $response = $this->httpClient->request('POST', self::API_URL, [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 60,
      ]);
    }
    catch (GuzzleException $e) {
      $body = $e instanceof RequestException && $e->getResponse()
        ? (string) $e->getResponse()->getBody()
        : $e->getMessage();
      $this->logger->error('OpenAI classification request failed for bill @nid: @body', [
        '@nid' => $bill->id(),
        '@body' => $body,
      ]);
      throw new \RuntimeException("OpenAI request failed: $body", 0, $e);
    }

    $data = json_decode((string) $response->getBody(), TRUE);
    $content = $data['choices'][0]['message']['content'] ?? NULL;
    $result = $content ? json_decode($content, TRUE) : NULL;

    if (!is_array($result) || !isset($result['issues'], $result['confidence'], $result['rationale'])) {
      throw new \RuntimeException('Unexpected OpenAI response shape for bill ' . $bill->id() . ': ' . (string) $response->getBody());
    }

    return $result;
  }

  /**
   * Finds the sponsor's stated position quote for a bill, if one exists.
   *
   * Featured-legislation paragraphs (both the general and committee
   * variants) pair a bill reference with an optional sponsor quote via
   * field_featured_bill / field_featured_quote on the same paragraph
   * entity - most bills have no such paragraph at all, so this returns
   * NULL far more often than not.
   *
   * @param int $nid
   *   The bill node id.
   *
   * @return string|null
   *   The quote text, or NULL if this bill has none.
   */
  public function getPositionQuote(int $nid): ?string {
    $quote = $this->database->select('paragraph__field_featured_bill', 'fb')
      ->fields('fq', ['field_featured_quote_value'])
      ->condition('fb.field_featured_bill_target_id', $nid)
      ->condition('fb.deleted', 0)
      ->range(0, 1);
    $quote->innerJoin('paragraph__field_featured_quote', 'fq', 'fq.entity_id = fb.entity_id AND fq.bundle = fb.bundle');

    $value = $quote->execute()->fetchField();
    return $value !== FALSE && $value !== '' ? (string) $value : NULL;
  }

  /**
   * Builds the OpenAI Structured Outputs response_format block.
   *
   * @return array
   *   The response_format array, keyed as OpenAI expects it.
   */
  protected function buildResponseFormat(): array {
    return [
      'type' => 'json_schema',
      'json_schema' => [
        'name' => 'classify_bill',
        'strict' => TRUE,
        'schema' => [
          'type' => 'object',
          'properties' => [
            'issues' => [
              'type' => 'array',
              'items' => [
                'type' => 'string',
                'enum' => array_keys($this->getVocabulary()),
              ],
              'minItems' => 0,
              'maxItems' => self::MAX_ISSUES,
              'description' => '1-3 canonical issue terms this bill is substantively about. Empty array if no term is a confident, substantive match.',
            ],
            'primary_issue' => [
              'type' => ['string', 'null'],
              'description' => 'The single most central issue from the issues array, or null if issues is empty. Must be one of the values already in issues.',
            ],
            'confidence' => [
              'type' => 'string',
              'enum' => ['high', 'medium', 'low'],
              'description' => 'Confidence in this classification overall.',
            ],
            'rationale' => [
              'type' => 'string',
              'description' => 'One sentence: why these issues, in plain language. Used for QA spot-checks only, not stored long-term.',
            ],
          ],
          'required' => ['issues', 'primary_issue', 'confidence', 'rationale'],
          'additionalProperties' => FALSE,
        ],
      ],
    ];
  }

  /**
   * Builds the system prompt, including the live canonical vocabulary.
   *
   * @return string
   *   The system prompt.
   */
  protected function buildSystemPrompt(): string {
    $vocabularyLines = [];
    foreach ($this->getVocabulary() as $name => $term) {
      $vocabularyLines[] = "[$name] {$term['description']}";
    }

    return <<<PROMPT
You are classifying New York State Senate bills against a controlled vocabulary of canonical
policy issue terms, for use on NYSenate.gov's public issue pages.

Each bill should receive 0-3 issue tags - only the issues the bill is substantively about, not
every issue it tangentially touches. A bill that mentions "utility rate impacts" in passing while
primarily regulating data center permitting should be tagged Energy and Data Centers, not also
Utilities, Consumer Protection, Environment, and Technology Policy. Precision matters more than
recall: a constituent using these tags to find bills on an issue page should see bills that are
actually about that issue, not bills where it's mentioned once.

CANONICAL VOCABULARY:
{$this->formatVocabularyBlock($vocabularyLines)}

RULES:
1. Tag only what the bill is substantively about. A bill amending court procedure that happens to
   involve a criminal case is Judiciary, not automatically Criminal Justice Reform, unless the
   substance of the amendment is about criminal justice policy.
2. Entity terms (MTA, Utilities) apply when a bill specifically targets that entity or its
   regulatory structure - not every bill that touches transportation or energy broadly. A general
   clean-energy tax credit bill is Renewable Energy / Energy, not MTA or Utilities unless it
   specifically concerns those entities.
3. Budget is for bills that are substantively about appropriations, revenue, or the state fiscal
   plan - not any bill that happens to cost money to implement.
4. Many bills are purely technical or local (correcting a statutory cross-reference, authorizing a
   single municipality to do something routine, renaming a bridge). These should get an empty
   issues array. Do not force a classification onto a bill just to give it a tag.
5. If a bill's title and summary are genuinely ambiguous or too sparse to classify with at least
   medium confidence, prefer fewer tags (or none) over guessing.

You will receive a bill's number, name, summary, sponsor, and committee. The name and summary are
both short official descriptions and often overlap, but occasionally one includes a detail the
other omits - read both. Some bills will also include a senator position quote (written when the
sponsor features the bill on their microsite) - treat this as a strong disambiguation signal when
present, since it's the sponsor's own framing of what the bill is about.
PROMPT;
  }

  /**
   * Joins vocabulary description lines into the prompt's vocabulary block.
   *
   * @param string[] $lines
   *   One "[Term] description" line per canonical issue.
   *
   * @return string
   *   The joined block.
   */
  protected function formatVocabularyBlock(array $lines): string {
    return implode("\n", $lines);
  }

  /**
   * Builds the user message describing one bill for classification.
   *
   * @param \Drupal\node\NodeInterface $bill
   *   The bill node.
   *
   * @return string
   *   The user message.
   */
  protected function buildUserMessage(NodeInterface $bill): string {
    $billNumber = $this->billsHelper->formatTitle($bill);
    $chamber = $bill->hasField('field_ol_chamber') ? (string) $bill->get('field_ol_chamber')->value : '';
    $name = $bill->hasField('field_ol_name') ? (string) $bill->get('field_ol_name')->value : '';
    $summary = $bill->hasField('field_ol_summary') ? (string) $bill->get('field_ol_summary')->value : '';
    $committee = $bill->hasField('field_ol_latest_status_committee')
      ? (string) $bill->get('field_ol_latest_status_committee')->value
      : '';
    $sponsor = $this->getSponsorName($bill);
    $quote = $this->getPositionQuote((int) $bill->id());

    // field_ol_name and field_ol_summary are both short LBDC-sourced
    // descriptions and often overlap, but not always - e.g. one bill's
    // summary talked only about "reporting unmet need" for an unnamed
    // program, while its name specified "programs and services for the
    // aging". Sending both costs a little context, but summary alone can
    // omit the one word that actually disambiguates the bill.
    $lines = [
      "Bill: $billNumber (" . ucfirst($chamber) . ')',
      "Name: $name",
      "Summary: $summary",
      "Sponsor: $sponsor",
      "Committee: $committee",
    ];
    if ($quote) {
      $lines[] = "Sponsor's stated position: \"$quote\"";
    }
    $lines[] = '';
    $lines[] = 'Classify this bill.';

    return implode("\n", $lines);
  }

  /**
   * Resolves a bill's sponsor name from field_ol_sponsor.
   *
   * @param \Drupal\node\NodeInterface $bill
   *   The bill node.
   *
   * @return string
   *   The sponsor's name, or an empty string if none is set.
   */
  protected function getSponsorName(NodeInterface $bill): string {
    if (!$bill->hasField('field_ol_sponsor') || $bill->get('field_ol_sponsor')->isEmpty()) {
      return '';
    }
    $term = $bill->get('field_ol_sponsor')->entity;
    return $term ? (string) $term->label() : '';
  }

  /**
   * Loads and caches the global_issues vocabulary as name => details.
   *
   * @return array<string, array{tid: int, description: string}>
   *   Term name keyed vocabulary.
   */
  public function getVocabulary(): array {
    if ($this->vocabulary !== NULL) {
      return $this->vocabulary;
    }

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $ids = $storage->getQuery()
      ->condition('vid', 'global_issues')
      ->accessCheck(FALSE)
      ->execute();

    $vocabulary = [];
    foreach ($storage->loadMultiple($ids) as $term) {
      $vocabulary[$term->label()] = [
        'tid' => (int) $term->id(),
        'description' => (string) $term->get('description')->value,
      ];
    }
    ksort($vocabulary);

    return $this->vocabulary = $vocabulary;
  }

  /**
   * Resolves classified issue names to global_issues term ids.
   *
   * @param string[] $issueNames
   *   Issue names as returned in a classification result's "issues" array.
   *
   * @return int[]
   *   The matching term ids. Names with no vocabulary match are skipped -
   *   shouldn't happen given the enum-constrained response schema, but
   *   isn't treated as an error if it does.
   */
  public function resolveIssueTids(array $issueNames): array {
    $vocabulary = $this->getVocabulary();
    $tids = [];
    foreach ($issueNames as $name) {
      if (isset($vocabulary[$name])) {
        $tids[] = $vocabulary[$name]['tid'];
      }
    }
    return $tids;
  }

}
