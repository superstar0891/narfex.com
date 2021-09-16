normal

```js
<Dropdown
  placeholder="Placeholder"
  value="btc"
  onChange={console.log}
  onChangeValue={console.log}
  options={[
    { title: "BTC", note: "0.02112", value: "btc" },
    { title: "ETH", note: "1.511", value: "eth" },
    { title: "LTC", note: "9.1002", value: "ltc" }
  ]}
/>
```

small

```js
<Dropdown
  placeholder="Placeholder"
  value="btc"
  onChangeValue={console.log}
  size="small"
  options={[
    { title: "BTC", note: "0.02112", value: "btc" },
    { title: "ETH", note: "1.511", value: "eth" },
    { title: "LTC", note: "9.1002", value: "ltc" }
  ]}
/>
```
