<?php
chdir(dirname(__FILE__).'/..');
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
ini_set('memory_limit','-1');
require 'vendor/autoload.php';

function milliseconds() {
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
}

$loop = React\EventLoop\Factory::create();

$server = new React\Http\Server($loop, new React\Http\Middleware\StreamingRequestMiddleware(), function (Psr\Http\Message\ServerRequestInterface $request) {

    if($request->getMethod() === 'POST'){
        $dir = dirname(__FILE__);
        if(!\is_dir("$dir/uploads")){
            mkdir("$dir/uploads",0777,true);
        }
        $filename = isset($request->getQueryParams()['filename'])?$request->getQueryParams()['filename']:'';
        if($filename === '')
            return new React\Http\Message\Response(
                400,
                array(
                    'Content-Type' => 'text/plain'
                ),
                "Please specify a \"filename\" query string.\n"
            ); 

        $file = \fopen("$dir/uploads/$filename",'a');
        $start = milliseconds();
        $body = $request->getBody();
        assert($body instanceof Psr\Http\Message\StreamInterface);
        assert($body instanceof React\Stream\ReadableStreamInterface);
        
        return new React\Promise\Promise(function ($resolve, $reject) use ($body, $file, &$start) {
            $body->on('data', function ($data) use ($file) {
                \fwrite($file,$data);
            });

            $body->on('end', function () use ($resolve, $file, &$start){
                $end = milliseconds();
                \fclose($file);
                $resolve(new React\Http\Message\Response(
                    200,
                    array(
                        'Content-Type' => 'text/plain'
                    ),
                    "done (reactphp):".($end-$start)
                ));
            });

            // an error occures e.g. on invalid chunked encoded data or an unexpected 'end' event
            $body->on('error', function (\Exception $exception) use ($resolve, &$bytes) {
                $resolve(new React\Http\Message\Response(
                    400,
                    array(
                        'Content-Type' => 'text/plain'
                    ),
                    "Encountered error: {$exception->getMessage()}\n"
                ));
            });
        });
    }

    return new React\Http\Message\Response(
        200,
        array(
            'Content-Type' => 'text/html'
        ),
        file_get_contents(dirname(__FILE__).'/../public/index.html')
    );
});

$socket = new React\Socket\Server(8080, $loop);
$server->listen($socket);


echo "Server running at http://127.0.0.1:8080\n";

$loop->run();