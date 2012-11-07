# PayU account plugin for OpenCart over 1.5.3
-------
PayU account is a web application designed as an e-wallet for shoppers willing to open an account, define their payment options, see their purchase history and manage personal profiles.

## Dependencies

The following PHP extensions are required:

* cURL
* hash
* XMLWriter
* XMLReader

## Installation

1. Copy files to the root OpenCart folder
2. Open OpenCart administration page
3. Go to the Extensions/Modules
4. From the list select PayU account and click Install
3. Go to the Extensions/Payments
4. From the list select PayU account and click Install
5. Click the Edit link
6. Fill in all required configuration fields (Checkout POS data from Merchant Manager):
* Merchant POS ID
* POS Auth Key
* Client ID (the same as Merchant POS ID)
* Key (MD5)
* Second key (MD5)
* Sort order
7. Select payment image button
8. Save