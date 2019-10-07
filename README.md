Create *.password* file:
```
echo "email@domain:password" >> .password
```

Run:
```
php -f run.php > logs/error.log 2>&1
```
