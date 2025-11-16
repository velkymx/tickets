# Tickets!


Tickets is an agile style ticket tracker using the Laravel PHP framework and Bootstrap 3. Please feel free to add features and grow the project. See the screenshots below!


## Installing


Copy files to your server.


Run composer update to get all of the updated libraries


```
composer update
```


Edit the `.env` to reference the new database


From the command line run the migrations.


```
php artisan migrate
```


Seed the database


```
php artisan db:seed --class=DefaultsSeeder
```


Add default Users: 
* unassigned:nopassword
* admininistrator:password123

```
php artisan db:seed --class=UserSeeder
```


Load in a web browser and enjoy!


## Screenshots


![Alt text](https://raw.githubusercontent.com/velkymx/tickets/master/screenshots/listview.png?raw=true 'List View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/ticket.png?raw=true 'Ticket View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/status.png?raw=true 'Status View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/form.png?raw=true 'Form View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/milestone.png?raw=true 'Form View')



## License


Tickets! is open-sourced software licensed under the [MIT license](LICENSE.md).

