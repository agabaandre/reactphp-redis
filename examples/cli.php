<?php

// $ php examples/cli.php
// $ REDIS_URI=localhost:6379 php examples/cli.php

use React\EventLoop\Loop;

require __DIR__ . '/../vendor/autoload.php';

$factory = new Clue\React\Redis\Factory();

echo '# connecting to redis...' . PHP_EOL;

$factory->createClient(getenv('REDIS_URI') ?: 'localhost:6379')->then(function (Clue\React\Redis\Client $redis) {
    echo '# connected! Entering interactive mode, hit CTRL-D to quit' . PHP_EOL;

    Loop::addReadStream(STDIN, function () use ($redis) {
        $line = fgets(STDIN);
        if ($line === false || $line === '') {
            echo '# CTRL-D -> Ending connection...' . PHP_EOL;
            Loop::removeReadStream(STDIN);
            return $redis->end();
        }

        $line = rtrim($line);
        if ($line === '') {
            return;
        }

        $params = explode(' ', $line);
        $method = array_shift($params);
        $promise = call_user_func_array(array($redis, $method), $params);

        // special method such as end() / close() called
        if (!$promise instanceof React\Promise\PromiseInterface) {
            return;
        }

        $promise->then(function ($data) {
            echo '# reply: ' . json_encode($data) . PHP_EOL;
        }, function (Exception $e) {
            echo '# error reply: ' . $e->getMessage() . PHP_EOL;
        });
    });

    $redis->on('close', function() {
        echo '## DISCONNECTED' . PHP_EOL;

        Loop::removeReadStream(STDIN);
    });
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
});
