<?php
    class Logger{
        public static function info($message, $route){
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "info",
                "route" => $route,
                "message" => $message
            ];
            error_log("[log][{$log["date"]}][{$log["route"]}] | {$log["message"]}");
            if(!file_exists(__DIR__ . '../logs/log')){
                mkdir(__DIR__ . '../logs/log');
            }
            $currentDate = date("Y-m-d");;
            file_put_contents(__DIR__ . '../logs/log/' . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);

            self::sendLog($log);
        }


        public static function error($messageOrError, $route){
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "error",
                "route" => $route,
                "message" => $messageOrError instanceOf Error ? $messageOrError->getMessage() : $messageOrError,
            ];



            error_log("[error][{$log["date"]}][{$log["route"]}] | {$log["message"]}");
            if(!file_exists(__DIR__ . '../logs/error')){
                mkdir(__DIR__ . '../logs/error');
            }
            $currentDate = date("Y-m-d");
            file_put_contents(__DIR__ . '../logs/error/' . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);

            self::sendLog($log);
        }

        public static function debug($message, $route){
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "debug",
                "route" => $route,
                "message" => $message
            ];

            error_log("[debug][{$log["date"]}][{$log["route"]}] | {$log["message"]}");
            if(!file_exists(__DIR__ . '../logs/debug')){
                mkdir(__DIR__ . '../logs/debug');
            }
            $currentDate = date("Y-m-d");
            file_put_contents(__DIR__ . '../logs/debug/' . $currentDate . ".log", json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);

            self::sendLog($log);
        }

        private static function sendLog($log){
           //Logging for me
        //    $context = stream_context_create([
        //        'http' =>[
        //            'method' => 'POST',
        //            'header' => 'Content-Type: application/json',
        //            'content' => json_encode($log)
        //        ],
        //    ]);
        //    $response = file_get_contents('https://logs.jdvivian.co.uk/api/logs/create', false, $context);
           $url = "https://logs.jdvivian.co.uk/api/logs/create";
           $data = ['appId' => "clu1e7xgx0000hxomsbgyb4pv", "log" => json_encode($log)];
           $ch = curl_init();
           curl_setopt($ch, CURLOPT_URL, $url);
           curl_setopt($ch, CURLOPT_POST, 1);
           curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
           curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           $repsonse = curl_exec($ch);
           
           if($log["type"] === "debug"){
            mail("joel@jdvivian.co.uk", "Debug Log", $log);
           }
            curl_close($ch);
        }
        
        public static function warning($message, $route)
        {
            $log = [
                "date" => (new DateTime())->format(DateTimeInterface::ATOM),
                "type" => "warning",
                "route" => $route,
                "message" => $message
            ];
            error_log("[error][{$log["date"]}][{$log["route"]}] | {$log["message"]}");
            if (!file_exists(__DIR__ . '../logs/warning')) {
                mkdir(__DIR__ . '../logs/warning');
            }
            $currentDate = date("Y-m-d");
            file_put_contents(__DIR__ . '../logs/warning/' . $currentDate . ".log", json_encode($log) . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . "../logs/complete/" . $currentDate . '.log', json_encode($log) . "\n", FILE_APPEND);
            self::sendLog($log);
        }
    }
