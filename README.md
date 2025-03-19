# Mothership
Mothership is a friendly, open-source Joomla extension built for solo developers and small businesses. It started as a personal project designed to simplify everyday challenges like invoicing, support tickets, time tracking, expenses, taxes, and logs. Over time, it evolved into a tool that handles all these essential functions in one place—making life easier not just for me, but for anyone juggling the many hats of a web development operation.

At its core, Mothership organizes your business around five main objects:

- **Clients**
- **Accounts**
- **Projects**
- **Invoices**
- **Payments**

The model is flexible enough to accommodate real-world complexities. For example, a single client might manage multiple accounts—whether these represent different parts of the business, subdomains, or entirely separate brands. Each account can then have its own projects. While projects currently focus on websites, the architecture is designed to eventually support other types of work, like graphic design or any other service you might offer.

One of the standout features in the initial release is the projects module. Often, a big part of launching a new website involves researching the domain—finding out details like hosting information, email configurations, and even identifying the underlying CMS or technologies in use. Mothership’s scanning feature does exactly that, gathering vital domain data and making it easy to have informed discussions with your clients. And because these tools are available from the front end, your clients can also use them to better understand their projects.

In short, Mothership is built to streamline your workflow and let you focus on what really matters—delivering great work and growing your business.

## Clients
The **Clients** object represents the individuals or organizations you work with. Each client has the following attributes:

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

This structure ensures that all necessary client information is captured and organized efficiently.

## Accounts
The **Accounts** object represents the different accounts managed by a client. Each account has the following attributes:

- **ID**: A unique identifier for the account.
- **Client ID**: The ID of the client to whom the account belongs.
- **Name**: The name of the account.
- **Rate**: The billing rate for the account.
- **Created**: The timestamp when the account record was created.
- **Created By**: The ID of the user who created the account record.
- **Checked Out Time**: The timestamp when the account record was last checked out.
- **Checked Out**: The ID of the user who last checked out the account record.

## Invoices
The **Invoices** object represents the invoices generated for clients. Each invoice has the following attributes:

- **ID**: A unique identifier for the invoice.
- **Number**: The invoice number.
- **Client ID**: The ID of the client to whom the invoice belongs.
- **Account ID**: The ID of the account associated with the invoice.
- **Rate**: The billing rate for the invoice.
- **Status**: The status of the invoice (e.g., paid, unpaid).
- **Total**: The total amount of the invoice.
- **Due Date**: The date by which the invoice should be paid.
- **Sent Date**: The date the invoice was sent to the client.
- **Paid Date**: The date the invoice was paid.
- **Created**: The timestamp when the invoice was created.
- **Created By**: The ID of the user who created the invoice.
- **Checked Out Time**: The timestamp when the invoice record was last checked out.
- **Checked Out**: The ID of the user who last checked out the invoice record.

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

## Payments
The **Payments** object represents the payments made by clients. Each payment has the following attributes:

- **ID**: A unique identifier for the payment.
- **User ID**: The ID of the user who made the payment.
- **Client ID**: The ID of the client associated with the payment.
- **Account ID**: The ID of the account associated with the payment.
- **Name**: The name of the payment.
- **Payer Email**: The email address of the payer.
- **Invoices**: The invoices associated with the payment.
- **Method**: The method of payment (e.g., credit card, PayPal).
- **Amount**: The total amount of the payment.
- **Transaction Fee**: The transaction fee associated with the payment.
- **Net Total**: The net total amount after deducting the transaction fee.
- **Transaction ID**: The transaction ID of the payment.
- **Payment Date**: The date and time when the payment was made.
- **Status**: The status of the payment.
- **Checked Out Time**: The timestamp when the payment record was last checked out.
- **Checked Out**: The ID of the user who last checked out the payment record.

### Payment Status Levels
- **Pending**: The payment has been initiated but not yet completed.
- **Completed**: The payment has been successfully processed.
- **Failed**: The payment attempt was unsuccessful.
- **Refunded**: The payment has been refunded to the client.
- **Cancelled**: The payment was cancelled before completion.
- **Disputed**: The payment is under dispute and is being reviewed.

---

## Payments Helper
The **Payments Helper** provides several methods to manage and update payment records and statuses. Below are the methods available:

- **updatePaymentStatus()**: Updates the status of a payment.
- **setInvoicePaid()**: Marks an invoice as paid.
- **updateInvoiceStatus()**: Updates the status of an invoice.
- **insertPaymentRecord()**: Inserts a new payment record into the system.
- **getPaymentById()**: Retrieves a payment record by its unique identifier.
- **deletePayment()**: Deletes a payment record from the system.
- **validatePaymentData()**: Validates the data for a payment record before it is processed.
- **calculateTransactionFee()**: Calculates the transaction fee for a payment.
- **generatePaymentReceipt()**: Generates a receipt for a completed payment.
- **sendPaymentNotification()**: Sends a notification to the client about the payment status.

## Payment Supported Events
- **onAfterInitialise**: Runs after mothership initializes
- **onMothershipPaymentRequest**: Runs whenever a payment request is being made

## Payment Plugins
There are two payment plugins: Paypal and Zelle. The payment plugin type is 'Mothership Payments'.

### PayPal

### Zelle
This payment method is essentially a digital version of "Pay by Check". Once the payment has been confirmed, an administrator will need to manually update the status of the payment to confirmed.
