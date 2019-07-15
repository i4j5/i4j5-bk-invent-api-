<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Phone;

/**
 * WebHook
 * amoCRM
 * Поиск дублей
 */ 
class FixAllContactsController extends Controller // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! переименовать класс FindDuplicates
{

    private $amocrm;
    private $phone;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
        $this->phone = Phone::getInstance();
    }

    public function handle($query = '')
    {        
        set_time_limit(0);

        $start = microtime(true);

        $i = 0;
        $data = [];

        $run = true;
        for($limit_offset = 0; $run; $limit_offset++) 
        {
            $res = $this->amocrm->contact->apiList([
                'query' => $query,
                'limit_rows' => 500,
                'limit_offset' => $limit_offset * 500,
                'type' => 'cantact'
            ]);

            $data = array_merge($data, $res);

            if (count($res) < 500) $run = false;
        }

        $finishData = microtime(true);

        $contacts = [];
        $allPhones = [];
        foreach ( $data as $contact )
        {
            // Формируеи список тегов
            $tags = [];
            foreach ( $contact['tags'] as $tag )
            {
                array_push($tags, $tag['name']);
            }

            if(isset($contact['custom_fields'])) 
            {
                foreach( $contact['custom_fields'] as $field )
                {
                    if (isset($field['code']) && $field['code'] == 'PHONE') 
                    {
                        foreach ( $field['values'] as $item )
                        {
                            $allPhones[] = [
                                'contactID' => $contact['id'],
                                'phone' => $item['value'],
                            ];
                        }  
                    }
                }
            }

            $contacts[$contact['id']] = [
                'tags' => $tags,
                'double' => false
            ];

            //Поиск дублей
            foreach ( $allPhones as $item )
            {
                $key = array_search($item['phone'], array_column($allPhones, 'phone'));

                if($key) 
                {
                    if($item['contactID'] != $allPhones[$key]['contactID'])
                    {
                        // $contacts[ $item['contactID'] ]['double'] == true;

                        $contact = $contacts[$item['contactID']];

                        array_push($contact['tags'], 'Дубль');

                        $updateContact = $this->amocrm->contact;
                        $updateContact['tags'] = $tags;
                        $updateContact->apiUpdate((int) $item['contactID'], 'now');
                        $i++;

                    }
                } else  {
                    $contact = $contacts[$item['contactID']];

                    if( in_array('Дубль', $contact['tags']) )
                    {
                        unset($tags[array_search('Дубль', $contact['tags'])]);

                        $updateContact = $this->amocrm->contact;
                        $updateContact['tags'] = $tags;
                        $updateContact->apiUpdate((int) $item['contactID'], 'now');
                    }
                }
            }

            $finishDouble = microtime(true);
            

            // Сохраняем изменения
            // foreach ( $contacts as  $id => $contact)
            // {
            //     if($contact['double']) {
            //         array_push($contact['tags'], 'Дубль');
            //         $updateContact = $this->amocrm->contact;
            //         $i++;
            //     } else {
            //         if( in_array('Дубль', $contact['tags']) ) 
            //         {
            //             unset($tags[array_search('Дубль', $contact['tags'])]);
            //             $updateContact = $this->amocrm->contact;
            //         }
            //     }

            //     if(isset($updateContact))
            //     {
            //         $updateContact['tags'] = $tags;
            //         $updateContact->apiUpdate((int) $id, 'now');
            //     }
            // }
        
        }

        $finish = microtime(true);

        echo 'Время на получение контактов: ' . ($finishData - $start) . ' сек. <br>';
        echo 'Время на поиск дубликатов: ' . ($finishDouble - $finishData) . ' сек. <br>';
        echo 'Общее время: ' . ($finish - $start) . ' сек. <br>';
        echo "Найдено дублей: $i";
    }

}
