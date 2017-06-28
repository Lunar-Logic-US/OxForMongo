# Ox in Docker

## Prerequisites
* Docker

## Dockerfile.prod 
* Extends our apache-php-mongo **production** image.
* Configures apache and php.
* Source is baked into image.

## Dockerfile.env
* Extends our apache-php-mongo **development** image.
* Configures apache and php.
* Source is baked into image.

## Usage

### Development
1. Read the `docker-compose.yml` file

2. Update `$mongo_config` in `app-blank/config/app.php` 

3. From root: `docker-compose up`

### Build and Push to Gitlab
Note: this is done during ci on tag

1. Build production image from root: `docker build -f Dockerfile.prod . -t registry.gitlab.com/lunar-logic/ox/prod:<CURRENT REPO TAG>`

2. Build devlopment image from root: `docker build -f Dockerfile.dev . -t registry.gitlab.com/lunar-logic/ox/dev:<CURRENT REPO TAG>`

3. `docker login registry.gitlab.com`

4. `docker push <IMAGE>`

### Verify an image
1. `docker run -dit --rm -p 8443:443 <IMAGE>`

2. Visit [https://localhost:8080/](https://localhost:8443/)

### CI
* On commit, a dev image is built and tests are run inside it
* On tag, dev and prod images are pushed to the gitlab registry
