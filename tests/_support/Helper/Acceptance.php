<?php

declare(strict_types=1);

namespace Tests\Support\Helper;


// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \codeception\Module
{
    public function fillJoomlaForm(array $form_fields, array $form_data, \Tests\Support\AcceptanceTester $I){
        
        // Fill in the form fields
        foreach ($form_fields as $field => $type) {
            $type = $type['type'];
            if(!in_array($field, array_keys($form_data))){
                continue;
            }
            switch($type){
                case 'select':
                    $I->selectOption("select#jform_{$field}", $form_data[$field]);
                    break;
                default:
                    $I->fillField("input#jform_{$field}", $form_data[$field]);
            }
        }
    }

    public function validateJoomlaForm($form_id, array $form_fields, \Tests\Support\AcceptanceTester $I){
        $I->seeElement("form[name=adminForm]");
        $I->seeElement("form#{$form_id}");
         foreach($form_fields as $field=> $params) {
            $type = $params['type'];
            switch($type){
                case 'select':
                    $I->seeElement("select#jform_{$field}");
                    break;
                default:
                    $I->seeElement("input#jform_{$field}");
            }
        }
    }
    public function validateJoomlaFormErrors(array $form_fields, \Tests\Support\AcceptanceTester $I){
        
         foreach($form_fields as $field=> $params) {
            $type = $params['type'];
            $required_fields = $params['required_fields']??false;
           
            if($required_fields != true){
                continue;
            }

            switch($type){
                case 'select':
                    $I->see("One of the options must be selected", "#jform_{$field}-lbl");
                    $I->seeElement("select#jform_{$field}.invalid[aria-invalid=true]");
                    break;
                case 'modal':
                default:
                    $I->see("Please fill in this field", "#jform_{$field}-lbl");
                    $I->seeElement("input#jform_{$field}.invalid[aria-invalid=true]");
            }
        }
    }

    public function validateJoomlaItemActions(array $actions, \Tests\Support\AcceptanceTester $I){
        
         foreach($actions as $action) {
            $I->amGoingTo("See the {$action} action in the toolbar");
            $I->see($action, "#toolbar");
        }
    }

    public function waitForJoomlaHeading($heading, \Tests\Support\AcceptanceTester $I){
        $I->wait(1);
        $I->waitForText("Mothership: {$heading}", 30, "h1.page-title");
    }

    public function validateJoomlaViewAllTableHeaders(array $headers, \Tests\Support\AcceptanceTester $I){
        
         foreach($headers as $header=> $position) {
            $I->amGoingTo("See the {$header} header in the view all table");
            $I->see($header, "#j-main-container table thead tr th:nth-child({$position})");
        }
    }

    public function validateJoomlaViewAllTableRowData(int $rowid, array $row_data, \Tests\Support\AcceptanceTester $I){
        
         foreach($row_data as $field => $data) {
            $position = $data['position'];
            $value = $data['value'];
            $I->amGoingTo("See the {$value} data in the view all table row");
            $I->see("{$value}", "#j-main-container table tbody tr:nth-child({$rowid}) td:nth-child({$position})");
        }
    }

    public function validateJoomlaViewAllNumberRows(int $num_rows, \Tests\Support\AcceptanceTester $I){
        $I->seeNumberOfElements("#j-main-container table tbody tr", $num_rows);
    }
}
