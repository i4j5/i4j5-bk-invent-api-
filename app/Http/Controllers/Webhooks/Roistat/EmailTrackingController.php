<?php

namespace App\Http\Controllers\Webhooks\Roistat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;

/**
 * WebHook
 * Roistat
 * Емейлтрекинг
 */
class EmailTrackingController extends Controller
{
    
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
    }
    
    public function handle(Request $request)
    {
        $email_from = $request->input('email_from');
        $subject = $request->input('subject');
        $text = $request->input('text');
        $attachments = $request->input('attachments');
     
        $lead_name = "Емейлтрекинг";
        
        $utm_medium = $request->input('utm_medium');
        $utm_source = $request->input('utm_source');
        $utm_campaign = $request->input('utm_campaign');
        $utm_term = $request->input('utm_term');
        $utm_content = $request->input('utm_content');
        $url = $request->input('landing_page');
        $utm = '';
        $roistat = $request->input('visit_id');
        $comment = '';
        $referrer = $request->input('referrer');

        // Поиск по email       
        $contact = $this->contactSearch($email_from);
         
        if ($attachments != '')
        {
            $text = "$text \n--------------------\n  Вложения:";
            foreach ($attachments as $attachment) {
                $name = $attachment['name'];
                $url = $attachment['url'];
                
                $text = "$text \n $name: $url"; 
            }
        }
        
        // Сделка которая будет создана после одобрения заявки.
        $lead = $this->amocrm->lead;
        $lead['name'] = $lead_name;
        $lead['tags'] = ['Заявка с сайта'];

        // Системные
        $lead->addCustomField(232407, $utm_medium);
        $lead->addCustomField(232409, $utm_source);
        $lead->addCustomField(232413, $utm_campaign);
        $lead->addCustomField(232415, $utm_term);
        $lead->addCustomField(232417, $utm_content);
        $lead->addCustomField(232419, $referrer);
        $lead->addCustomField(232421, $url);
        $lead->addCustomField(232423, $utm);
        $lead->addCustomField(226175, 487647);
        $lead->addCustomField(240623, $roistat);

        $note = $this->amocrm->note;
        $note['element_type'] = \AmoCRM\Models\Note::TYPE_CONTACT;
        $note['note_type'] = \AmoCRM\Models\Note::COMMON;

        $comment = $comment . " \n
        ====================\n
        $lead_name \n
        ====================\n
        Почта: $email_from \n
        Тема письма: $subject \n
        Текст письма: \n 
        $text \n
        ====================\n
        Страница захвата: $url \n
        Ключевое слово: $utm_term \n
        Промокод: $roistat \n
        ";

        $note['text'] = $comment;

        if (!$contact) {
            $lead['notes'] = $note;

            $unsorted = $this->amocrm->unsorted;
            $unsorted['source'] = 'bk-invent.ru';
            $unsorted['source_uid'] = null;

            $unsorted['source_data'] = [
                'data' => [
                    'email' => [
                        'type' => 'text',
                        'element_type' => '1',
                        'name' => 'Почта',
                        'value' => $email_from,
                    ]
                ],
                'form_id' => 1,
                'form_type' => 1,
                'origin' => [
                    'ip' => $_SERVER['REMOTE_ADDR']
                ],
                'date' => time(),
                'from' => 'Емейлтрекинг'
            ];

            // Заполнение контакта 
            $contact = $this->amocrm->contact;
            $contact['name'] = $email_from;
            $contact->addCustomField('95356', [
                [$email_from, 'PRIV'],
            ]);

            // Присоединение контакт к неразобранному
            $unsorted->addDataContact($contact);

            // Присоединение сделки к неразобранному
            $unsorted->addDataLead($lead);

            // Добавление неразобранной заявки с типом FORMS
            $unsortedId = $unsorted->apiAddForms();
        } else {
            // Добавление отвественного
            if (isset($contact['responsible_user_id'])) {
                $lead['responsible_user_id'] = $contact['responsible_user_id'];
            }

            $lead_id = $lead->apiAdd();

            //Добавить коментарий
            $note['element_id'] = $contact['id'];
            $note->apiAdd();

            // Добавить задачу
            $task = $this->amocrm->task;
            $task['element_id'] = $lead_id;
            $task['element_type'] = 2;
            $task['task_type'] = 1;
            $task['text'] = "@A Связаться с клиентом. Емейлтрекинг";
            $task['responsible_user_id'] = $contact['responsible_user_id'];
            $task['complete_till'] = '+20 minutes';
            $task->apiAdd();

            $link = $this->amocrm->links;
            $link['from'] = 'leads';
            $link['from_id'] = $lead_id;
            $link['to'] = 'contacts';
            $link['to_id'] = $contact['id'];
            $link->apiLink();
        }

        return 'ok';
    }
    
    private function contactSearch($email) 
    {
        $data = $this->amocrm->contact->apiList([
            'query' => $email,
            'limit_rows' => 1,
            'type' => 'contact'
        ]);

        $res = null;

        foreach ($data as $contact) {
            foreach ($contact['custom_fields'] as $field) {
                if (isset($field['code']) && $field['code'] == 'EMAIL') {
                    foreach ($field['values'] as $item) {
                        if ($email == $item['value']) {
                            $res = $contact;
                            break(3);
                        }
                    }
                }
            }
        }
        
        return $res;
    }
}
