#!/bin/sh
rm data/oauth2.sqlite
sqlite3 data/oauth2.sqlite < docs/oauth.sql
chmod o+w data/oauth2.sqlite
 
