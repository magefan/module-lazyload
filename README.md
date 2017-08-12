# [Magefan](http://magefan.com/) Lazy Load Extension for Magento 2

## Features
  * Allow to load images on your store only when customer can see them. Module reduces page size and number of request.

## Configuration
  * To enable or disable extension please navigate to Magento 2 Admin Panel > Stores > Magefan Extensions > Lazy Load

## Requirements
  * Magento Community Edition 2.1.x or Magento Enterprise Edition 2.1.x

## Installation Method 1 - Installing via composer
  * Open command line
  * Using command "cd" navigate to your magento2 root directory
  * Run command: composer require magefan/module-lazyload


## Installation Method 2 - Installing using archive
  * Download [ZIP Archive](https://github.com/magefan/module-lazyload/archive/master.zip)
  * Extract files
  * In your Magento 2 root directory create folder app/code/Magefan/LazyLoad
  * Copy files and folders from archive to that folder
  * In command line, using "cd", navigate to your Magento 2 root directory
  * Run commands:
```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

## Support
If you have any issues, please [contact us](mailto:support@magefan.com)
then if you still need help, open a bug report in GitHub's
[issue tracker](https://github.com/magefan/module-lazyload/issues).

Please do not use Magento Marketplace's Reviews or (especially) the Q&A for support.
There isn't a way for us to reply to reviews and the Q&A moderation is very slow.

## License
The code is licensed under [Open Software License ("OSL") v. 3.0](http://opensource.org/licenses/osl-3.0.php).
