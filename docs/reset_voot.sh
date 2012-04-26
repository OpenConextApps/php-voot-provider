#!/bin/sh
rm data/voot.sqlite
sqlite3 data/voot.sqlite < docs/voot.sql
sqlite3 data/voot.sqlite < docs/voot_demo_data.sql
chmod o+w data/voot.sqlite
