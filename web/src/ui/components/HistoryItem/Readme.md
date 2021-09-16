```js
<HistoryItem />
```

```js
<HistoryItem icon="err" type="fail" />
```

```js
<HistoryItem
  icon="ok"
  type="success"
  label="Поступление средств"
  header="7461L...c2c171"
/>
```

```js
import Status from "../Status/Status";

<HistoryItem
  icon="ok"
  type="success"
  status={<Status status="success" />}
  header="Вход с нового устройства"
  smallText="Приложение для Android. Москва, Россия, 173.683.28.38 Если вы не совершали данное действие обратитесь в службу поддержки."
/>;
```

```js
import NumberFormat from "../NumberFormat/NumberFormat";
<HistoryItem
  type="primary"
  label="Обмен валют"
  header={<NumberFormat number={-1.234} currency="btc" />}
  headerSecondary={<NumberFormat color number={123} currency="usd" />}
  smallText="Отдать Bitcoin"
  smallTextSecondary="Получить USD"
/>;
```

```js
import NumberFormat from "../NumberFormat/NumberFormat";
<HistoryItem
  type="primary"
  label="Поступление средств"
  header="Permata"
  headerSecondary={
    <NumberFormat symbol color number={1000000000} currency="idr" />
  }
/>;
```
