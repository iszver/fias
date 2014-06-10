FIAS
====

Автодополнение физических адресов по базе ФИАС.

## Инициалиация базы данных

Для инициализации необходимо запустить `init.php`. Поддерживаются 3 режима работы:

1. `php cli/init.php` — скачает с сайта ФИАСа последнюю версию базы, распакует и импортирует;
2. `php cli/init.php /path/to/archive.rar` — распакует и импортирует архив;
3. `php cli/init.php /path/to/fias_directory` — импортирует уже распакованный архив.

## API

### `/api/complete` — дополнение адреса

Пример запроса:

    http://fias.loc/api/complete?pattern=Невск&limit=20

    Ответ:
    {
        "items": [
            {"title": "г Москва, пр Невский", "is_complete": false, "tags": ["address"]},
            {"title": "г Москва, Невское урочище", "is_complete": false, "tags": ["address"]},
            {"title": "Невский вокзал", "is_complete": true, "tags": ["place", "railway"]}
        ]
    }

GET-параметры:

* `pattern` — дополняемый адрес;
* `limit` — максимальное количество вариантов дополнения в ответе (не более 50, см. `config/config.ini`);
* `regions` — массив номеров регионов для ограничения поиска адресов (см. `database/02_system_data.sql`);
* `max_address_level` — максимальная детализация адреса.

Максимальная детализация влияет на состав дополняемых вариантов (см. ниже).

Поля ответа:

* `items` — массив вариантов дополнения адреса;
    * `title` — текст варианта дополнения;
    * `is_complete` — `true` для адресов, которым не нужно дальнейшее дополнение (набран точный адрес, либо достигнута максимальная детализация адреса);
    * `tags` — присущие варианту ответа свойства (см. раздел теги).

Параметр `is_complete` помогает отличить точные адреса от промежуточных вариантов дополнения.
Например, если на Невском проспекте есть дом 11, то
при дополнении строки "Санкт-Петербург" для варианта "Санкт-Петербург, Невский проспект" `is_complete=false`,
а дополнении строки "Санкт-Петербург, Невский проспект" для варианта "Санкт-Петербург, Невский проспект 11" `is_complete=true`.
Параметр `is_complete` не учитывает параметры детализации.

Примеры запросов с ограничением детализации:

    http://fias.loc/api/complete?pattern=Москва, Невский пр.&limit=20

    В ответе будут все варианты вплоть до номеров домов:
    {
        "items": [
            {"title": "г Москва, пр Невский, 10", "is_complete": true, "tags": ["address"]},
            {"title": "г Москва, пр Невский, 11", "is_complete": true, "tags": ["address"]}
        ]
    }


    http://fias.loc/api/complete?pattern=Мос&limit=20&max_address_level=region

    В ответе будут только регионы без дальнейшей детализации:
    {
        "items": [
            {"title": "г Москва", "is_complete": true, "tags": ["address"]},
            {"title": "обл Московская", "is_complete": true, "tags": ["address"]}
        ]
    }


### `/api/validate` — валидация элемента

Пример запроса:

    http://fias.loc/api/validate?pattern=Москва, Невский пр.

    Ответ:
    {
        "items": [
            {
                "is_complete": false,
                "tags": ["address"]
            },
            {
                "is_complete": true,
                "tags": ["place", "railway"]
            }
        ]
    }

GET-параметры:

* `pattern` — проверяемый адрес.

Поля ответа:

* `items` — массив вариантов корректных объектов;
    * `is_complete` — `true` для точного адреса (вместе с домом, корпусом и т.п.);
    * `tags` — присущие варианту ответа свойства (см. раздел теги):


### `/api/postal_code_location` — получение адреса по почтовому индексу

Пример запроса:

    http://fias.loc/api/postal_code_location?postal_code=198504

    Ответ:
    {
        "address_parts": [
            {"title": "г Санкт-Петербург", "address_level": "region"},
            {"title": "р-н Петродворцовый", "address_level": "city_district"}
        ]
    }

GET-параметры:

* `postal_code` — почтовый индекс.

Поля ответа:

* `address_parts` — массив частей адреса по уровням детализации;
    * `title` — название;
    * `address_level` — уровень детализации (район, город и т.п.).

Если соединить по порядку `title` всех частей адреса в строку,
получится общий префикс для всех адресов по указанному почтовому индексу.

### `/api/address_postal_code` — получение почтового индекса по адресу

Пример запроса:

    http://fias.loc/api/address_postal_code?address=обл Псковская, р-н Новосокольнический, д Мошино

    Ответ:
    {
        "postal_code": 182200
    }

GET-параметры:

* `address` — адрес.

Поля ответа:

* `postal_code` — почтовый индекс или `null`, если индекс не найден.


### Уровни детализации частей адреса

1. `region` — регион: Санкт-Петербург, Московская область, Хабаровский край;
2. `area` — округ: пока данные отсутствуют, заложено для дальнейшей совместимости с ФИАС, когда ФИАС перенесет часть элементов из region;
3. `area_district` — район округа/региона: Волжский район, Ломоносовский район, Гатчинский район;
4. `city` — город: Петергоф, Сосновый бор, Пушкин;
5. `city_district` — район города: микрорайон № 13, Кировский район, Центральный район;
6. `settlement` — населенный пункт: поселок Парголово, станция Разлив, поселок Металлострой;
7. `street` — улица: проспект Косыгина, улица Ярославская, проспект Художников;
8. `territory` — дополнительная территория: Рябинушка снт (садовое некоммерческое товарищество), Победа гск (гаражно-строительный кооператив);
9. `sub_territory` — часть дополнительной территории: Садовая улица, 7-я линия;
10. `building` — конкретный дом (максимальная детализация).

### Теги

* `"address"` — текст найден в ФИАС;
* `"place"` — текст найден в списке places (аэропорты, вокзалы, порты и т.д.);
* `"place"` — текст найден в списке places (аэропорты, вокзалы, порты и т.д.);
* `"airport"` — аэропорт;
* `"railway_station"` — вокзал;
* `"bus_terminal"` — автовокзал;
* `"port"` — порт;
* `"airport_terminal"` — терминал аэропорта;
* `"riverside_station"` — речной вокзал.


### Выбор формата

Для указания формата необходимо добавить его к названию ресурса:

* `.json` для JSON (по умолчанию)
* `.jsonp` для JSONP. Для JSONP требуется дополнительный GET параметр callback.

Пример запроса:

    http://fias.loc/api/complete.jsonp?pattern=Невск&limit=20&callback=someFunction

    Ответ:
    someFunction(
        {
            "items": [
                {"title": "г Москва, пр Невский", "is_complete": false, "tags": ["address"]},
                {"title": "г Москва, Невское урочище", "is_complete": false, "tags": ["address"]},
                {"title": "Невский вокзал", "is_complete": true, "tags": ["place", "railway"]}
            ]
        }
    )
