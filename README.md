Requirements (PHP 8.2, Laravel 11, Composer, Node)

Installation steps:

git clone ...

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

php artisan serve

CRUD routes:

/crud/categories

/crud/subcategories

/crud/products
