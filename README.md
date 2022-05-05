# Roadrunner distribution

This is an **experimental** distribution to run Neos / Flow via Roadrunner.

> Note: The Neos / Flow code currently needs patches to 

## Getting up and running

### Install dependencies

```shell
composer install
```

### Patch Packages

```shell
patch -s -p0 < core-patches.patch
cp *Instance.php Packages/Framework/Neos.Flow/Classes/ObjectManagement/
```

### Setup site

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

* Provide configs for Development and Production context
* Better exception handling
* ~~Optimized reset of object manager instances~~
  * This currently needs a patched version of Neos / Flow with `ImmutableInstance` and `ResettableInstance` (not yet commited to a fork)
* Keep track of memory usage
* Use Roadrunner features
* Check usage of `Now` and how it can be re-injected
* Support handling of objects managed by Flow but implemented outside of it (e.g. `Doctrine\ORM\EntityManagerInterface`),
  it should be possible to mark either as immutable or provide a custom reset function
* Move `psr-worker.php` to Flowpack.Roadrunner Package (how to reference from top-level?)
* Can `rr` be moved to `bin/`?
* ExceptionHandler should be Psr compatible in Flow (and use a small adapter for "echo" in default setup)
* Perspective: request scoped objects in Flow
* 
