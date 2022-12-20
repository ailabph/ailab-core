# ailab-core
core framework classes

---
## Changelog

v.0.70.13
- fix bug on load payment values
- suppress type errors
- add experimental method to check if value is compatible to property

v.0.70.10
- major
  - implement data class for wallet
  - implement data class for center coin
  - implement generation of coins
  - implement use of top up credits
  - implement class for sms queue sending
  - implement data class for top up credits
  - implement data class for top up request
- minor
  - add assert check date
  - add getter for product codes and check
  - use load values to remove excess properties for snapshot in points log
  - add improved method to retrieve wallet
  - throw error if hook provided is not callable
  - implement compiled logs with no query info
  - fix bug invalid class usage
  - add patch for top up credits and request
  - add test for top up
  - bug fixes

v.0.63.12
- major:
  - add class for code ownership log
  - implement generation of entry and product codes
  - add generic class getter
  - add class for image_log
  - add class for data inventory order
  - add class for data order detail
  - add class for data order header
  - add data class for payment
  - implement creation of payment for entry and products
  - add class for permissions
- minor:
  - add bcmath as requirement
  - add test for data codes
  - codes patch for variant_tag and product_tag
  - codes patch for price_paid
  - order_header patch make hash unique
  - order_detail patch make hash unique
  - package_variant patch make package_tag unique
  - payment patch make payment_ref unique
  - code improvements
  - add test for data payment
  - disable tests for render
  - added new helper methods

v.0.53.5
 - add empty logs
 - add logs folder with empty php file
 - add pure flag in products getter

v.0.53.2
 - add initial class for session
 - use core session to check if logged in
 - bug fixes

v.0.52.9
 - fix bug not able to log if called via hook call
 - add implementation to add value via hrtime if primary key is an non-auto increment integer
 - fix bug in importing values to object
 - add check if no sponsor_id, throws error

v.0.52.5
 - fix bug that throws error if default X class does not exist

v.0.52.4
 - major
   - add traverse sponsor and binary class
   - user methods with hooks
 - minor
   - override current time
   - get sponsor and binary upline methods
   - add isCallable method
   - patch sponsor_id and placement_id

v.0.50.26
  - minor 
    - improve format in logger
    - added property constants. improved template readability
    - implement Logger on TableClass 
    - fix bug in resetting original values after saving
    - improve logic on hasValue and hasChange
    - add hasPlaceholder method
    - improve logging format

v.0.50.19
  - fix list not using extended class

v.0.50.18
  - fix list not using extended class

v.0.50.17
  - implement core module for codes
  - implement core module for package_header
  - implement core module for package_variant
  - implement core module for points_log
  - implement core module for products
  - implement core module for account
  - implement core module for user
  ---
  - patch sponsor_id and placement_id
  - patch account and wallet snapshot in points_log
  - patch add gen_level in points_log
  - patch add sponsor_level in points_log
  - patch add data_value in points_log
  - patch add data_value_remarks in points_log
  - patch add product_id in points_log
  - patch add product_tag in points_log
  - patch add variant_id in points_log
  - patch add variant_tag in points_log
  - patch add product_srp in points_log
  - patch add product_dp in points_log
  - patch add bonus percentage in points_log
  - add option force write in logger
  - always record actions in patching
  - implement some core helper methods in tools
  - code improvements
  

v.0.43.4
  - add undefined constants and exclude from get query
  - allow null on default values

v.0.43.2
  - add log pure
  - implement default value to required that triggers an error if not replaced
  - set sql mode to strict
  - check required values are set before saving
---
  - improve sql to php types
  - code improvements

v.0.39.3
  - only accept php script to add header
  - assume passed argument is an executable script
  - accept only php script tp add to header
  - add body wrapper twig path
  - add content wrapper twig path
  - implement twig path for add content
  - only accept php script to add top content
  - refactor pureRender, require twig path
  - imported scripts to js files
  - initial core js
  - fp3 script
  --- 
  - remove obfuscation
  - add additional methods for assert
  - add getter for core template dir

v.0.28.7
  - implement patch to run on site and core level
  - add index property info
  - updated config and public config properties
  - add manual path and page details on render class
  - add patch to make account code unique
  - implement default value on reset
  - patch package_tag as unique
  - patch some points_log properties as unique
  - patch product tag as unique
  - add sample core script

v.0.25.2
  - update method get base dir of composer module
  - implement core patch on patcher
  - add core patch to set username to unique

v.0.24.5
  - new patcher class
  - implement fingerprint and js cookies
  - set writeClass method to private
  - add final page wrapper, removing new lines and html spaces
  - add empty index on patch folder
  - fix some methods in tools

v.0.23.1
  - fix some methods in tools
  - execute parts of page after adding content

v.0.22.10
  - implement body wrapper

v.0.22.9
  - fix bug on loading module tpl
  - refactor get site title
  - renamed core functions
  - added body wrapper

v0.22.5
  - added base class for config public
  - add logger class
  - added public config loader with extend support
  - added top content
  - added bottom content
  - ---
  - improved format in logging
  - added blank index files
  - added sample config public
  - change implementation on hooking to header, footer, top and bottom content
  - implement logger in render

v0.17.1
  - fix generating php db class

v0.17
  - added transaction bypass
  - added db table info extractor
  - added generator for php db classes
  - added base TableClass
  - improved get connection
  - add methods in helpers class
  - 

v0.14
  - added htaccess, remove index and php ext
  - add config_public
  - add logs directory
  - updated and added methods in assert class
  - improved config class
  - added connection class
  - added time helper class
  - added methods in tools

v0.13.1
  - allow force_real_path on getBaseDirectory for test compatability

v0.13.0 - site title in Render class
- added site_title in Render class

v0.12.0 - partial implementation on front end functions
- added boostrap
- added popper
- added underscore
- added bootbox
- added js helper functions
  - global and local loading
  - simplified postForm
  - ajax reply handler
  - modal message

v0.3.1
- fix page template render to be used for project

v0.3.0
- add Render class with test
- add new methods on Tools class

v0.2.1
- fixed get base directory

v0.2
- Add Assert class and test
- Add Config class and test
- Add Random class and test

v0.1.2
- change minimum stability to stable

v0.1.1
- change minimum stability to beta

v0.1
- initial commit