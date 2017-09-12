# mage2_fix_pr9621

Standalone fix for [PR-9621](https://github.com/magento/magento2/pull/9621) to validate proposed solution.

Related issues: [#7968](https://github.com/magento/magento2/issues/7968), [#8018](https://github.com/magento/magento2/issues/8018).

* v. 2.1.7 still [contains](https://github.com/magento/magento2/blob/2.1.7/lib/internal/Magento/Framework/DB/Query/BatchIterator.php#L156) this error (use [v. 0.1.0](https://github.com/flancer32/mage2_fix_pr9621/releases/tag/0.1.0) for Magento 2.1.7).
* v. 2.1.8: under investigation in this moment.

## Install


```bash
$ cd ${DIR_MAGE_ROOT}
$ composer require flancer32/mage2_fix_pr9621
$ bin/magento module:enable Flancer32_FixPr9621
$ bin/magento setup:upgrade
$ bin/magento setup:static-content:deploy
$ bin/magento cache:clean
```

## Uninstall

You need an authentication keys for `https://repo.magento.com/` to uninstall any Magento 2 module. Go to your [Magento Connect](https://www.magentocommerce.com/magento-connect/customer/account/) account, section (My Account / Connect / Developer / Secure Keys) and generate pair of keys to connect to Magento 2 repository. Then place composer authentication file `auth.json` besides your `composer.json` as described [here](https://getcomposer.org/doc/articles/http-basic-authentication.md) and put your authentication keys for `https://repo.magento.com/` into the authentication file:
```json
{
  "http-basic": {
    "repo.magento.com": {
      "username": "...",
      "password": "..."
    }
  }
}
```

Then run these commands to completely uninstall `Flancer32_FixPr9621` module: 
```bash
$ cd ${DIR_MAGE_ROOT}   
$ bin/magento module:uninstall Flancer32_FixPr9621
$ bin/magento setup:upgrade
$ bin/magento setup:di:compile
$ bin/magento setup:static-content:deploy
$ bin/magento cache:clean
```

Be patient, uninstall process (`bin/magento module:uninstall ...`) takes about 2-4 minutes. Remove `auth.json` file at the end:

 ```bash
$ rm ./auth.json
```
