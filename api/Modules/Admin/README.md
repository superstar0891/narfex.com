# Как все работает?
Сервер собирает обьект в специальном формате и отдает клиенту json, по которому рендерится админка.


# Модули
Все модули наследуются от Admin\helpers\PageContainer и реализуют обязательный метод build().

В build составляется контент модуля, создаются простые кнопки и списки, более сложные элементы типа таблиц или форм создаются в registerActions(), так-же в registerActions() создаются действия (колбеки).

# Actions

Все апи завязано на действиях (Admin\layout\Action). Действия используются для интерактивный фич, например если нужно удалить какой-то элемент на странице или открыть модальное окно.

Создание действия:
```php
$action = $this->createAction(function (array $params, array $values) {
        
});
```

Можно добавить параметры:
```php
$action->setParams(['my_param' => 123]);
```

Колбек всегда принимает два параметра:
- $params - это параметры, которые прокидываются из кода;
- $values - поля, которые пользователь вводить в ui;

В ответ нужно вернуть другое действие или массив действий. Например:
```php
$action = $this->createAction(function (array $params, array $values) {
    return $this->showToast('Message'); 
});
```

Действие можно переиспользовать:
```php
$action->use(['my_param' => $order->id]);
```

# Пример
Пример реализации простейщего раздела:
```php
class AdminSimpleModule extends Admin\helpers\PageContainer {
    public function build(){
        $button = \Admin\layout\Button::withParams('Click me');
        $this->layout->push($button);
    }
}
```

Этот код выведет кнопку с надписью "Click me".

Чтобы добавить интерактивности нужно создать действие:
```php
class AdminSimpleModule extends Admin\helpers\PageContainer {
    /* @var Admin\layout\Action */
    private $action;

    public function registerActions() {
        $this->action = $this->createAction(function (\Admin\helpers\ActionRequest $request) {
            $params = $request->getParams();
            return $this->showToast('Param: ' . $params['my_param']); // Клиент покажет тост: Param: Hello
        });
    }

    public function build(){
        $button = \Admin\layout\Button::withParams('Click me')
            ->onClick($this->action->use(['my_param' => 'Hello']));

        $this->layout->push($button);
    }
}
```

# Таблицы

Пример таблицы:

```php
$headers = ['ID', 'Amount', 'Currency'];
$this
    ->createManagedTable(PoolModel::class, $headers)
    ->setDataMapper(function (ModelSet $items) {
        return $items->map(function (PoolModel $item) {
            return [
                $item->id,
                $item->amount,
                $item->currency,
            ];
        });
    });
```

Этот код выведет таблицу с пагинацией.

# Формы

Пример формы:
```php
$this->createFormManager()
    ->setItems(
        Input::withParams('amount', 'Amount')
    )
    ->onSubmit(function (array $params, array $values) {
        $amount = (double) $values['amount'];
        return $this->showToast('Amount: ' . $amount);
    });
```

Этот код выведет форму с полем ввода количества и кнопкой Submit, при нажатии на отпарвку формы сработает метод onSubmit.