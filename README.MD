###############################################
Ecommerce Repeat Orders
Pre 0.1 proof of concept
###############################################

Add the "repeating order" functionality to
e-commerce.  For example, if someone wants to purchase
a weekly loaf of bread without having to re-enter
the order each week, the repeating order module allows
the customer to enter the order once at which time
the shop admin can execute the weekly repeat of the order.


Developers
-----------------------------------------------
Nicolaas Francken [at] sunnysideup.co.nz

Requirements
-----------------------------------------------
Ecommerce 1.0+ / SSU Branch
SilverStripe 2.4+
Payment class that allows repeating payment

Project Home
-----------------------------------------------
See http://code.google.com/p/silverstripe-ecommerce

Demo
-----------------------------------------------
See http://www.silverstripe-ecommerce.com

Installation Instructions
-----------------------------------------------
1. Find out how to add modules to SS and add module as per usual.
2. copy configurations from this module's _config.php file
into mysite/_config.php file and edit settings as required.
NB. the idea is not to edit the module at all, but instead customise
it from your mysite folder, so that you can upgrade the module without redoing the settings.

If you just want one or two things from this module
then of course you are free to copy them to your
mysite folder and delete the rest of this module.
