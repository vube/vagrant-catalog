Vagrant Catalog
===============

[![Build Status](https://travis-ci.org/vube/vagrant-catalog.png?branch=master)](https://travis-ci.org/vube/vagrant-catalog)
[![Coverage Status](https://coveralls.io/repos/vube/vagrant-catalog/badge.png?branch=master)](https://coveralls.io/r/vube/vagrant-catalog?branch=master)
[![Latest Stable Version](https://poser.pugx.org/vube/vagrant-catalog/v/stable.png)](https://packagist.org/packages/vube/vagrant-catalog)
[![Dependency Status](https://www.versioneye.com/user/projects/5363b9b5fe0d0764770000b0/badge.png)](https://www.versioneye.com/user/projects/5363b9b5fe0d0764770000b0)

Application to manage boxing up Vagrant VMs to be used as base boxes for use in
private box distribution systems.

You can use [vagrant-boxer](https://github.com/vube/vagrant-boxer) to package up the Vagrant boxes and keep their
metadata updated, then upload the files to a server that is running vagrant-catalog.

vagrant-catalog is a simple HTTP interface to your collection of Vagrant boxes that allows you to browse the boxes
you've created and returns metadata to Vagrant so you can use commants like `vagrant box outdated`,
`vagrant box update`, etc, on your own boxes.


Features
--------

- Simple vagrant catalog browser
- Integrates easily with Vagrantfile
    - Shows you how to add each box to your Vagrantfile


Installation
------------

To install, clone this repository into your web server's docroot, or a sub-directory thereof.

Then, run `composer update`

```bash
$ git clone https://github.com/vube/vagrant-catalog /path/to/docroot
$ cd /path/to/docroot
$ composer update
```

For example on a typical Debian server you may clone it into `/var/www/vagrant.yourdomain.com`


Configuration
-------------

```bash
$ cd /path/to/docroot
$ cp config.php.dist config.php
$ edit config.php
```

The default configuration is probably OK for most installations.  It assumes are running in the
docroot of your virtualhost, and that you are storing files in the `files` sub-directory.


Usage
-----

Once you're set up, you need to actually install your Vagrant base boxes into the `files`
sub-directory.

For example your docroot may look like this:

```yaml
docroot:
  - files:
    - your_company:
      - base_box:
        - metadata.json
        - your_company-base_box-1.0.0-virtualbox.box
      - devel_box:
        - metadata.json
        - your_company-devel_box-1.0.0-virtualbox.box
  - config.php
  - index.php
```

In the above example, there are 2 Vagrant boxes, named `your_company/base_box` and `your_company/devel_box`.

The `*.box` files and the `metadata.json` files should be created by
[vagrant-boxer](https://github.com/vube/vagrant-boxer)
and then you should have uploaded them to this location.


Dependencies
------------

- A web server (Apache or nginx)
- PHP 5.3.7+
- Composer
