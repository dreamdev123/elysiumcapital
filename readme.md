### database
Database is shared with office but for capital we want to have separate tables with prefix capital_*
Because of that after import office db we have to run migration from that project:
```bash
php artisan migrate
```

### ide-helper
```bash
php artisan ide-helper:models -W --reset
```
or for each group of models
```bash
php artisan ide-helper:models -W --reset "App\CapitalUser"
```

### crontab
```bash
* * * * * php /data/www/elysiumcapital.io/current/artisan schedule:run >> /dev/null 2>&1
```

## Links - signup:
```
6 digits for affiliate/IB links - 749238
```
