#!/bin/bash

wp plugin activate --all

wp rewrite structure /%year%/%monthnum%/%postname%/ --hard;