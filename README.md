```bash
po stahnuti

#------BE-------
cd BE
composer install
touch .env
cp .env.example .env

# do souboru .env dopsat
# DB_CONNECTION=sqlite
# DB_DATABASE=/cesta/k/BE/database/database.sqlite

php artisan key:generate
# dopsat ten key do .env, jsem nechal komentare

php artisan migrate
php artisan storage:link
php artisan serve

#------FE-------
# jiny wsl
cd pictera/FE
npm install
npm run dev
