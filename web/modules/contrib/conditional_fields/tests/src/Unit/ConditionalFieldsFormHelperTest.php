<?php

namespace Drupal\Tests\conditional_fields\Unit;

use Drupal\conditional_fields\ConditionalFieldsFormHelper;
use Drupal\conditional_fields\ConditionalFieldsHandlersManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\node\NodeForm;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test the ConditionalFieldsFormHelper class.
 *
 * @group conditional_fields
 */
class ConditionalFieldsFormHelperTest extends UnitTestCase {

  /**
   * Test addJavascriptEffects() when there are 0 effects.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::addJavascriptEffects
   */
  public function testAddJavascriptEffects0Effects() {
    // Create an instance of the class we are testing.
    $elementInfo = $this->createMock(ElementInfoManager::class);
    $cfHandlersManager = $this->createMock(ConditionalFieldsHandlersManager::class);
    $sutClass = new ConditionalFieldsFormHelper($elementInfo, $cfHandlersManager);

    // Set up fixtures.
    $sutClass->effects = [];
    $sutClass->form = [];

    // Run the method under test.
    $sutClass->addJavascriptEffects();

    // Assert that the data is modified in the expected way.
    $this->assertSame([
      '#attached' => ['library' => [0 => 'conditional_fields/conditional_fields']],
    ], $sutClass->form);
  }

  /**
   * Test addJavascriptEffects() when there are some effects.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::addJavascriptEffects
   */
  public function testAddJavascriptEffectsSomeEffects() {
    // Create an instance of the class we are testing.
    $elementInfo = $this->createMock(ElementInfoManager::class);
    $cfHandlersManager = $this->createMock(ConditionalFieldsHandlersManager::class);
    $sutClass = new ConditionalFieldsFormHelper($elementInfo, $cfHandlersManager);

    // Set up fixtures.
    $sutClass->effects = ['some_effect'];
    $sutClass->form = [];

    // Run the method under test.
    $sutClass->addJavascriptEffects();

    // Assert that the data is modified in the expected way.
    $this->assertSame([
      '#attached' => [
        'library' => [0 => 'conditional_fields/conditional_fields'],
        'drupalSettings' => ['conditionalFields' => ['effects' => ['some_effect']]],
      ],
    ], $sutClass->form);
  }

  /**
   * Test addValidationCallback().
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::addValidationCallback
   */
  public function testAddValidationCallback() {
    // Create an instance of the class we are testing.
    $elementInfo = $this->createMock(ElementInfoManager::class);
    $cfHandlersManager = $this->createMock(ConditionalFieldsHandlersManager::class);
    $sutClass = new ConditionalFieldsFormHelper($elementInfo, $cfHandlersManager);

    // Set up fixtures.
    $formState = $this->createMock(FormState::class);
    $formState->expects($this->exactly(1))
      ->method('setValue')
      ->with('conditional_fields_untriggered_dependents', []);
    $sutClass->form_state = $formState;
    $sutClass->form = [];

    // Run the method under test.
    $sutClass->addValidationCallback();

    // Assert that the data is modified in the expected way.
    $this->assertSame([
      '#validate' => [0 => [ConditionalFieldsFormHelper::class, 'formValidate']],
    ], $sutClass->form);
  }

  /**
   * Test afterBuild() when we do not detect conditional fields.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::afterBuild
   */
  public function testAfterBuild0Fields() {
    $form = [];
    $formState = $this->createMock(FormState::class);

    // Test that we don't process anything if we don't detect any conditional
    // fields.
    $sutClassNoCFields = $this->createPartialMock(ConditionalFieldsFormHelper::class, [
      'hasConditionalFields',
      'processDependentFields',
      'addJavascriptEffects',
      'addValidationCallback',
    ]);
    $sutClassNoCFields->expects($this->exactly(1))
      ->method('hasConditionalFields')
      ->willReturnOnConsecutiveCalls(FALSE);
    $sutClassNoCFields->expects($this->exactly(0))
      ->method('processDependentFields');
    $sutClassNoCFields->expects($this->exactly(0))
      ->method('addJavascriptEffects');
    $sutClassNoCFields->expects($this->exactly(0))
      ->method('addValidationCallback');

    $sutClassNoCFields->afterBuild($form, $formState);
  }

  /**
   * Test afterBuild() when we do detect conditional fields.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::afterBuild
   */
  public function testAfterBuildSomeFields() {
    $form = [];
    $formState = $this->createMock(FormState::class);

    // Test that we process dependent fields, add JavaScript effects, and add a
    // validation callback when we do detect some conditional fields.
    $sutClassSomeCFields = $this->createPartialMock(ConditionalFieldsFormHelper::class, [
      'hasConditionalFields',
      'processDependentFields',
      'addJavascriptEffects',
      'addValidationCallback',
    ]);
    $sutClassSomeCFields->expects($this->exactly(1))
      ->method('hasConditionalFields')
      ->willReturnOnConsecutiveCalls(TRUE);
    $sutClassSomeCFields->expects($this->exactly(1))
      ->method('processDependentFields')
      ->willReturnOnConsecutiveCalls($sutClassSomeCFields);
    $sutClassSomeCFields->expects($this->exactly(1))
      ->method('addJavascriptEffects')
      ->willReturnOnConsecutiveCalls($sutClassSomeCFields);
    $sutClassSomeCFields->expects($this->exactly(1))
      ->method('addValidationCallback')
      ->willReturnOnConsecutiveCalls($sutClassSomeCFields);

    // Run the method under test.
    $sutClassSomeCFields->afterBuild($form, $formState);
  }

  /**
   * Test hasConditionalFields() returns FALSE when there are none.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::hasConditionalFields
   */
  public function testHasConditionalFields0Fields() {
    // Create an instance of the class we are testing.
    $elementInfo = $this->createMock(ElementInfoManager::class);
    $cfHandlersManager = $this->createMock(ConditionalFieldsHandlersManager::class);
    $sutClass = new ConditionalFieldsFormHelper($elementInfo, $cfHandlersManager);

    // Set up fixtures.
    $sutClass->form = ['#conditional_fields' => []];
    $formState = $this->createMock(FormState::class);
    $formState->expects($this->exactly(0))
      ->method('getFormObject');
    $sutClass->form_state = $formState;

    // Run the method under test.
    $result = $sutClass->hasConditionalFields();

    // Assert that the function detects no conditional fields.
    $this->assertFalse($result);
  }

  /**
   * Test hasConditionalFields() returns FALSE when form object has no display.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::hasConditionalFields
   */
  public function testHasConditionalFieldsFormObjectNoDisplay() {
    // Create an instance of the class we are testing.
    $elementInfo = $this->createMock(ElementInfoManager::class);
    $cfHandlersManager = $this->createMock(ConditionalFieldsHandlersManager::class);
    $sutClass = new ConditionalFieldsFormHelper($elementInfo, $cfHandlersManager);

    // Set up fixtures.
    $sutClass->form = ['#conditional_fields' => ['test' => 'test']];
    $formState = $this->createMock(FormState::class);
    $formState->expects($this->exactly(1))
      ->method('getFormObject')
      ->willReturnOnConsecutiveCalls(new \stdClass());
    $sutClass->form_state = $formState;

    // Run the method under test.
    $result = $sutClass->hasConditionalFields();

    // Assert that the function detects no conditional fields.
    $this->assertFalse($result);
  }

  /**
   * Test hasConditionalFields() returns TRUE when it detects conditional field.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::hasConditionalFields
   */
  public function testHasConditionalFields1Field() {
    // Create an instance of the class we are testing.
    $elementInfo = $this->createMock(ElementInfoManager::class);
    $cfHandlersManager = $this->createMock(ConditionalFieldsHandlersManager::class);
    $sutClass = new ConditionalFieldsFormHelper($elementInfo, $cfHandlersManager);

    // Set up fixtures.
    $sutClass->form = ['#conditional_fields' => ['test' => 'test']];
    $formObject = $this->createMock(NodeForm::class);
    $formState = $this->createMock(FormState::class);
    $formState->expects($this->exactly(1))
      ->method('getFormObject')
      ->willReturnOnConsecutiveCalls($formObject);
    $sutClass->form_state = $formState;

    // Run the method under test.
    $result = $sutClass->hasConditionalFields();

    // Assert that the function detects conditional fields.
    $this->assertTrue($result);
  }

  /**
   * Test processDependentFields() function with 0 conditional fields.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::processDependentFields
   */
  public function testProcessDependentFields0Fields() {
    // Mock ConditionalFieldsFormHelper and set up expectations for it as if
    // there are 0 conditional fields.
    $sutClass = $this->createPartialMock(ConditionalFieldsFormHelper::class, [
      'processDependeeFields',
      'mapStates',
    ]);
    $sutClass->expects($this->exactly(0))
      ->method('processDependeeFields');
    $sutClass->expects($this->exactly(0))
      ->method('mapStates');

    // Set up the form array fixture.
    $conditionalFieldControl = [];
    $sutClass->form = [
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [0 => ['value' => ['#type' => 'textfield']]],
    ];

    // Run the method under test.
    $sutClass->processDependentFields();

    // Check that nothing has changed when there are zero conditional fields.
    $this->assertSame([
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [0 => ['value' => ['#type' => 'textfield']]],
    ], $sutClass->form);
  }

  /**
   * Test processDependentFields() function with 1 simple conditional field.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::processDependentFields
   */
  public function testProcessDependentFields1Simple() {
    // Mock ConditionalFieldsFormHelper and set up expectations for it as if
    // there is 1 conditional field.
    $sutClass = $this->createPartialMock(ConditionalFieldsFormHelper::class, [
      'processDependeeFields',
      'mapStates',
    ]);
    $sutClass->expects($this->exactly(1))
      ->method('processDependeeFields')
      ->willReturnOnConsecutiveCalls(
        ['raw_state']
      );
    $sutClass->expects($this->exactly(1))
      ->method('mapStates')
      ->willReturnMap([
        [['raw_state'], ['mapped_state']],
      ]);

    // Set up the form array fixture.
    $conditionalFieldControl = [
      'field_src1' => [
        'parents' => ['field_src1', 0, 'value'],
        'dependents' => [
          '4c6f4ff2-3113-4a75-81b6-164626cf694e' => [
            'dependent' => 'field_tgt1',
            'options' => [],
          ],
        ],
      ],
      'field_tgt1' => [
        'field_parents' => ['field_tgt1', 0, 'value'],
        'dependees' => [
          '4c6f4ff2-3113-4a75-81b6-164626cf694e' => [
            'dependee' => 'field_src1',
            'options' => [],
          ],
        ],
      ],
    ];
    $sutClass->form = [
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [
        0 => ['value' => ['#type' => 'textfield']],
      ],
    ];

    // Run the method under test.
    $sutClass->processDependentFields();

    // Check that #states has been added to the target field.
    $this->assertSame([
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [
        0 => ['value' => ['#type' => 'textfield']],
        '#states' => ['mapped_state'],
      ],
    ], $sutClass->form);
  }

  /**
   * Test processDependeeFields() function with 0 conditional fields.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::processDependeeFields
   */
  public function testProcessDependeeFields0Fields() {
    // Mock ConditionalFieldsFormHelper and set up expectations for it as if
    // there is 0 conditional field.
    $sutClass = $this->createPartialMock(ConditionalFieldsFormHelper::class, [
      'getSelector',
      'getState',
      'elementAddProperty',
      'addStateToGroup',
      'buildJquerySelectorForField',
      'getEffect',
    ]);
    $sutClass->expects($this->exactly(0))
      ->method('getSelector');
    $sutClass->expects($this->exactly(0))
      ->method('getState');
    $sutClass->expects($this->exactly(0))
      ->method('elementAddProperty');
    $sutClass->expects($this->exactly(0))
      ->method('addStateToGroup');
    $sutClass->expects($this->exactly(0))
      ->method('buildJquerySelectorForField');
    $sutClass->expects($this->exactly(0))
      ->method('getEffect');

    // Set up the form array fixture.
    $conditionalFieldControl = [];
    $sutClass->form = [
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [0 => ['value' => ['#type' => 'textfield']]],
    ];

    // Set up other fixtures.
    $sutClass->effects = [];
    $controllingFields = [];
    $targetField = $sutClass->form['field_tgt1'];
    $targetFieldLocation = [];
    $states = [];

    // Run the method under test.
    $newStates = $sutClass->processDependeeFields($controllingFields, $targetField, $targetFieldLocation, $states);

    // Check the output matches what we expect.
    $this->assertSame([
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [0 => ['value' => ['#type' => 'textfield']]],
    ], $sutClass->form);
    $this->assertSame([], $sutClass->effects);
    $this->assertSame([
      0 => ['value' => ['#type' => 'textfield']],
    ], $targetField);
    $this->assertSame([], $newStates);
  }

  /**
   * Test processDependeeFields() function with 1 simple conditional field.
   *
   * @covers \Drupal\conditional_fields\ConditionalFieldsFormHelper::processDependeeFields
   */
  public function testProcessDependeeFields1Simple() {
    // Mock ConditionalFieldsFormHelper and set up expectations for it as if
    // there is 1 conditional field.
    $sutClass = $this->createPartialMock(ConditionalFieldsFormHelper::class, [
      'getSelector',
      'getState',
      'elementAddProperty',
      'addStateToGroup',
      'buildJquerySelectorForField',
      'getEffect',
    ]);
    $sutClass->expects($this->exactly(1))
      ->method('getSelector')
      ->willReturnOnConsecutiveCalls('[name="field_src1[0][value]"]');
    $sutClass->expects($this->exactly(1))
      ->method('getState')
      ->willReturnOnConsecutiveCalls([
        '!visible' => ['[name="field_src1[0][value]"]' => ['value' => 'hideme']],
      ]);
    $sutClass->expects($this->exactly(1))
      ->method('elementAddProperty')
      ->willReturnOnConsecutiveCalls(
        [
          0 => ['value' => ['#type' => 'textfield']],
          '#element_validate' => [0 => [0 => ConditionalFieldsFormHelper::class, 'dependentValidate']],
        ]
    );
    $sutClass->expects($this->exactly(1))
      ->method('addStateToGroup')
      ->willReturnOnConsecutiveCalls(
        ['!visible' => ['AND' => ['[name="field_src1[0][value]"]' => ['value' => 'hideme']]]]
      );
    $sutClass->expects($this->exactly(1))
      ->method('buildJquerySelectorForField')
      ->willReturnOnConsecutiveCalls('#edit-field-tgt1-wrapper');
    $sutClass->expects($this->exactly(1))
      ->method('getEffect')
      ->willReturnOnConsecutiveCalls([]);

    // Set up the form array fixture.
    $conditionalFieldControl = [
      'field_src1' => [
        'parents' => ['field_src1', 0, 'value'],
        'dependents' => [
          '4c6f4ff2-3113-4a75-81b6-164626cf694e' => [
            'dependent' => 'field_tgt1',
            'options' => [],
          ],
        ],
      ],
      'field_tgt1' => [
        'field_parents' => ['field_tgt1', 0, 'value'],
        'dependees' => [
          '4c6f4ff2-3113-4a75-81b6-164626cf694e' => [
            'dependee' => 'field_src1',
            'options' => [
              'condition' => 'value',
              'selector' => '',
              'values' => 'hideme',
              'grouping' => 'AND',
            ],
          ],
        ],
      ],
    ];
    $sutClass->form = [
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [0 => ['value' => ['#type' => 'textfield']]],
    ];

    // Set up other fixtures.
    $sutClass->effects = [];
    $controllingFields = $sutClass->form['#conditional_fields']['field_tgt1']['dependees'];
    $targetField = $sutClass->form['field_tgt1'];
    $targetFieldLocation = $sutClass->form['#conditional_fields']['field_tgt1']['field_parents'];
    $states = [];

    // Run the method under test.
    $newStates = $sutClass->processDependeeFields($controllingFields, $targetField, $targetFieldLocation, $states);

    // Check the output matches what we expect.
    $this->assertSame([
      '#conditional_fields' => $conditionalFieldControl,
      'field_src1' => [0 => ['value' => ['#type' => 'textfield']]],
      'field_tgt1' => [0 => ['value' => ['#type' => 'textfield']]],
    ], $sutClass->form);
    $this->assertSame(['#edit-field-tgt1-wrapper' => []], $sutClass->effects);
    $this->assertSame([
      0 => ['value' => ['#type' => 'textfield']],
      '#element_validate' => [0 => [0 => ConditionalFieldsFormHelper::class, 'dependentValidate']],
    ], $targetField);
    $this->assertSame(['!visible' => ['AND' => ['[name="field_src1[0][value]"]' => ['value' => 'hideme']]]], $newStates);
  }

}
