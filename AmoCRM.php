<?php

class AmoCRM
{
    private $clientId;
    private $subdomain;
    private $fileToken;
    private $accessToken;
    private $error_code = [
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    ];
    
	public function __construct($clientId, $clientSecret, $clientAuthCode, $redirectUri, $subdomain)
	{
        try
        {
            // Получаем данные для авторизации
            $this->clientId = $clientId;
            $this->subdomain = $subdomain;
            // Проверяем наличие существующего токена
            $this->fileToken = "token-$clientId.txt";
            if (file_exists($this->fileToken)) {
                // Загружаем токен из файла
                $this->loadAuthTokenFromFile();
            }
            // Токена нет. Идем авторизовываться
            else {
                $this->oauth2($clientSecret, $clientAuthCode, $redirectUri);
            }

        }
        catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage());
        }
	}

    private function loadAuthTokenFromFile()
	{
        try
        {
            $config = parse_ini_file($this->fileToken);
            if (isset($config["access_token"]) and (mb_strlen($config["access_token"]) > 0)) {
                $this->accessToken = $config['access_token'];
            }
            else throw new Exception("В файле '$this->fileToken' отсуствует токен");
        }
        catch(Exception $e)
        {
            die('Ошибка загрузки токена OAuth2: ' . $e->getMessage());
        }
    }

    private function oauth2($clientSecret, $clientAuthCode, $redirectUri)
	{
        
        $link = 'https://' . $this->subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $clientAuthCode,
            'redirect_uri' => $redirectUri,
        ];

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        try
        {
            $code = (int)$code;
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($this->error_code[$code]) ? $this->error_code[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            die('Ошибка получения токена OAuth2: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }
        
        $response = json_decode($out, true);
        // Сохраняем данные токена
        try
        {
            $this->accessToken = $response['access_token'];

            $fd = fopen($this->fileToken, 'w');
            $str = "access_token = \"".$response['access_token']."\"".PHP_EOL;  //Access токен
            fwrite($fd, $str);
            $str = "refresh_token = \"".$response['refresh_token']."\"".PHP_EOL;  //Refresh токен
            fwrite($fd, $str);
            $str = "token_type = \"".$response['token_type']."\"".PHP_EOL;  //Тип токена
            fwrite($fd, $str);
            $str = "expires_in = \"".$response['expires_in']."\"".PHP_EOL; //Через сколько действие токена истекает
            fwrite($fd, $str);
            fclose($fd);
        }
        catch(Exception $e)
        {
            die('Ошибка сохранения токена OAuth2: ' . $e->getMessage());
        }
	}
    
	public function request($method, $options = [], $post = false, $head = false)
	{
        $query = '';
		if (COUNT($options) > 0) $query = '?' . http_build_query($options);
        $link = 'https://' . $this->subdomain . '.amocrm.ru/api/v4/' . $method . $query; //Формируем URL для запроса
        /** Формируем заголовки */
        $headers = [
            'Authorization: Bearer ' . $this->accessToken
        ];
        
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
		if ($post) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        try
        {
            $code = (int)$code;
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($this->error_code[$code]) ? $this->error_code[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            die('Ошибка получения полчения данных: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }
        
        $response = json_decode($out, true);
        return $response;
	}
}