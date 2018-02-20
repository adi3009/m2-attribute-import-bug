### Magento 2 modules depicting a bug with product attributes of type multiselect with a source model 

Code in this repo can be used to import any type of product attributes, however after importing an attribute of type
mulitiselect with a source model the attribute does not appear in flat table when flat catalog is enabled. Use provided
attribute csvs in var/importexport directory to create **test_multiselect** attribute.

**How to**

- Enable flat catalog
- Copy app/code/Smart and var/importexport to m2 installation directory
- do upgrade ```./bin/magento setup:upgrade```
- check table ```smart_catalog_test_multiselect``` is created in db
- import attribute from provided csv ```./bin/magento smart:import:product-attributes importexport/attr.csv importexport/attr/```
- verify that attribute ```test_multiselect``` is created and is used in product listing
- reindex ```./bin/magento index:reindex```

**Expected Result**

- Column ```test_multiselect``` in catalog_product_flat_1 table

**Actual Result**

- Table ```catalog_product_flat_1``` is not updated   

**Environment**
---------------
**Magento Version** - 2.2.2

**PHP Version** - PHP 7.1.12

**MySql version** - 5.6.33

**OS** - Ubuntu 14.04.1