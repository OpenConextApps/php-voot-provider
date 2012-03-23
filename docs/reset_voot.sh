#!/bin/sh
rm data/voot.sqlite
sqlite3 data/voot.sqlite < docs/voot.sql
chmod o+w data/voot.sqlite
