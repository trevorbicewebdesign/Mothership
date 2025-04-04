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

    public function customMethodExample()
    {
        codecept_debug("Custom method called!");
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
            "status" => 1,
            "due_date" => date('Y-m-d H:i:s', strtotime('+30 days')),
            "created" => null,
            "total" => 100,
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
            codecept_debug("Error creating invoice: " . $e->getMessage());
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
        } catch (Exception $e) {
            codecept_debug("Error creating invoice item: " . $e->getMessage());
        }
        codecept_debug($data);
        // Return the ID of the newly created invoice
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

        $domain_name = $faker->domainName;
        $primary_url = "https://{$domain_name}";
        // Default values for the project
        $defaultData = [
            "archived" => isset($data['archived']) ? $data['archived'] : 0,
            "status" => isset($data['status']) ? $data['status'] : 0,
            "favicon" => isset($data['favicon']) ? $data['favicon'] : '',
            "platform" => isset($data['platform']) ? $data['platform'] : '',
            "version" => isset($data['version']) ? $data['version'] : '',
            "last_updated" => isset($data['last_updated']) ? $data['last_updated'] : date('Y-m-d H:i:s'),
            "under_construction" => isset($data['under_construction']) ? $data['under_construction'] : 0,
            "last_activity" => isset($data['last_activity']) ? $data['last_activity'] : date('Y-m-d H:i:s'),
            "project_name" => isset($data['project_name']) ? $data['project_name'] : 'Test Project',
            "primary_domain" => isset($data['primary_domain']) ? $data['primary_domain'] : $domain_name,
            "primary_url" => isset($data['primary_url']) ? $data['primary_url'] : $primary_url,
            "redirect_test" => isset($data['redirect_test']) ? $data['redirect_test'] : '',
            "www" => isset($data['www']) ? $data['www'] : '',
            "non_www" => isset($data['non_www']) ? $data['non_www'] : '',
            "dev_url" => isset($data['dev_url']) ? $data['dev_url'] : '',
            "type" => isset($data['type']) ? $data['type'] : '',
            "build" => isset($data['build']) ? $data['build'] : '',
            "newsite" => isset($data['newsite']) ? $data['newsite'] : 0,
            "deposit" => isset($data['deposit']) ? $data['deposit'] : 0,
            "final_payment" => isset($data['final_payment']) ? $data['final_payment'] : 0,
            "client_id" => isset($data['client_id']) ? $data['client_id'] : 0,
            "account_id" => isset($data['account_id']) ? $data['account_id'] : 0,
            "project_created" => isset($data['project_created']) ? $data['project_created'] : date('Y-m-d H:i:s'),
            "quote_amount" => isset($data['quote_amount']) ? $data['quote_amount'] : 0.00,
            "domains" => isset($data['domains']) ? $data['domains'] : '',
            "httpcode" => isset($data['httpcode']) ? $data['httpcode'] : 0,
            "color" => isset($data['color']) ? $data['color'] : '',
            "tracking" => isset($data['tracking']) ? $data['tracking'] : '',
            "name" => isset($data['name']) ? $data['name'] : '',
            "checked_out_time" => isset($data['checked_out_time']) ? $data['checked_out_time'] : date('Y-m-d H:i:s'),
            "checked_out" => isset($data['checked_out']) ? $data['checked_out'] : 0,
            "scan" => isset($data['scan']) ? $data['scan'] : 0,
            "mothership_scan" => isset($data['mothership_scan']) ? $data['mothership_scan'] : 0,
            "extensions" => isset($data['extensions']) ? $data['extensions'] : '',
            "backups" => isset($data['backups']) ? $data['backups'] : '',
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

    public function createMothershipTicketData(array $data)
    {
        /*
        name	varchar(255) [0]	
        user_id	int(11) NULL	
        client_id	int(11) NULL	
        account_id	int(11) NULL	
        project_id	int(11) NULL	
        sample_url	text NULL	
        short_desc	varchar(255) NULL	
        emailCC	varchar(255) NULL	
        url	varchar(255) NULL	
        long_desc	text NULL	
        note	text NULL	
        priority	int(11) NULL [0]	
        requestdate	datetime NULL	
        status	int(11) [0]	
        status_history	text	
        submitted	timestamp NULL [CURRENT_TIMESTAMP]	
        submitted_userid	int(11) NULL	
        completed	timestamp NULL	
        hours_estimated	decimal(10,2) NULL	
        hours_recorded	decimal(10,2) NULL	
        closedate	timestamp NULL	
        communication	int(11) [0]	
        ticket_sent_date	datetime NULL	
        checked_out_time	timestamp NULL	
        checked_out	int(11) NULL
        */
        $defaultData = [
            'name' => isset($data['name']) ? $data['name'] : '',
            'user_id' => isset($data['user_id']) ? $data['user_id'] : 0,
            'client_id' => isset($data['client_id']) ? $data['client_id'] : NULL,
            'account_id' => isset($data['account_id']) ? $data['account_id'] : NULL,
            'project_id' => isset($data['project_id']) ? $data['project_id'] : NULL,
            'sample_url' => isset($data['sample_url']) ? $data['sample_url'] : NULL,
            'short_desc' => isset($data['short_desc']) ? $data['short_desc'] : NULL,
            'long_desc' => isset($data['long_desc']) ? $data['long_desc'] : NULL,
            'priority' => isset($data['priority']) ? $data['priority'] : 5,
            'requestdate' => isset($data['requestdate']) ? $data['requestdate'] : date("Y-m-d H:i:s"),
            'status' => isset($data['status']) ? $data['status'] : 0,
            'status_history' => '',
            'submitted' => isset($data['user_id']) ? $data['user_id'] : date("Y-m-d H:i:s"),
            'submitted_userid' => isset($data['user_id']) ? $data['user_id'] : NULL,
            'completed' => NULL,
            'hours_estimated' => isset($data['hours_estimated']) ? $data['hours_estimated'] : 0,
            'hours_recorded' => isset($data['hours_recorded']) ? $data['hours_recorded'] : 0,
            'closedate' => NULL,
            'communication' => isset($data['communication']) ? $data['communication'] : false,
            'ticket_sent_date' => isset($data['ticket_sent_date']) ? $data['ticket_sent_date'] : NULL,

        ];
        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipTicket(array $data)
    {
        $data = $this->createMothershipTicketData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Ticket with the following data:");
        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_tickets", $data);
        $data['id'] = $id;
        codecept_debug($data);

        // Return the ID of the newly created invoice
        return $data;
    }

    public function createMothershipExpenseData(array $data)
    {
        $defaultData = [
            "type" => isset($data['type']) ? $data['type'] : 'expense',
            "tax_type" => isset($data['tax_type']) ? $data['tax_type'] : 0,
            "item" => isset($data['item']) ? $data['item'] : 'Test Item',
            "client_id" => isset($data['client_id']) ? $data['client_id'] : 0,
            "account_id" => isset($data['account_id']) ? $data['account_id'] : 0,
            "project_id" => isset($data['project_id']) ? $data['project_id'] : 0,
            "name" => isset($data['name']) ? $data['name'] : 'Test Expense',
            "date" => isset($data['date']) ? $data['date'] : date('Y-m-d H:i:s'),
            "paypal_transactionid" => isset($data['paypal_transactionid']) ? $data['paypal_transactionid'] : '',
            "price" => isset($data['price']) ? $data['price'] : 0.00,
            "description" => isset($data['description']) ? $data['description'] : 'Test Description',
            'merchant' => isset($data['merchant']) ? $data['merchant'] : '',
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    /**
     * Creates a new Mothership Expense record in the database.
     *
     * This method takes an array of data, processes it to ensure it meets the
     * necessary format, and then inserts it into the database. The ID of the
     * newly created record is added to the data array and returned.
     *
     * @param array $data The data to be used for creating the Mothership Expense.
     * @return array The data array with the ID of the newly created record.
     */
    public function createMothershipExpense(array $data)
    {
        $data = $this->createMothershipExpenseData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Expense with the following data:");
        codecept_debug($data);

        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_expenses", $data);
        $data['id'] = $id;
        codecept_debug($data);

        return $data;

    }

    public function createMothershipQuoteData(array $data)
    {

        if (!isset($data['items'])) {
            $data['items'] = [
                [
                    'id' => 1,
                    'name' => 'Test Item',
                    'description' => 'Test Description',
                    'hours' => 1,
                    'minutes' => 30,
                    'quantity' => 1.5,
                    'rate' => 100.00,
                    'price' => 100.00,
                    'tax' => 0.00,
                ]
            ];
            $data['items'] = json_encode($data['items']);
            // $data['items'] = serialize($data['items']);
        } else {
            $data['items'] = json_encode($data['items']);
        }

        $defaultData = [
            "type" => isset($data['type']) ? $data['type'] : 0,
            "template_pieces" => isset($data['template_pieces']) ? $data['template_pieces'] : NULL,
            "status" => isset($data['status']) ? $data['status'] : 0,
            "client_id" => isset($data['client_id']) ? $data['client_id'] : 0,
            "name" => isset($data['name']) ? $data['name'] : 'Test Item',
            "account_id" => isset($data['account_id']) ? $data['account_id'] : 0,
            "project_id" => isset($data['project_id']) ? $data['project_id'] : 0,
            "deposit_invoice_id" => isset($data['deposit_invoice_id']) ? $data['deposit_invoice_id'] : 0,
            "project_name" => isset($data['project_name']) ? $data['project_name'] : 'Test Project',
            "project_domain" => isset($data['project_domain']) ? $data['project_domain'] : 'example.com',
            "total" => isset($data['total']) ? $data['total'] : 0.00,
            "sitemap" => isset($data['sitemap']) ? $data['sitemap'] : '',
            "approved" => isset($data['approved']) ? $data['approved'] : 0,
            "deposit" => isset($data['deposit']) ? $data['deposit'] : 0,
            "deposit_date" => isset($data['deposit_date']) ? $data['deposit_date'] : date('Y-m-d H:i:s'),
            "summary" => isset($data['summary']) ? $data['summary'] : '',
            "programming_basic" => isset($data['programming_basic']) ? $data['programming_basic'] : '',
            "programming_modules" => isset($data['programming_modules']) ? $data['programming_modules'] : '',
            "programming_template" => isset($data['programming_template']) ? $data['programming_template'] : '',
            "programming_extensions" => isset($data['programming_extensions']) ? $data['programming_extensions'] : '',
            "programming_forms" => isset($data['programming_forms']) ? $data['programming_forms'] : '',
            "programming_other" => isset($data['programming_other']) ? $data['programming_other'] : '',
            "programming_notes" => isset($data['programming_notes']) ? $data['programming_notes'] : '',
            "items" => isset($data['items']) ? $data['items'] : '',
            "created" => isset($data['created']) ? $data['created'] : date('Y-m-d H:i:s'),
            "sent_date" => isset($data['sent_date']) ? $data['sent_date'] : date('Y-m-d H:i:s'),
            "expires" => isset($data['expires']) ? $data['expires'] : date('Y-m-d H:i:s'),
            "hours" => isset($data['hours']) ? $data['hours'] : 0,
            "communication" => isset($data['communication']) ? $data['communication'] : 0,
            "checked_out_time" => isset($data['checked_out_time']) ? $data['checked_out_time'] : date('Y-m-d H:i:s'),
        ];

        // Merge provided data with defaults
        $finalData = array_merge($defaultData, $data);
        return $finalData;
    }

    public function createMothershipQuote(array $data)
    {
        $data = $this->createMothershipQuoteData($data);
        // Debugging output for visibility
        codecept_debug("Creating Mothership Quote with the following data:");
        codecept_debug($data);

        // Insert into the database
        $id = $this->Db->haveInDatabase("{$this->prefix}mothership_quotes", $data);

        // Return the ID of the newly created invoice
        return $id;
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

    public function clearClientsTable()
    {
        codecept_debug("Clearing clients table");
        $this->Db->driver->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_clients", []);
    }

    public function clearAccountsTable()
    {
        codecept_debug("Clearing accounts table");
        $this->Db->driver->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_accounts", []);
    }

    public function clearUsersTable()
    {
        codecept_debug("Clearing users table");
        $this->Db->driver->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_users", []);
    }

    public function clearInvoicesTable()
    {
        codecept_debug("Clearing invoices table");
        $this->Db->driver->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_invoices", []);
    }

    public function clearInvoiceItemsTable()
    {
        codecept_debug("Clearing invoice items table");
        $this->Db->driver->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_invoice_items", []);
    }

    public function clearPaymentsTable()
    {
        codecept_debug("Clearing payments table");
        $this->Db->driver->executeQuery("TRUNCATE TABLE {$this->prefix}mothership_payments", []);
    }

    public function resetMothershipTables()
    {
        codecept_debug("Resetting Mothership tables");

         // Turn off foreign key checks
         $this->Db->driver->executeQuery("SET FOREIGN_KEY_CHECKS = 0", []);

         // Truncate everything
         $this->clearInvoiceItemsTable();
         $this->clearInvoicesTable();
         $this->clearPaymentsTable();
         $this->clearAccountsTable();
         $this->clearClientsTable();
         $this->clearUsersTable();
 
         // Turn it back on
         $this->Db->driver->executeQuery("SET FOREIGN_KEY_CHECKS = 1", []);
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

    public function assertInvoiceStatusLate($invoiceId)
    {
        $this->assertInvoiceStatus($invoiceId, 'Late');
    }
    public function assertInvoiceStatusPaid($invoiceId)
    {
        $this->assertInvoiceStatus($invoiceId, 'Paid');
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

    public function grabTicketFromDatabase($ticketId)
    {
        $fields = [
            'id', 'short_desc', 'user_id', 'client_id', 'account_id', 'project_id', 'sample_url', 'emailCC', 'url', 
            'long_desc', 'note', 'priority', 'requestdate', 'status', 'status_history', 'submitted', 
            'submitted_userid', 'completed', 'hours_estimated', 'hours_recorded', 'closedate', 'communication', 
            'ticket_sent_date'
        ];

        $ticketData = [];
        foreach ($fields as $field) {
            $ticketData[$field] = $this->Db->grabFromDatabase("{$this->prefix}mothership_tickets", $field, ["id" => $ticketId]);
        }

        return $ticketData;
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

}
