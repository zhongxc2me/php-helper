<?php

namespace Myzx\PhpHelper\DB;

use MongoDB\Client;

class MongoDBUtil
{

    public function getClient($dsn): Client
    {
        return new Client($dsn);
    }
}