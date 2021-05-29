<?php

include "AmoCRM.php";
$amoCrm = new AmoCRM(
    // ID интеграции
    "52e2573b-9b2c-43be-bcab-b3abc01bb2ba",
    // Секретный ключ
    "8EM0yzpG5P8liQXfpTzmcLCzAidRIIRL167lfdDMSx9bnJ9h1AYBOOUACQH5tJUh",
    // Код авторизации Oauth2
    "def50200712538c64c63dcab551ca2d59a2def4ec868632e2ec8042762602fc9fc871c4a3034a71b7910521c71970568e2d5616ba449564a27968f45de60b461336afcf9ba355a1dd984ab295f635bab68f8f4afddd42f32a22acb98d4edd3c45e3b96482c553df6ae2845f4b090e251bf5d13ab539d0009a4f0ec37243cc03d58266cf47b9c4a2b87bc0d268b9459b1c223c373957f570dd2b233eb48ea505a53c107b4ffdfa8297dabaa075e9ce5d793c7f31669aaa486d397c8abd24c833c31297f045e40ca0f2b455af0839d8c4a26227d72b257aa0ab6de567e062e1047c4c2d077657c37cd45a7838af739ff4c1f289f566cb453be460702d6e1eed30f4d1e6b4439db68e170d0c5052460533b701998ba87f5afa894df3c05cc7dcd36aec20867934f1575ae675fe3714e8c19d182080b1a1f549bf2401fd4a0a36c895c3cdb8e62911892c1a2a67b8f7d2a336470824840b2e2fba8fd85785f0fb52145fc727fae02c4b749d12e2d10ea306815ec004c0e588815bc4be1d200b45bacf40c2984d97eeb89a81ca11b9f03c50fe35d27021135e6527938810dcca50e3db091c5f032d15baa0da65b",
    // Адрес, на который будет переадресован пользователь после прохождения авторизации
    "https://test.ru/test-amocrm/",
    // Поддомен нужного аккаунта
    "stalkernova"
);
// Запрос списка контактов с их задачами
$options = [
    "with" => "leads",
    "limit" => 250
];
$contacts = $amoCrm->request("contacts", $options);
try
{
    if (!isset($contacts["_embedded"])) throw new Exception("Вложения \"_embedded\" не найдены");
    if (!isset($contacts["_embedded"]["contacts"])) throw new Exception("Вложения \"contacts\" не найдены");
    // Поиск в контактах
    foreach ($contacts["_embedded"]["contacts"] as $contact) {
        if (!isset($contact["_embedded"])) throw new Exception("Вложения контакта \"_embedded\" не найдены");
        if (!isset($contact["_embedded"]["leads"])) throw new Exception("Вложения контакта \"leads\" не найдены");
        // Создаём задачу для контактов Без сделок
        if (COUNT($contact["_embedded"]["leads"]) == 0) {
            echo "Контакт \"".$contact["name"]."\" (ID ".$contact["id"].") без сделок.";
            $options = [];
            $post = [
                [
                    "text" => "Контакт без сделок",
                    "entity_type" => "contacts",
                    "entity_id" => $contact["id"],
                    "complete_till" => strtotime('+ 1 hour')
                ]
            ];
            $tasks = $amoCrm->request("tasks", $options, $post);
            // Выводим инфу о задаче
            if (!isset($tasks["_embedded"])) throw new Exception("Вложения задачи \"_embedded\" не найдены");
            if (!isset($tasks["_embedded"]["tasks"])) throw new Exception("Вложения задачи \"tasks\" не найдены");
            foreach ($tasks["_embedded"]["tasks"] as $task) {
                echo " Задача ID ".$task["id"]." создана.";
            };
            echo '<br>'; 
        };
    }
}
catch(Exception $e)
{
    die('Ошибка: ' . $e->getMessage());
}