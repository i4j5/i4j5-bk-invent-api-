<?php

namespace App;

use Dotzero\LaravelAmoCrm\Facades\AmoCrm;

// Фабрика 
class Contact
{
    private $amocrm;

    public function __construct()
    {
        $this->amocrm = AmoCrm::getClient();

        // Преобразование номера
        // Проверка номера на дубли
        // Создание контакты
        // Возвращаем объект контакта
        
    }
    
}
