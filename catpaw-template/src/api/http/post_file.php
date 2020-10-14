<?php
namespace api\http;

use com\github\tncrazvan\catpaw\http\HttpConsumer;

function milliseconds() {
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
}

return function(string $filename, string &$body, HttpConsumer $consumer){
    if(!\is_dir("./uploads/consumer")){
        mkdir("./uploads/consumer",0777,true);
    }
    $file = \fopen("./uploads/consumer/$filename",'a');
    $start = milliseconds();
    for($consumer->rewind();$consumer->valid();$consumer->consume($body)){
        \fwrite($file,$body);
        $current = milliseconds();
        yield $consumer;
    }
    $end = milliseconds();
    \fclose($file);

    return "done (catpaw):".($end-$start);
};