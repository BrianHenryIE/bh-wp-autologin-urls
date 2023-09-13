#!/bin/bash

wp plugin activate --all

wp plugin deactivate wp-super-cache

wp rewrite structure /%year%/%monthnum%/%postname%/ --hard;