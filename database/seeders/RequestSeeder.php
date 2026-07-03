<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Request;
use App\Models\StatusHistory;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        StatusHistory::query()->delete();
        Request::query()->delete();

        Request::create([
            'source' => 'telegram',
            'client_name' => 'Алексей',
            'phone' => '+79161234211',
            'car_info' => 'Газель Next, 2021',
            'problem' => 'Скрипит при торможении, машина хуже тормозит. На ходу.',
            'urgency' => 'emergency',
            'status' => 'new',
            'request_type' => 'client',
        ]);

        Request::create([
            'source' => 'manual',
            'client_name' => 'Ирина',
            'phone' => '+79030887700',
            'car_info' => 'Kia Rio, 2018',
            'problem' => 'Плановое ТО, замена масла и фильтров.',
            'urgency' => 'soon',
            'status' => 'processing',
            'responsible_id' => 1,
            'request_type' => 'client',
        ]);

        Request::create([
            'source' => 'telegram',
            'client_name' => 'Сергей',
            'phone' => '+79851904040',
            'car_info' => 'Toyota Camry, 2020',
            'problem' => 'Замена передних тормозных колодок и дисков.',
            'urgency' => 'today',
            'status' => 'assigned',
            'responsible_id' => 1,
            'request_type' => 'client',
        ]);

        Request::create([
            'source' => 'manual',
            'client_name' => null,
            'phone' => '+79161112233',
            'car_info' => 'Lada Vesta',
            'problem' => 'Не заводится, стартер крутит. Нужна диагностика.',
            'urgency' => 'emergency',
            'status' => 'contacted',
            'responsible_id' => 1,
            'request_type' => 'client',
        ]);

        Request::create([
            'source' => 'manual',
            'client_name' => 'Дмитрий',
            'phone' => '+79251530000',
            'car_info' => 'VW Polo, 2019',
            'problem' => 'Замена передних колодок. Готово, клиент забрал.',
            'urgency' => 'planned',
            'status' => 'success',
            'responsible_id' => 1,
            'request_type' => 'client',
        ]);

        Request::create([
            'source' => 'telegram',
            'client_name' => 'Ольга',
            'phone' => '+79094445566',
            'car_info' => 'Hyundai Solaris, 2017',
            'problem' => 'Шумит подвеска на кочках, стук спереди слева.',
            'urgency' => 'soon',
            'status' => 'follow_up',
            'responsible_id' => 1,
            'request_type' => 'client',
        ]);

        Request::create([
            'source' => 'telegram',
            'client_name' => 'Максим',
            'phone' => '+79167778899',
            'car_info' => 'Ford Focus, 2015',
            'problem' => 'Замена ремня ГРМ, пробег 120000.',
            'urgency' => 'planned',
            'status' => 'new',
            'request_type' => 'client',
        ]);

        Request::create([
            'source' => 'manual',
            'client_name' => 'Наталья',
            'phone' => '+79263334455',
            'car_info' => null,
            'problem' => 'Клиент не берёт трубку, не удалось согласовать визит.',
            'urgency' => null,
            'status' => 'lost',
            'responsible_id' => 1,
            'request_type' => 'client',
        ]);
    }
}
