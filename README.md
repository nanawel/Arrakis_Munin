Arrakis_Munin
=============

A [Munin](http://munin-monitoring.org/) plugin for Magento platforms.

This plugin allows you to monitor various statistics over time from [Munin](http://munin-monitoring.org/), such as:
 - The number of customers
 - The number of orders (last hour and total)
 - The number of products and their types
 - The biggest tables in your DB
 - The biggest log files

Only a few sample monitors are provided with this package. You are encouraged to implement more in order to gather more data into your Munin server.

This plugin supports **multiple Magento instances** on the same node (see [Configuration](#Configuration)).

SCREENSHOTS OF GENERATED GRAPHS ARE NOT YET AVAILABLE, SORRY FOR THE INCONVENIENCE :(

Requirements
------------
 - Magento CE 1.4+ or Magento EE 1.9+
 - Munin-node (on the Magento server)
 - Munin (on the central Munin server)
 - PHP CLI
 - awk


Installation
------------
1. Create a link to the munin plugin provided in `src/shell/arrakis/munin_plugin/magento_` into your munin-node plugins directory (generally `/etc/munin/plugins`). Don't forget to make it executable. You may copy this file instead of linking it.
2. Install the Magento module by copying the content of the directory `src` into your Magento instance. You need to flush the cache to apply the changes. Even if the module is extremely light (no setups, no observers, no rewrites, etc.) you should validate that the module is not generating side-effects on your platform before pushing it into a production environment.
3. Copy the plugin configuration file from `src/shell/arrakis/munin_plugin/conf/magento` into the directory `/etc/munin/plugin-conf.d`.


Configuration
-------------
Edit the file `/etc/munin/plugin-conf.d/magento` you just copied and define the different local Magento instances you want to monitor.

The available directives are as follow (`{id}` here is the ID of the Magento instance, and should be incremented for each one):


    env.mageInstance{id} (Magento instance's root dir)

    env.mageinstance{id}_label (Magento instance's label)

    env.mageinstance{id}_name (Magento instance's name - ONLY LETTERS/NUMBERS/UNDERSCORES)

Example:

    env.mageinstance1 /var/www/html/magento
    env.mageinstance1_label My Magento Shop
    env.mageinstance1_name my_magento_shop


You can enable each monitor provided by the module indivually. To list the available monitors, run the following command as a privileged user:

    root@myhost:~# munin-run magento_ suggest

    magento_catalog_productcountbytype
    magento_catalog_productcountbywebsite
    magento_db_biggesttables
    magento_log_biggestfiles
    magento_sales_ordercountbystate
    magento_sales_recentordercount

If you're satisfied with the results, you can create the links to different monitors with the following command (from Munin plugins dir):

    root@myhost:/etc/munin/plugins# for p in $(munin-run magento_ suggest); do ln -s magento_ $p; done

**Limitation**: Currently monitors are enabled once for all local instances.

License
-------
See LICENSE.

The Software is provided "as is" without warranty of any kind, either express or implied, including without limitation any implied warranties of condition, uninterrupted use, merchantability, fitness for a particular purpose, or non-infringement.
