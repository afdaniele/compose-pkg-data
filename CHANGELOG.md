## 1.0.1 (June 19, 2023)
  - added guest access modes

## 1.0.0 (July 23, 2020)
  - minor

## 1.0.0-rc2 (July 21, 2020)
  - added compatibility data to metadata.json as per compose v1.0+ requirement
  - reformatted metadata.json
  - preparing transition to compose v1.0

## 1.0.0-rc (July 02, 2020)
  - minor
  - API endpoint data/get now supports selector inside path. e.g., /a/[b,c,d]
  - entries in data-viewer are now opened on a separate modal
  - API endpoint /data/get now supports the optional arg 'seek'
  - Revert "first commit"
  - first commit
  - added support for user-data

## 0.3.1 (April 24, 2019)
  - minor
  - added _is_database_name_valid() to all public functions in Data

## 0.3 (April 21, 2019)
  - added "go back to list" in DB page
  - fixed bug
  - fixed bug
  - minor
  - added new DB button in DB list page
  - added new, drop API endpoints
  - added Data::new, Data::drop

## 0.2.2 (April 21, 2019)
  - minor

## 0.2.1 (April 21, 2019)
  - updated bump-version

## 0.2 (April 21, 2019)
  - added Data Viewer (data-viewer) page
  - added Data::del, moved authentication to API endpoints
  - we now convert value to JSON in set() if we can
  - added Data:listDBs, Data::getDB
  - added Data::info
  - added Data::canAccess
  - admins now have access to all DBs regardless of ownership
  - minor

## 0.1.2 (April 21, 2019)
  - added bump-version script

## 0.1.1 (April 21, 2019)
  - solved #1

## 0.1 (April 21, 2019)
  - First release!
  - Minimal functionalities.
