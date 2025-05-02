![Run Mothership Tests](https://github.com/trevorbicewebdesign/mothership/actions/workflows/codeception.tests.yml/badge.svg)
# Mothership
Mothership is a friendly, open-source Joomla extension built for solo developers and small businesses. It started as a personal project designed to simplify everyday challenges like invoicing, support tickets, time tracking, expenses, taxes, and logs. Over time, it evolved into a tool that handles all these essential functions in one place—making life easier not just for me, but for anyone juggling the many hats of a web development operation.

At its core, Mothership organizes your business around five main objects:

- **Clients**
- **Accounts**
- **Projects**
- **Invoices**
- **Payments**
- **Domains**
- **Logs**

The model is flexible enough to accommodate real-world complexities. For example, a single client might manage multiple accounts—whether these represent different parts of the business, subdomains, or entirely separate brands. Each account can then have its own projects. While projects currently focus on websites, the architecture is designed to eventually support other types of work, like graphic design or any other service you might offer.

One of the standout features in the initial release is the projects module. Often, a big part of launching a new website involves researching the domain—finding out details like hosting information, email configurations, and even identifying the underlying CMS or technologies in use. Mothership’s scanning feature does exactly that, gathering vital domain data and making it easy to have informed discussions with your clients. And because these tools are available from the front end, your clients can also use them to better understand their projects.

In short, Mothership is built to streamline your workflow and let you focus on what really matters—delivering great work and growing your business.

---

## Clients
The **Clients** object represents the individuals or organizations you work with.

- **ID**: A unique identifier for the client.
- **Name**: The name of the client.
- **Email**: The client's email address.
- **Phone**: The client's phone number.
- **Address**: Includes address lines, city, state, and zip code.
- **Tax ID**: The client's tax identification number.
- **Default Rate**: The default billing rate for the client.
- **Owner User ID**: The ID of the user who owns the client record.
- **Created**: The timestamp when the client record was created.
- **Created By**: The ID of the user who created the client record.
- **Checked Out Time**: The timestamp when the client record was last checked out.
- **Checked Out**: The ID of the user who last checked out the client record.

### Clients Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_clients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `address_1` VARCHAR(255) NOT NULL,
  `address_2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(32) NOT NULL,
  `state` VARCHAR(32) NOT NULL,
  `zip` VARCHAR(32) NOT NULL,
  `tax_id` VARCHAR(30) NOT NULL,
  `default_rate` DECIMAL(10,2) DEFAULT NULL,
  `owner_user_id` INT(11) DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
```

## Accounts
The **Accounts** object represents the different accounts managed by a client.

- **ID**: A unique identifier for the account.
- **Client ID**: The ID of the client to whom the account belongs.
- **Name**: The name of the account.
- **Rate**: The billing rate for the account.
- **Created**: The timestamp when the account record was created.
- **Created By**: The ID of the user who created the account record.
- **Checked Out Time**: The timestamp when the account record was last checked out.
- **Checked Out**: The ID of the user who last checked out the account record.

### Accounts Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_accounts` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `client_id` INT(10) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `rate` DECIMAL(10,2) DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_accounts_client` (`client_id`),
  KEY `idx_name` (`name`(100)),
  CONSTRAINT `fk_accounts_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;
```

## Domains
The **Domains** object represents the domains associated with client accounts. 

- **ID**: A unique identifier for the domain.
- **Name**: The name of the domain (e.g., example.com).
- **Client Id**: The ID of the client who owns the domain.
- **Account Id**: The ID of the account associated with the domain.
- **Status**: The current status of the domain (e.g., active, expired).
- **Registrar**: The registrar where the domain is registered.
- **Reseller**: The reseller through whom the domain was purchased, if applicable.
- **DNS Provider**: The provider managing the domain's DNS settings.
- **NS1**: The primary nameserver for the domain.
- **NS2**: The secondary nameserver for the domain.
- **NS3**: An optional tertiary nameserver for the domain.
- **NS4**: An optional quaternary nameserver for the domain.
- **Purchase Date**: The date when the domain was purchased.
- **Expiration Date**: The date when the domain is set to expire.
- **Auto Renew**: Indicates whether the domain is set to renew automatically.

### Domains Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_domains` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `client_id` INT(10) NOT NULL,
  `account_id` INT(10) DEFAULT NULL,
  `status` ENUM('active', 'expired', 'transferring') NOT NULL DEFAULT 'active',
  `registrar` VARCHAR(255) DEFAULT NULL,
  `reseller` VARCHAR(255) DEFAULT NULL,
  `dns_provider` VARCHAR(255) DEFAULT NULL,
  `ns1` VARCHAR(255) NULL DEFAULT NULL,
  `ns2` VARCHAR(255) NULL DEFAULT NULL,
  `ns3` VARCHAR(255) NULL DEFAULT NULL,
  `ns4` VARCHAR(255) NULL DEFAULT NULL,
  `purchase_date` DATE DEFAULT NULL,
  `expiration_date` DATE DEFAULT NULL,
  `auto_renew` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `modified` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_domains_client` (`client_id`),
  KEY `fk_domains_account` (`account_id`),
  KEY `idx_name` (`name`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;
```

### Domain Status Levels
- **Active**: The domain is currently active and operational.
- **Inactive**: The domain is not currently in use or has been deactivated.
- **Pending**: The domain is awaiting activation or completion of a required process.
- **Suspended**: The domain has been temporarily disabled due to policy violations or other issues.

## Invoices
The **Invoices** object represents the invoices generated for clients. Each invoice has the following attributes:

- **ID**: A unique identifier for the invoice.
- **Number**: The invoice number.
- **Client ID**: The ID of the client to whom the invoice belongs.
- **Account ID**: The ID of the account associated with the invoice.
- **Rate**: The billing rate for the invoice.
- **Status**: The status of the invoice (e.g., draft, opened, canceled, closed).
- **Total**: The total amount of the invoice.
- **Due Date**: The date by which the invoice should be paid.
- **Sent Date**: The date the invoice was sent to the client.
- **Paid Date**: The date the invoice was paid.
- **Locked**: Setting this to true will make the invoice view only.
- **Created**: The timestamp when the invoice was created.
- **Created By**: The ID of the user who created the invoice.
- **Checked Out Time**: The timestamp when the invoice record was last checked out.
- **Checked Out**: The ID of the user who last checked out the invoice record.

### Invoices Tables
```
CREATE TABLE `#__mothership_invoices` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `client_id` INT(10) NULL DEFAULT NULL,
  `account_id` INT(10) NULL DEFAULT NULL,
  `rate` DECIMAL(10,2) NULL DEFAULT NULL,
  `status` INT(11) NULL DEFAULT NULL,
  `total` DECIMAL(10,2) NULL DEFAULT NULL,
  `due_date` DATE NULL DEFAULT NULL,
  `sent_date` DATE NULL DEFAULT NULL,
  `paid_date` DATE NULL DEFAULT NULL,
  `locked` BOOLEAN NOT NULL DEFAULT 0,
  `created` DATETIME NULL DEFAULT (CURRENT_TIMESTAMP),
  `created_by` INT(11) NULL DEFAULT NULL,
  `checked_out_time` DATETIME NULL DEFAULT NULL,
  `checked_out` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_client` (`client_id`) USING BTREE,
  INDEX `fk_account` (`account_id`) USING BTREE,
  INDEX `idx_number` (`number`) USING BTREE,
  CONSTRAINT `fk_invoice_account` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_invoice_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;
```

### Invoice Lifecycle Status Levels
Invoices that are set from `Draft` to `Opened` will have their `Locked` status set to true. Opened invoices should not be modified. Invoices that are in a `Locked` state can be `Unlocked` if it is necessary to override for some reason.

- **Draft**: The invoice is being created and is not yet finalized.
- **Opened**: The invoice has been finalized and sent to the client and is awaiting payment.
- **Cancelled**: The invoice has been canceled and is no longer valid.
- **Closed**: The invoice has been paid and is considered complete.

### Invoice Payment Status Levels
- **Unpaid**: The invoice has been issued but no payment has been received yet.
- **Partially Paid**: A portion of the invoice amount has been paid, but the full balance is still outstanding.
- **Paid**: The invoice has been fully paid and no balance remains.

## Invoice Items
The **Invoice Items** object represents the individual items listed on an invoice. Each invoice item has the following attributes:

- **ID**: A unique identifier for the invoice item.
- **Invoice ID**: The ID of the invoice to which the item belongs.
- **Name**: The name of the item.
- **Description**: A description of the item.
- **Hours**: The number of hours worked for the item.
- **Minutes**: The number of minutes worked for the item.
- **Quantity**: The quantity of the item.
- **Rate**: The billing rate for the item.
- **Subtotal**: The subtotal amount for the item.
- **Ordering**: The order in which the item appears on the invoice.

### Invoice Items Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_invoice_items` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `invoice_id` INT(10) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `hours` INT(11) NOT NULL DEFAULT 0,
  `minutes` INT(11) NOT NULL DEFAULT 0,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ordering` INT(11) NOT NULL DEFAULT 0,
  KEY `fk_invoice_items_invoice` (`invoice_id`),
  KEY `idx_name` (`name`(191)),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;
```

> **Note:** Only invoices in **Draft** status can be deleted. Attempting to delete an invoice that is **Opened**, **Cancelled**, or **Closed** will be blocked to prevent accidental data loss. When a draft invoice is deleted, all associated `Invoice Payments` records are also removed automatically to maintain data integrity.

## Payments
The **Payments** object represents the payments made by clients. Each payment has the following attributes:

- **ID**: A unique identifier for the payment.
- **User ID**: The ID of the user who made the payment.
- **Client ID**: The ID of the client associated with the payment.
- **Account ID**: The ID of the account associated with the payment.
- **Payer Email**: The email address of the payer.
- **Invoices**: The invoices associated with the payment.
- **Method**: The method of payment (e.g., credit card, PayPal).
- **Amount**: The total amount of the payment.
- **Transaction Fee**: The transaction fee associated with the payment.
- **Net Total**: The net total amount after deducting the transaction fee.
- **Transaction ID**: The transaction ID of the payment.
- **Payment Date**: The date and time when the payment was made.
- **Status**: The status of the payment.
- **Locked**: Setting this to true will make the payment view only.
- **Checked Out**: The ID of the user who last checked out the payment record.

### Payments Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `client_id` INT NOT NULL,
  `account_id` INT DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATETIME NOT NULL,
  `fee_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fee_passed_on` TINYINT(1) NOT NULL DEFAULT 0,
  `payment_method` VARCHAR(50) NOT NULL, 
  `transaction_id` VARCHAR(255) DEFAULT NULL,
  `status` INT NOT NULL DEFAULT 0,
  `processed_date` DATETIME DEFAULT NULL,
  `locked` BOOLEAN NOT NULL DEFAULT 0,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_payments_client` (`client_id`),
  KEY `idx_transaction_id` (`transaction_id`(100)),
  CONSTRAINT `fk_payments_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Payment Status Levels
- **Pending**: The payment has been initiated but not yet completed.
- **Completed**: The payment has been successfully processed.
- **Failed**: The payment attempt was unsuccessful.
- **Refunded**: The payment has been refunded to the client.
- **Canceled**: The payment was canceled before completion.
- **Disputed**: The payment is under dispute and is being reviewed.

## Invoice Payments
The **Invoice Payments** object represents payments that are applied to specific invoices. Invoice payments have a many to many relationship with payments and invoices. One payment can go to multiple invoices or multiple payments can go to one invoice.

- **ID**: A unique identifier for the payment.
- **invoice_id**: The invoice that this payment will be applied to
- **payment_id**: The payment that is being applied to the invoice
- **allocated_amount**: The amount of the payment that is applied to this invoice

### Invoice Payments Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_invoice_payment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `payment_id` INT NOT NULL,
  `invoice_id` INT NOT NULL,
  `applied_amount` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_invoice_payment_payment` FOREIGN KEY (`payment_id`) REFERENCES `#__mothership_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoice_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `#__mothership_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Note:** If a draft invoice is deleted, any `Invoice Payments` records linked to that invoice will also be deleted automatically. This helps keep your data consistent and prevents orphaned records.

## Projects

- **ID**: A unique identifier for the project.
- **Client ID**: The ID of the client associated with the project.
- **Account ID**: The ID of the account associated with the project.
- **Name**: The name of the project.
- **Description**: The description of the project.
- **Type**: The type of project, currently there are only `Websites`
- **Status**: The status of the project. Can be `active` or `inactive`
- **Metadata**: Json to store data related to different project types
- **Created**: The date the project was created
- **Created By**: The user that created the project
- **Checked Out**: The ID of the user who last checked out the project record.

### Projects Table
```
CREATE TABLE IF NOT EXISTS `#__mothership_projects` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `client_id` INT(10) NOT NULL,
  `account_id` INT(10) DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `type` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL,
  `checked_out_time` DATETIME DEFAULT NULL,
  `checked_out` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_projects_client` (`client_id`),
  KEY `idx_name` (`name`(100)),
  CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`) REFERENCES `#__mothership_clients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_projects_account` FOREIGN KEY (`account_id`) REFERENCES `#__mothership_accounts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1;
```

## Logs 

### Logs Table

- **ID**:
- **Client Id**:
- **Account Id**:
- **User Id**: 
- **Object Id**:
- **Object Type**:
- **Action**:
- **Meta**:
- **Created**:

```
CREATE TABLE IF NOT EXISTS `#__mothership_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_id` INT(11) NULL DEFAULT NULL,
  `account_id` INT(11) NULL DEFAULT NULL,
  `user_id` INT(11) NULL DEFAULT NULL,
  `object_id` INT(11) NULL DEFAULT NULL,
  `object_type` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `action` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `meta` JSON NULL DEFAULT NULL,
  `created` DATETIME NULL DEFAULT (CURRENT_TIMESTAMP),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
;
```

---

## Payment Supported Events
- **onAfterInitialiseMothership**: Runs after mothership initializes
- **onMothershipPaymentRequest**: Runs whenever a payment request is being made

## Payment Plugins
There are two payment plugins: Paypal and Zelle. The payment plugin type is 'Mothership Payments'.

### Creating a Payment Plugin
To create a Mothership Payment plugin, follow these steps:

1. **Create the Plugin Directory**: Create a new directory for your plugin under the `plugins/mothership-payment` directory. Name the directory according to your payment method, e.g., `mypaymentmethod`.

2. **Create the Plugin Files**: Inside your plugin directory, create the following files:
    - `mypaymentmethod.php`: This is the main plugin file.
    - `mypaymentmethod.xml`: This is the manifest file that describes your plugin.

3. **Define the Plugin Class**: In `mypaymentmethod.php`, define a class that extends `MothershipPaymentsPlugin`. Implement the required methods for processing payments.

    ```php
    defined('_JEXEC') or die;

    class PlgMothershipPaymentsmypaymentmethod extends MothershipPaymentsPlugin
    {
         public function initiate($payment, $invoice) {
          /* Initiates the payment process for this plugin whatever that is */
         }
         
    }
    ```

4. **Create the Manifest File**: In `mypaymentmethod.xml`, define the plugin metadata and files.

    ```xml
    <extension type="plugin" group="mothershippayments" method="upgrade">
         <name>PLG_MOTHERSHIPPAYMENTS_mypaymentmethod</name>
         <author>Your Name</author>
         <version>1.0.0</version>
         <description>My custom payment plugin for Mothership</description>
         <files>
              <filename plugin="mypaymentmethod">mypaymentmethod.php</filename>
         </files>
         <fieldset name="basic">
              <field name="display_name" type="text" label="Display Name" description="Description here" default="mypaymentmethod" />
          </fieldset>
    </extension>
    ```

5. **Install and Enable the Plugin**: Install the plugin through the Joomla Extension Manager and enable it from the Plugin Manager.

6. **Configure the Plugin**: Add any necessary configuration options in the plugin settings to allow users to enter their payment gateway credentials.

By following these steps, you can create a custom payment plugin for Mothership that integrates with your preferred payment gateway.

### PayPal
This payment method allows clients to pay invoices using PayPal. Once the payment is completed, the status of the payment will be automatically updated to confirmed.

### Zelle
This payment method is essentially a digital version of "Pay by Check". Once the payment has been confirmed, an administrator will need to manually update the status of the payment to confirmed.

### Pay By Check
This payment method allows clients to pay invoices by mailing a physical check. Once the check is received and processed, an administrator will need to manually update the status of the payment to confirmed. This method is ideal for clients who prefer traditional payment methods or do not use digital payment platforms.

---

# Helpers

## Mothership Helper
- **getReturnRedirect($default = null)**:

## Client Helper
- **getClientListOptions()**: Retrieves a list of client options for selection.
- **getClient($client_id)**: Retrieves the details of a specific client based on the provided client ID.

## Account Helper
- **getAccountListOptions($client_id=NULL)**: Retrieves a list of account options for a specific client or all clients if no client ID is provided.
- **getAccount($account_id)**: Retrieves the details of a specific account based on the provided account ID.

## Invoice Helper
- **getStatus($status_id)**: Retrieves the status details for the given status ID.
- **isLate($invoice_id)**: Checks if the specified invoice is past its due date.
- **getDueString(int $invoice_id)**: Retrieves a formatted string indicating the due date of the specified invoice.
- **getDueStringFromDate(?string $dueDate)**: Converts a due date into a human-readable string format.
- **setInvoiceClosed($invoiceId)**: Marks the specified invoice as paid.
- **getInvoiceAppliedPayments($invoiceID)**: Retrieves all payments applied to the specified invoice.
- **sumInvoiceAppliedPayments($invoiceId)**: Calculates the total amount of payments applied to the specified invoice.
- **updateInvoiceStatus($invoiceId, $status)**: Updates the status of the specified invoice.
- **getInvoice($invoice_id)**: Retrieves the details of the specified invoice.
- **recalculateInvoiceStatus(int $invoiceId)**: This method recalculates the status of an invoice based on its current data. If an invoice was set to `Closed` it will be set back to `Opened` due to it no longer being fully paid.


## Payments Helper
The **Payments Helper** provides several methods to manage and update payment records and statuses. Below are the methods available:

- **getPayment($paymentId)**: Retrieves the payment details for the given payment ID.
- **getInvoicePayment($invoiceId, $paymentId)**: Retrieves the payment details associated with a specific invoice and payment ID.
- **updateStatus($paymentId, $status_id)**: Updates the status of a payment based on the provided status ID.
- **getStatus($status_id)**: Retrieves the status details for the given status ID.
- **updatePaymentStatus($paymentId, $status)**: Updates the payment status with the provided status value.
- **insertPaymentRecord(int $clientId, int $accountId, float $amount, $paymentDate, float $fee, $feePassedOn, $paymentMethod, $txnId, int $status)**: Inserts a new payment record with the specified details.
- **insertInvoicePayments($invoiceId, $paymentId, $applied_amount)**: Inserts a payment record for a specific invoice with the applied amount.

## Domains Helper

- **getDomain(int $domain_id)**: Retrieves the domain information based on the provided domain ID.
- **getStatus(int $status_id)**: Retrieves the domain status based on a provided integer status level

## Project Helper

- **scanWebsiteProject(string $url): array**: Scans the given website URL and returns an array containing information about the website's structure, metadata, and other relevant details.
- **getGenerator($html)**: Extracts and returns the generator meta tag from the provided HTML content, which typically indicates the CMS or framework used by the website.
- **detectJoomla(array $headers, string $html): bool**: Analyzes the provided HTTP headers and HTML content to determine if the website is powered by Joomla. Returns `true` if Joomla is detected, otherwise `false`.
- **detectWordpress(array $headers, string $html): bool**: Analyzes the provided HTTP headers and HTML content to determine if the website is powered by WordPress. Returns `true` if WordPress is detected, otherwise `false`.


## Logs Helper
- **log(array $params)**: Logs a generic event with the provided parameters.
- **logPaymentLifecycle(string $event, int $invoiceId, int $paymentId, ?int $clientId = null, ?int $accountId = null, float $amount = 0.0, string $method = '', ?string $extraDetails = null)**: Logs the lifecycle events of a payment, such as initiation or completion.
- **logPaymentInitiated($invoice_id, $payment_id, $client_id, $account_id, $invoiceTotal, $paymentMethod)**: Logs when a payment process is initiated for a specific invoice.
- **logPaymentCompleted($payment)**: Logs the completion of a payment.
- **logPaymentFailed($paymentId, ?string $reason = null)**: Logs a failed payment attempt with an optional reason.
- **logObjectViewed($object_type, $object_id, $client_id, $account_id)**: Logs when a specific object (e.g., invoice, domain) is viewed.
- **logDomainViewed($client_id, $account_id, $domain_id)**: Logs when a domain is viewed by a user.
- **logProjectViewed($client_id, $account_id, $project_id)**: Logs when a project is viewed by a user.
- **logPaymentViewed($client_id, $account_id, $payment_id)**: Logs when a payment record is viewed by a user.
- **logInvoiceViewed($client_id, $account_id, $invoice_id)**: Logs when an invoice is viewed by a user.
- **logAccountViewed($client_id, $account_id)**: Logs when an account is viewed by a user.
- **logInvoiceStatusOpened($invoice_id, $client_id, $account_id)**: Logs when an invoice status is changed to "Opened."
- **logStatusChange(object $payment, string $newStatus)**: Logs a status change for a payment, including the new status.

# Notification Emails

## Invoice Opened
The invoice has been set from `Draft` to `Opened`. This will send the email template `invoice.opened` to the Client Owner and BCC an administrator.

## Payment Completed
The payment has been set from `pending` to `completed`. This will send the email template `payment.completed` to the Client Owner and BCC an administrator. This should be sent to the payee whenever the payment cycle has been completed. 

# Testing

### DB Helpers

- **createMothershipClientData(array $data)**:
- **createMothershipClient(array $data)**:
- **createJoomlaUserData(array $data)**:
- **createJoomlaUser(array $data)**:
- **createMothershipUserData(array $data)**:
- **createMothershipUser(array $data)**:
- **createMothershipAccountData(array $data)**:
- **createMothershipAccount(array $data)**:
- **createMothershipInvoiceData(array $data)**: 
- **createMothershipInvoice(array $data)**: 
- **createMothershipInvoiceItemData(array $data)**:
- **createMothershipPaymentData(array $data)**:
- **createMothershipPayment(array $data)**:
- **createMothershipProjectData(array $data)**:
- **createMothershipInvoicePaymentData(array $data)**:
- **createMothershipInvoicePayment(array $data)**:
- **createMothershipDomainData(array $data)**:
- **createMothershipDomain(array $data)**:
- **createMothershipLogData(array $data)**:
- **createMothershipLog(array $data)**:
- **setInvoiceStatus($invoiceId, $status)**:
- **clearClientsTable()**:
- **clearAccountsTable()**:
- **clearInvoicesTable()**:
- **clearInvoiceItemsTable()**:
- **clearPaymentsTable()**:
- **clearInvoicePaymentTable()**:
- **clearUsersTable()**:
- **setMothershipConfig(array $settings)**:
- **grabInvoiceRow($invoiceId, $rowNumber)**:
- **grabDomainFromDatabase($domainId)**:
- **getClientIdByName($clientName)**:
- **grabLastCompletedPaymentId()**:
- **setPaymentStatus($paymentId, $status)**:
- **setPaymentLocked($paymentId)**:
- **setPaymentUnlocked($paymentId)**:
- **setInvoiceLocked($invoiceId)**:
- **setInvoiceUnlocked($invoiceId)**:

### Custom Assertions

- **assertInvoiceHasRows($invoiceId, $expectedRows)**:
- **assertInvoiceStatus(int $invoiceId, string $expectedStatusLabel)**:
- **assertInvoiceStatusDraft($invoiceId)**:
- **assertInvoiceStatusOpened($invoiceId)**:
- **assertInvoiceStatusClosed($invoiceId)**:
- **assertPaymentStatus(int $paymentId, string $expectedStatusLabel)**:
- **assertPaymentStatusDraft($paymentId)**:
- **assertPaymentStatusOpened($paymentId)**:
- **assertPaymentStatusCompleted($paymentId)**:
- **assertInvoiceClientId($invoiceId, $expectedClientId)**:
- **assertInvoiceHasItems(int $invoiceId, array $expectedItems)**:

### Helpers

- **calculatePaypalFee($amount)**:
- **totalWithPaypalFee($amount)**:

## Acceptance

### Front End

- **MothershipFrontClientsCest**:
- **MothershipFrontAccountsCest**:
- **MothershipFrontInvoicesCest**:
- **MothershipFrontProjectsCest**:
- **MothershipFrontDomainsCest**:
- **MothershipFrontPayByCheckCest**:
- **MothershipFrontZelleCest**:
- **MothershipFrontPaymentsCest**:


## Functional

## API

## Integration

