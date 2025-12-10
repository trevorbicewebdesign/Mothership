<?php
namespace Helper;

use \Faker;
use \Exception;
use \InvalidArgumentException;

use Codeception\Module\Db;
use DateTime;
use DateTimeZone;

class DbHelper extends Db
{
    private $Db;
    private $prefix;
    public function _initialize(): void
    {
        parent::_initialize();
        codecept_debug("DbHelper module initialized!");
    }

    public function _beforeSuite($settings = array()): void
    {
        // Initialization logic as before
        $this->Db = $this->getModule('Helper\DbHelper');
        $this->prefix = "jos_";
    }

    public function createMothershipClientData(array $data)
    {
        $faker = Faker\Factory::create();
        $defaultData = [
            "name" => isset($data['name']) ? $data['name'] : "Test Client",
            "email" => isset($data['email']) ? $data['email'] : $faker->email(),
            "phone" => isset($data['phone']) ? $data['phone'] : $faker->phoneNumber(),
            "address_1" => isset($data['address_1']) ? $data['address_1'] : $faker->streetAddress(),
            "address_2" => isset($data['address_2']) ? $data['address_2'] : $faker->secondaryAddress(),
            "city" => isset($data['city']) ? $data['city'] : $faker->city(),
            "state" => isset($data['state']) ? $data['state'] : $faker->state(),
            "zip" => isset($data['zip']) ? $data['zip'] : $faker->postcode(),
            "tax_id" => "",
            "default_rate" => $data['default_rate'] ?? 100.00,
            "owner_user_id" => $data['owner_user_id'] ?? 0,
            "invoice_pdf_template" => $data['invoice_pdf_template'] ?? 'default',
            "created" => date('Y-m-d H:i:s'),
        ];

        $finalData = array_merge($defaultData, $data);

        return $finalData;
    }

    public function createMothershipClient(array $data)
    {
        $data = $this->createMothershipClientData($data);

        codecept_debug("Creating Mothership Client: {$data['name']}");
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_clients", $data);
        $data['id'] = $id;
        codecept_debug($data);

        return $data;
    }
    public function createJoomlaUserData(array $data)
    {

        $faker = Faker\Factory::create();

        $fname = $faker->firstName();
        $lname = $faker->lastName();
        $email = $faker->email();
        $username = strtolower($fname);
        $password = '$2y$10$YczB41GuMXeBzD3fko00su9xBh.eK6WXdWR5r9w4d.eRk3INwC/A.';

        $params = [
            'admin_style' => '',
            'admin_language' => "en-GB",
            'language' => "en-GB",
            'editor' => '',
            'helpsite' => '',
            'timezone' => "America\/Los_Angeles",
        ];

        $params = json_encode($params);

        $defaultData = [
            'name' => "{$fname} {$lname}",
            'username' => "{$username}",
            'email' => "{$fname}.{$lname}@mailinator.com",
            'password' => $password,
            'params' => '',
            'registerDate' => date("Y-m-d H:i:s"),
            'lastVisitDate' => date("Y-m-d H:i:s"),
            'lastResetTime' => date("Y-m-d H:i:s"),
            'activation' => '0',
            'block' => '0',
            'sendEmail' => '1',
        ];

        $finalData = array_merge($defaultData, $data);

        return $finalData;

    }

    public function createJoomlaUser(array $data)
    {
        $data = $this->createJoomlaUserData($data);

        codecept_debug("Creating Joomla User: {$data['name']}");
        $id = $this->Db->haveInDatabase("{$this->prefix}users", $data);
        $this->Db->haveInDatabase("{$this->prefix}user_usergroup_map", ['user_id' => $id, "group_id" => 8]);

        $data['id'] = $id;
        codecept_debug($data);

        return $data;
    }
    public function createMothershipUserData(array $data)
    {
        $defaultData = [
            'user_id' => 0,
            'client_id' => 0,
        ];

        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipUser(array $data)
    {
        $data = $this->createMothershipUserData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership User with the following data:");
        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_users", $data);
        $data['id'] = $id;
        codecept_debug($data);

        // Return the ID of the newly created invoice
        return $data;
    }

    public function createMothershipAccountData(array $data)
    {
        $defaultData = [
            "name" => "Test Account",
            "client_id" => NULL,
            "rate" => 100.00,
        ];

        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipAccount(array $data)
    {
        $data = $this->createMothershipAccountData($data);
        codecept_debug("Creating Mothership Account: ");
        try {
            $id = $this->Db->haveInDatabase("{$this->prefix}mothership_accounts", $data);
            $data["id"] = $id;
        } catch (Exception $e) {
            codecept_debug("Error creating account: " . $e->getMessage());
        }

        codecept_debug($data);

        return $data;
    }

    public function createMothershipInvoiceData(array $data)
    {
        // Default values for the invoice
        $defaultData = [
            "number" => rand(1, 100),
            "client_id" => 1,
            "account_id" => 1,
            "project_id" => NULL,
            "status" => 1,
            "due_date" => date('Y-m-d H:i:s', strtotime('+30 days')),
            "created" => null,
            "total" => 100,
            "summary" => "This is a test invoice summary.",
            "notes" => "These are test invoice notes.",
            "checked_out_time" => null,
            "checked_out" => null
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    /**
     * Creates a new Mothership invoice in the database.
     *
     * This method takes an array of data, processes it to create the necessary
     * invoice data, and inserts it into the database. It returns the data of the
     * newly created invoice, including its ID.
     *
     * @param array $data The data to create the invoice with.
     * @return array The data of the newly created invoice, including its ID.
     */
    public function createMothershipInvoice(array $data): array
    {
        $data = $this->createMothershipInvoiceData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Invoice with the following data:");
        codecept_debug($data);
        // Insert into the database
        try {
            $id = $this->Db->haveInDatabase("{$this->prefix}mothership_invoices", $data);
            $data['id'] = $id;
        } catch (Exception $e) {
            throw new Exception("Failed to create invoice: " . $e->getMessage());
        }
        codecept_debug($data);
        // Return the ID of the newly created invoice
        return $data;
    }

    public function createMothershipInvoiceItemData(array $data)
    {
        $faker = Faker\Factory::create();
        $defaultData = [
            "invoice_id" => 0,
            "name" => "Test Item",
            "description" => "Test Description",
            "hours" => 1,
            "minutes" => 30,
            "quantity" => 1.5,
            "rate" => 100.00,
            "subtotal" => 100.00,
            "ordering" => 0,
        ];

        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipInvoiceItem(array $data)
    {
        $data = $this->createMothershipInvoiceItemData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Invoice Item with the following data:");
        codecept_debug($data);
        // Insert into the database
        try {
            $id = $this->Db->haveInDatabase("{$this->prefix}mothership_invoice_items", $data);
            $data['id'] = $id;
            $data['quantity'] = number_format($data['quantity'], 2);
            $data['rate'] = number_format($data['rate'], 2);
        } catch (Exception $e) {
            codecept_debug("Error creating invoice item: " . $e->getMessage());
        }
        codecept_debug($data);
        // Return the ID of the newly created invoice
        return $data;
    }

        /**
     * Build default data array for a Mothership proposal.
     *
     * @param  array $data
     * @return array
     */
    public function createMothershipProposalData(array $data): array
    {
        // Default values for the proposal
        $defaultData = [
            // Core fields
            "number"        => rand(1, 1000),
            "client_id"     => 1,
            "account_id"    => 1,
            "project_id"    => null,

            // Proposal-level type – matches the <field name="type"> in the form
            "type"          => 'hourly',

            // Money fields
            "total_low"     => 80.00,
            "total"         => 100.00,
            "rate"          => 100.00,

            // Status / dates
            "status"        => 1,
            // due_date is DATE in the schema, so use Y-m-d (not datetime)
            "due_date"      => date('Y-m-d', strtotime('+30 days')),
            "created"       => null,

            // Text content
            "summary"       => 'Test proposal summary',
            "notes"         => 'Test proposal notes',

            // Joomla housekeeping
            "locked"        => 0,
            "state"         => 1,
            "created_by"    => null,
            "modified"      => null,
            "modified_by"   => null,
            "checked_out_time" => null,
            "checked_out"   => null,
            "version"       => 1,
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);

        return $finalData;
    }

    /**
     * Creates a new Mothership proposal in the database.
     *
     * @param  array $data
     * @return array The data of the newly created proposal, including its ID.
     * @throws \Exception
     */
    public function createMothershipProposal(array $data): array
    {
        $data = $this->createMothershipProposalData($data);

        codecept_debug("Creating Mothership Proposal with the following data:");
        codecept_debug($data);

        try {
            $id = $this->Db->haveInDatabase("{$this->prefix}mothership_proposals", $data);
            $data['id'] = $id;
        } catch (\Exception $e) {
            throw new \Exception("Failed to create proposal: " . $e->getMessage());
        }

        codecept_debug($data);

        return $data;
    }

    /**
     * Build default data for a Mothership proposal item.
     *
     * @param  array $data
     * @return array
     */
    public function createMothershipProposalItemData(array $data): array
    {
        $faker = \Faker\Factory::create();

        $defaultData = [
            "proposal_id"   => 0,
            "name"          => "Test Proposal Item",
            "description"   => "Test Proposal Item Description",

            // Line item type: hourly vs fixed
            "type"          => "hourly",

            // Time fields: your JS uses HH:MM strings
            "time"          => "01:30",
            "time_low"      => "01:00",

            // Numeric values
            "quantity"      => 1.50,
            "quantity_low"  => 1.00,
            "rate"          => 100.00,
            "subtotal"      => 150.00,
            "subtotal_low"  => 100.00,

            "ordering"      => 0,
        ];

        $finalData = array_merge($defaultData, $data);

        return $finalData;
    }

    /**
     * Creates a new Mothership proposal item in the database.
     *
     * @param  array $data
     * @return array
     */
    public function createMothershipProposalItem(array $data): array
    {
        $data = $this->createMothershipProposalItemData($data);

        codecept_debug("Creating Mothership Proposal Item with the following data:");
        codecept_debug($data);

        try {
            $id = $this->Db->haveInDatabase("{$this->prefix}mothership_proposal_items", $data);
            $data['id'] = $id;

            // Normalize numeric formatting if your tests/UI rely on it
            $data['quantity']      = number_format($data['quantity'], 2);
            $data['quantity_low']  = number_format($data['quantity_low'], 2);
            $data['rate']          = number_format($data['rate'], 2);
            $data['subtotal']      = number_format($data['subtotal'], 2);
            $data['subtotal_low']  = number_format($data['subtotal_low'], 2);
        } catch (\Exception $e) {
            codecept_debug("Error creating proposal item: " . $e->getMessage());
        }

        codecept_debug($data);

        return $data;
    }


    public function createMothershipPaymentData(array $data)
    {
        $now = date('Y-m-d H:i:s');
        // Default values for the invoice
        $defaultData = [
            "client_id" => 0,
            "account_id" => 0,
            "amount" => 0,
            "payment_method" => 1,
            "fee_amount" => 0,
            "fee_passed_on" => 0,
            "transaction_id" => "",
            "status" => 0,
            "payment_date" => $now,
            "processed_date" => $now,
            "created_at" => $now,
            "updated_at" => $now,
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipPayment(array $data)
    {
        $data = $this->createMothershipPaymentData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Payment with the following data:");
        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_payments", $data);
        $data['id'] = $id;
        codecept_debug($data);

        // Return the ID of the newly created invoice
        return $data;

    }

    public function createMothershipProjectData(array $data)
    {
        $faker = Faker\Factory::create();

        $domain_name = $faker->domainName();
        $primary_url = "https://{$domain_name}";
        // Default values for the project

        $defaultData = [
            "client_id" => isset($data['name']) ? $data['name'] : NULL,
            "account_id" => isset($data['name']) ? $data['name'] : NULL,
            "name" => isset($data['name']) ? $data['name'] : 'Test Project',
            "description" => isset($data['description']) ? $data['description'] : 'Test Description',
            "type" => isset($data['type']) ? $data['type'] : 'Test Type',
            "status" => isset($data['status']) ? $data['status'] : 'active',
            "created" => date('Y-m-d H:i:s'),
            "created_by" => isset($data['created_by']) ? $data['created_by'] : 0,
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipProject(array $data)
    {
        $data = $this->createMothershipProjectData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Project with the following data:");
        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_projects", $data);
        $data['id'] = $id;
        codecept_debug($data);

        // Return the ID of the newly created invoice
        return $data;

    }

    public function createMothershipInvoicePaymentData(array $data)
    {

        $defaultData = [
            "payment_id" => 0,
            "invoice_id" => 0,
            "applied_amount" => 0,
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipInvoicePayment(array $data)
    {
        $data = $this->createMothershipInvoicePaymentData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Invoice Payment with the following data:");
        codecept_debug($data);

        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_invoice_payment", $data);
        $data['id'] = $id;

        // Return the ID of the newly created invoice
        return $data;
    }

    public function createMothershipDomainData(array $data)
    {

        $defaultData = [
            "name" => isset($data['name']) ? $data['name'] : 'example.com',
            "client_id" => isset($data['client_id']) ? $data['client_id'] : 0,
            "account_id" => isset($data['account_id']) ? $data['account_id'] : 0,
            'status' => isset($data['status']) ? $data['status'] : 1,
            'registrar' => isset($data['registrar']) ? $data['registrar'] : 'GoDaddy',
            'reseller' => isset($data['reseller']) ? $data['reseller'] : 'GoDaddy',
            'epp_status' => isset($data['epp_status']) ? json_encode($data['epp_status']) : json_encode(['clientUpdateProhibited']),
            'dns_provider' => isset($data['dns_provider']) ? $data['dns_provider'] : 'Cloudflare',
            'purchase_date' => isset($data['purchase_date']) ? $data['purchase_date'] : date('Y-m-d H:i:s'),
            'expiration_date' => isset($data['expiration_date']) ? $data['expiration_date'] : date('Y-m-d H:i:s', strtotime('+1 year')),
            'auto_renew' => isset($data['auto_renew']) ? $data['auto_renew'] : 1,
            'notes' => isset($data['notes']) ? $data['notes'] : '',
            'created' => isset($data['created']) ? $data['created'] : date('Y-m-d H:i:s'),
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipDomain(array $data)
    {
        $data = $this->createMothershipDomainData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Domain with the following data:");
        codecept_debug($data);

        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_domains", $data);
        $data['id'] = $id;

        // Return the ID of the newly created invoice
        return $data;
    }

    public function createMothershipLogData(array $data)
    {

        $defaultData = [
            "client_id" => $data['client_id'] ?? 0,
            "account_id" => $data['account_id'] ?? 0,
            "object_type" => $data['object_type'] ?? 'Test Object Type',
            "object_id" => $data['object_id'] ?? 0,
            "action" => $data['action'] ?? 'Test Action',
            "meta" => isset($data['meta']) ? json_encode($data['meta']) : json_encode(['default' => 'Test Meta']),
            "description" => $data['description'] ?? 'Test Description',
            "user_id" => $data['user_id'] ?? 0,
            "created" => date('Y-m-d H:i:s'),
            "notes" => $data['notes'] ?? '',
        ];
        

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }
    public function createMothershipLog(array $data)
    {
        $data = $this->createMothershipLogData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Log with the following data:");
        codecept_debug($data);

        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_logs", $data);
        $data['id'] = $id;

        // Return the ID of the newly created invoice
        return $data;
    }

    public function setInvoiceStatus($invoiceId, $status)
    {
        // Status levels are 1-5
        $statusArray = [
            1 => "Draft",
            2 => "Opened",
            3 => "Late",
            4 => "Paid",
            5 => "Cancelled"
        ];
        // Validate the status
        if ($status < 1 || $status > 5) {
            throw new Exception("Invalid status provided: {$statusArray[$status]}");
        }
        $this->Db->updateInDatabase("{$this->prefix}mothership_invoices", ['status' => $status], ['id' => $invoiceId]);
    }

    public function setProposalStatus($proposalId, $status)
    {
        // Status levels are 1-5
        $statusArray = [
            1 => "Draft",
            2 => "Opened",
            3 => "Late",
            4 => "Paid",
            5 => "Cancelled"
        ];
        // Validate the status
        if ($status < 1 || $status > 5) {
            throw new Exception("Invalid status provided: {$statusArray[$status]}");
        }
        $this->Db->updateInDatabase("{$this->prefix}mothership_proposals", ['status' => $status], ['id' => $proposalId]);
    }

    public function clearClientsTable()
    {
        codecept_debug("Clearing clients table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_clients", []);
    }

    public function clearAccountsTable()
    {
        codecept_debug("Clearing accounts table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_accounts", []);
    }

    public function clearUsersTable()
    {
        codecept_debug("Clearing users table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_users", []);
    }

    public function clearInvoicesTable()
    {
        codecept_debug("Clearing invoices table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_invoices", []);
    }

    public function clearProposalsTable()
    {
        codecept_debug("Clearing proposals table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_proposals", []);
    }

    public function clearInvoicePaymentTable()
    {
        codecept_debug("Clearing invoice payment table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_invoice_payment", []);
    }

    public function clearInvoiceItemsTable()
    {
        codecept_debug("Clearing invoice items table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_invoice_items", []);
    }

    public function clearProposalItemsTable()
    {
        codecept_debug("Clearing proposal items table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_proposal_items", []);
    }

    public function clearPaymentsTable()
    {
        codecept_debug("Clearing payments table");
        $this->Db->_getDriver()->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_payments", []);
    }

    public function resetMothershipTables()
    {
        codecept_debug("Resetting Mothership tables");

         // Turn off foreign key checks
         $this->Db->_getDriver()->executeQuery("SET FOREIGN_KEY_CHECKS = 0", []);

         // Truncate everything
         $this->clearInvoiceItemsTable();
         $this->clearInvoicesTable();
         $this->clearProposalsTable();
         $this->clearProposalItemsTable();
         $this->clearPaymentsTable();
         $this->clearInvoicePaymentTable();
         $this->clearAccountsTable();
         $this->clearClientsTable();
         $this->clearUsersTable();
 
         // Turn it back on
         $this->Db->_getDriver()->executeQuery("SET FOREIGN_KEY_CHECKS = 1", []);
    }


    public function setMothershipConfig(array $settings)
    {
        // Grab the existing JSON-encoded params from the DB and decode into an array
        $config_params = (array) json_decode(
            $this->Db->grabFromDatabase("jos_extensions", "params", ["name" => "com_mothership"])
        );
        codecept_debug($config_params);

        $mothershipConfigKeys = [
            'company_name',
            'company_address_1',
            'company_city',
            'company_state',
            'company_zip',
            'company_phone',
            'company_default_rate',
            'testmode',
            'company_email',
            'company_address_2',
            'company_mobil',
            'date_format',
            'email_from',
            'email_reply',
            'storage_path',
            'notification_days',
            'late_invoice_notifications',
            'warning_invoice_notifications',
            'warning_days',
            'twilio_account_sid',
            'twilio_auth_token',
            'paypal_api_username',
            'paypal_api_password',
            'paypal_api_signature',
            'paypal_application_id',
            'paypal_rest_client_id',
            'paypal_rest_client_secret',
        ];

        // Notice: We no longer do array_keys($mothershipConfigKeys).
        foreach ($settings as $setting => $value) {
            if (in_array($setting, $mothershipConfigKeys)) {
                // Optional: Fix the spelling of "Updating"
                if (isset($config_params[$setting]) && $config_params[$setting] != $value) {
                    codecept_debug("Updating {$setting} from {$config_params[$setting]} to {$value}");
                } else {
                    codecept_debug("Setting {$setting} to {$value}");
                }
                $config_params[$setting] = $value;
            }
        }

        // Save the new params as JSON
        $this->Db->updateInDatabase(
            "jos_extensions",
            ["params" => json_encode($config_params)],
            ["name" => "com_mothership"]
        );

        return $config_params;
    }

    public function assertInvoiceHasRows($invoiceId, $expectedRows)
    {
        $actualRows = $this->Db->grabNumRecords("{$this->prefix}mothership_invoice_items", ["invoice_id" => $invoiceId]);

        codecept_debug("Invoice {$invoiceId} has {$actualRows} rows");
        $this->assertEquals($expectedRows, $actualRows);
    }

    /**
     * Asserts that the status of an invoice matches the expected status.
     *
     * @param int $invoiceId The ID of the invoice to check.
     * @param string $expectedStatusLabel The expected status of the invoice (e.g., "Draft", "Opened", "Late", "Paid").
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException If the actual status does not match the expected status.
     */
    public function assertInvoiceStatus(int $invoiceId, string $expectedStatusLabel)
    {
        // Map of status codes to their labels
        
        $statusLabels = [
            1 => "Draft",
            2 => "Opened",
            3 => "Late",
            4 => "Paid",
        ];

        // Flip the statusLabels array to map strings to their corresponding numeric values
        $labelToStatus = array_flip($statusLabels);

        // Ensure the provided status label is valid
        if (!isset($labelToStatus[$expectedStatusLabel])) {
            throw new InvalidArgumentException("Invalid status label: '{$expectedStatusLabel}'. Valid labels are: " . implode(', ', $statusLabels));
        }

        // Fetch the actual status code from the database
        $actualStatusCode = $this->Db->grabFromDatabase("jos_mothership_invoices", "status", ["id" => $invoiceId]);

        // Map the actual status code to its label
        $actualStatusLabel = $statusLabels[$actualStatusCode] ?? "Unknown";

        // Debug output for easier troubleshooting
        codecept_debug("Invoice status (actual): {$actualStatusLabel} ({$actualStatusCode}), expected: {$expectedStatusLabel} ({$labelToStatus[$expectedStatusLabel]}).");

        // Assert that the actual status matches the expected status
        $this->assertEquals(
            $expectedStatusLabel,
            $actualStatusLabel,
            "Failed asserting that invoice with ID {$invoiceId} has status '{$expectedStatusLabel}'. Actual status is '{$actualStatusLabel}'."
        );
    }

    public function assertInvoiceStatusDraft($invoiceId)
    {
        $this->assertInvoiceStatus($invoiceId, 'Draft');
    }

    public function assertInvoiceStatusOpened($invoiceId)
    {
        $this->assertInvoiceStatus($invoiceId, 'Opened');
    }

    public function assertInvoiceStatusClosed($invoiceId)
    {
        $this->assertInvoiceStatus($invoiceId, 'Closed');
    }

    public function assertPaymentStatus(int $paymentId, string $expectedStatusLabel)
    {
        // Map of status codes to their labels
        $statusLabels = [
            1 => "Pending",
            2 => "Completed",
            3 => "Failed",
            4 => "Cancelled",
            5 => "Refunded",
        ];

        // Flip the statusLabels array to map strings to their corresponding numeric values
        $labelToStatus = array_flip($statusLabels);

        // Ensure the provided status label is valid
        if (!isset($labelToStatus[$expectedStatusLabel])) {
            throw new InvalidArgumentException("Invalid status label: '{$expectedStatusLabel}'. Valid labels are: " . implode(', ', $statusLabels));
        }

        // Fetch the actual status code from the database
        $actualStatusCode = $this->Db->grabFromDatabase("jos_mothership_payments", "status", ["id" => $paymentId]);

        // Map the actual status code to its label
        $actualStatusLabel = $statusLabels[$actualStatusCode] ?? "Unknown";

        // Debug output for easier troubleshooting
        codecept_debug("Payment status (actual): {$actualStatusLabel} ({$actualStatusCode}), expected: {$expectedStatusLabel} ({$labelToStatus[$expectedStatusLabel]}).");

        // Assert that the actual status matches the expected status
        $this->assertEquals(
            $expectedStatusLabel,
            $actualStatusLabel,
            "Failed asserting that payment with ID {$paymentId} has status '{$expectedStatusLabel}'. Actual status is '{$actualStatusLabel}'."
        );
    }

    public function assertPaymentStatusDraft($paymentId)
    {
        $this->assertPaymentStatus($paymentId, 'Draft');
    }

    public function assertPaymentStatusOpened($paymentId)
    {
        $this->assertPaymentStatus($paymentId, 'Opened');
    }

    public function assertPaymentStatusLate($paymentId)
    {
        $this->assertPaymentStatus($paymentId, 'Late');
    }
    public function assertPaymentStatusCompleted($paymentId)
    {
        $this->assertPaymentStatus($paymentId, 'Completed');
    }

    public function grabInvoiceRow($invoiceId, $rowNumber)
    {
        // Fetch the row from the mothership_invoice_items table
        $itemData = $this->Db->grabFromDatabase("{$this->prefix}mothership_invoice_items", "*", ["invoice_id" => $invoiceId], $rowNumber);

        // Return the requested row's data (or null if out of range)
        return $itemData;
    }

    public function grabDomainFromDatabase($domainId)
    {
        $fields = [
            'id',
            'name',
            'client_id',
            'account_id',
            'status',
            'epp_status',
            'registrar',
            'reseller',
            'dns_provider',
            'ns1',
            'ns2',
            'ns3',
            'ns4',
            'purchase_date',
            'expiration_date',
            'notes',
            'created',
            'modified'
        ];

        $domainData = [];
        foreach ($fields as $field) {
            if($field == 'epp_status'){
                $domainData[$field] = json_decode($this->Db->grabFromDatabase("{$this->prefix}mothership_domains", $field, ["id" => $domainId]), true);
            } else {            
                $domainData[$field] = $this->Db->grabFromDatabase("{$this->prefix}mothership_domains", $field, ["id" => $domainId]);
            }
        }

        return $domainData;
    }

    public function grabProjectFromDatabase($projectId)
    {
        $fields = [
            'id',
            'name',
            'description',
            'client_id',
            'account_id',
            'type',
            'status',
            'metadata',
            'created',
            'created_by',
            'checked_out_time',
            'checked_out',
        ];

        $projectData = [];
        foreach ($fields as $field) {
            if($field == 'metadata') {
                $projectData[$field] = json_decode($this->Db->grabFromDatabase("{$this->prefix}mothership_projects", $field, ["id" => $projectId]), true);
            } else {
                $projectData[$field] = $this->Db->grabFromDatabase("{$this->prefix}mothership_projects", $field, ["id" => $projectId]);
            }
        }

        return $projectData;
    }

    public function getClientIdByName($clientName)
    {
        $clientId = $this->Db->grabFromDatabase("{$this->prefix}mothership_clients", "id", ["name" => $clientName]);
        codecept_debug("Client {$clientName} has ID {$clientId}");
        return $clientId;
    }

    public function assertInvoiceClientId($invoiceId, $expectedClientId)
    {
        $clientId = $this->Db->grabFromDatabase("{$this->prefix}mothership_invoices", "client_id", ["id" => $invoiceId]);
        codecept_debug("Invoice {$invoiceId} has client ID {$clientId}");
        $this->assertEquals($expectedClientId, $clientId);
    }

    public function grabLastCompletedPaymentId()
    {
        $payment_id = $this->Db->grabFromDatabase("{$this->prefix}mothership_payments", "id", ['status'=>2]);
        codecept_debug("Last payment ID is {$payment_id}");
        return $payment_id;
    }


    /*
    $I->assertInvoiceHasItems($this->invoiceData['id'] + 1, [
        ['id' => '1', 'name' => 'Test Item', 'description' => 'Test Description', 'hours' => '1', 'minutes' => '30', 'quantity' => '1.5', 'rate' => '70.00', 'price' => '140.00'],
        ['id' => '2', 'name' => 'A different Item', 'description' => 'Test Description', 'hours' => '2', 'minutes' => '45', 'quantity' => '2.75', 'rate' => '70.00', 'price' => '192.50'],
    ]);
    */
    public function assertInvoiceHasItems(int $invoiceId, array $expectedItems)
    {
        // Fetch the actual items from the mothership_invoice_items table
        $columns = ['name', 'description', 'hours', 'minutes', 'quantity', 'rate', 'subtotal'];
        $actualItems = [];
        foreach ($columns as $column) {
            $actualItems[$column] = $this->Db->grabColumnFromDatabase("{$this->prefix}mothership_invoice_items", $column, ["invoice_id" => $invoiceId]);
        }
        // Need to put the data into the expected format
        codecept_debug($actualItems);
 
        $newItems = [];
        // Reformat $actualyItems into rows and columns
        foreach($actualItems as $column => $values) {
            foreach($values as $index => $value) {
                $newItems[$index][$column] = $value;
            }
        }
        $actualItems = $newItems;

        // Debug output for visibility
        codecept_debug("Actual items for invoice #{$invoiceId}:");
        codecept_debug($actualItems);

        // Assert that the count of items in the DB matches the count of expected items
        $this->assertCount(
            count($expectedItems),
            $actualItems,
            "Invoice #{$invoiceId} has a different number of items than expected."
        );

        // Assert that each item matches the expected item
        foreach ($expectedItems as $index => $expectedItem) {
            $this->assertEquals(
                $expectedItem,
                $actualItems[$index],
                "Mismatch in item #{$index} for Invoice #{$invoiceId}."
            );
        }
    }

    public function calculatePaypalFee($amount)
    {
        $flat_fee = 0.49;
        $percent_fee = 3.49 / 100;
        $fee_total = $flat_fee;
        $fee_total += ($amount * $percent_fee);
        $fee_total = round($fee_total, 2);
        return $fee_total;
    }

    public function totalWithPaypalFee($amount)
    {
        $fee = $this->calculatePaypalFee($amount);
        $total = $amount + $fee;
        return number_format($total, 2, '.', '');
    }

    public function setPaymentStatus($paymentId, $status)
    {
        $this->Db->updateInDatabase("{$this->prefix}mothership_payments", ['status' => $status], ['id' => $paymentId]);
    }

    public function setPaymentLocked($paymentId)
    {
        $this->Db->updateInDatabase("{$this->prefix}mothership_payments", ['locked' => 1], ['id' => $paymentId]);
    }

    public function setPaymentUnlocked($paymentId)
    {
        $this->Db->updateInDatabase("{$this->prefix}mothership_payments", ['locked' => 0], ['id' => $paymentId]);
    }

    public function setInvoiceLocked($invoiceId)
    {
        $this->Db->updateInDatabase("{$this->prefix}mothership_invoices", ['locked' => 1], ['id' => $invoiceId]);
    }

    public function setInvoiceUnlocked($invoiceId)
    {
        $this->Db->updateInDatabase("{$this->prefix}mothership_invoices", ['locked' => 0], ['id' => $invoiceId]);
    }

}
