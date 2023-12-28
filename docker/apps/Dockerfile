FROM bitnami/php-fpm:8.2

RUN apt update && apt install -y autoconf php-dev pkg-php-tools wget build-essential

RUN wget https://pecl.php.net/get/redis-5.3.4.tgz && \
    tar xzf redis-5.3.4.tgz &&  \
    cd redis-5.3.4 &&  \
    phpize &&  \
    ./configure &&  \
    make &&  \
    make install
