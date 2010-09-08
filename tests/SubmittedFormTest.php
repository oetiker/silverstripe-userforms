<?php

class SubmittedFormTest extends FunctionalTest {

	static $fixture_file = 'userforms/tests/SubmittedFormTest.yml';
	
	protected $controller, $form, $page, $field;
	
	function setUp() {
		parent::setUp();
		$this->page = $this->objFromFixture('UserDefinedForm', 'popular-form');
		
		$this->controller = new SubmittedFormTest_Controller($this->page);
		$this->form = $this->controller->Form();
		$this->field = $this->form->dataFieldByName('Report');
	}
	
	function testSubmissions() {
		$submissions = $this->field->Submissions();
		
		// test with 11 submissions. Should be over 2 pages. 10 per page.
		// @todo add tests to ensure the order
		$this->assertEquals($submissions->Count(), 10);
		$this->assertEquals($submissions->TotalPages(), 2);
		$this->assertEquals($submissions->TotalItems(), 11);
	}
	
	function testGetSubmissionns() {
		$template = $this->field->getSubmissions();
		
		$parser = new CSSContentParser($template);
		
		// check to ensure that the pagination exists
		$pagination = $parser->getBySelector('.userforms-submissions-pagination');
	
		$this->assertEquals(str_replace("\n", ' ',(string) $pagination[0]->span), "Viewing rows 0 - 10 of 11 rows");
		$this->assertEquals(str_replace("\n", ' ',(string) $pagination[0]->a), "Next page");

		// ensure the actions exist
		$actions = $parser->getBySelector('.userforms-submission-actions');
		$this->assertEquals(count($actions[0]->li), 2);
		
		// submissions
		$submissions = $parser->getBySelector('.userform-submission');
		$this->assertEquals(count($submissions), 10);
	}

	function testCSVExport() {
		$export = $this->field->export($this->page->ID);
		
		// export it back to an array (rather than string)
		$exportLines = explode("\n", $export);
		$data = array();
		
		array_pop($exportLines);
		
		foreach($exportLines as $line) {
			$line = explode("\",\"", $line);
			
			$clean = array();
			foreach($line as $part) {
				$clean[] = trim($part, "\"");
			}
			
			$data[] = $clean;
		}
		
		// check the headers are fine
		$this->assertEquals($data[0], array(
			'Submitted Title','Submitted Title 2','Submitted'
		));
	
		// check the number of records in the export
		
		$this->assertEquals(count($data), 12);
		
		$this->assertEquals($data[1][0], 'Value 1');
		$this->assertEquals($data[1][1], "");
		
		$this->assertEquals($data[2][0], "");
		$this->assertEquals($data[2][1], 'Value 2');
	}
	
	function testdeletesubmission() {
		$submission = $this->objFromFixture('SubmittedForm', 'long-1');
		
		$count = $this->page->Submissions()->Count();
		$this->assertTrue($this->field->deletesubmission($submission->ID));
		
		$this->assertEquals($count - 1, $this->page->Submissions()->Count());
		
		$this->assertFalse($this->field->deletesubmission(-1));
	}
	
	function testdeletesubmissions() {
		$this->assertTrue($this->field->deletesubmissions($this->page->ID));
		
		$this->assertEquals($this->page->Submissions()->Count(), 0);
	}
	
	function testOnBeforeDeleteOfForm() {
		$field = $this->objFromFixture('SubmittedFormField', 'submitted-form-field-1');
		$form = $field->Parent();
		
		$this->assertEquals($form->Values()->Count(), 2);
		$form->delete();
		
		$fields = DataObject::get('SubmittedFormField', "ParentID = '$form->ID'");
		
		$this->assertNull($fields);
	}
	
	function testGetFormattedValue() {
		$field = $this->objFromFixture('SubmittedFormField', 'submitted-form-field-1');
		
		$this->assertEquals('1', $field->getFormattedValue());
		
		$textarea = $this->objFromFixture('SubmittedFormField', 'submitted-textarea-1');
		
		$text = "I am here testing<br />\nTesting until I cannot<br />\nI love my testing";
		
		$this->assertEquals($text, $textarea->getFormattedValue());
	}
	
	function testFileGetLink() {
		$field = $this->objFromFixture('SubmittedFileField', 'submitted-file-1');

		// @todo add checks for if no file can be downloaded
		$this->assertContains('my-file.jpg', $field->getLink());
		
	}
	function testFileGetFormattedValue() {
		$field = $this->objFromFixture('SubmittedFileField', 'submitted-file-1');

		// @todo add checks for if no file can be downloaded
		$this->assertContains('Download File', $field->getFormattedValue());
	}
}


class SubmittedFormTest_Controller extends ContentController {
	
	function Form() {
		$form = new Form($this, 'Form', new FieldSet(new SubmittedFormReportField('Report')), new FieldSet(new FormAction('Submit')));

		$form->loadDataFrom($this->data());
		
		return $form;
	}
	
	function forTemplate() {
		return $this->renderWith(array('ContentController'));
	}
}
