# network-weathermap-influxdb

This a very basic InfluxDB datasource for [Network Weathermap]. To use, copy WeatherMapDataSource_influxdb.php into  $WEATHERMAP/lib/datasources. The TARGET definition is:
```
influxdb:host:database:username:password:seriesin:seriesout:from:whereclause
```
***NOTE: due to config file limitations, spaces in the where clause must be escaped with %20, see examples below.***

This is originally based on [guequierre/php-weathermap-influxdb](https://github.com/guequierre/php-weathermap-influxdb)

## Assumptions

This code makes several assumptions for simplicity, these may not be valid in your environment:
* InfluxDB is running on port 8086. If you are using a different port you will have to change the URL in the code
* All data sources except for ifOperStatus are counters and the derivitive funciton will be used. These derivitives are calcualted assuming "per second" data
* Data is queried over the last 5 minutes

## Examples
```TARGET influxdb:localhost:telegraf:::ifOperStatus:ifOperStatus:ifTable:"hostname"='mynetworkdevice'%20AND%20"ifIndex"='{node:this:ifIndex}'```

```TARGET 8*influxdb:localhost:telegraf:::ifHCInOctets:ifHCOutOctets:ifXTable:"hostname"='mynetworkdevice'%20AND%20"ifAlias"='{link:this:ifAlias}'```
        

[Network Weathermap]: https://www.network-weathermap.com
