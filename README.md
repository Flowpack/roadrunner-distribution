# Roadrunner distribution

This is an experimental distribution to run Neos / Flow via Roadrunner.

## Getting up and running

### Install dependencies

```shell
composer install
```

* Configure database etc.
* Migrate
* Import site

### Start Roadrunner

```shell
./rr serve
```

Go to http://localhost:8080/ - Magic!

### Check Roadrunner workers

```shell
./rr workers
```

## Loose ends

* Better exception handling
* Optimized reset of object manager instances
* Keep track of memory usage
* Use Roadrunner features
