## Install
```sh
$ composer install
$ composer run-script post-root-package-install
$ composer run-script post-create-project-cmd

sudo chown -R $USER vendor
sudo chown -R $USER storage
$ chown -R 777 vendor
$ chown -R 777 storage
```