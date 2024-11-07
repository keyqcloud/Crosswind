**Getting Started**

Prior to running gust to initialize the database and Kyte, make sure the `config.php` as been created and the required database information supplied. Once `config.php` has been created and the minimal required information supplied, you are ready to initialize the database by running:

```
php gust.php
```

The command above will prompt you with a series of questions. Once you have provided your answers, you will be ready to execute the command provided below.


```
php gust.php init db
```

Once the database initialized successfully, it is time to create a root account (master admin) using the following command:
```
init account [Account Name] [User's Name] [email] [password]
```

Make sure to replace the values in the square brakets.
