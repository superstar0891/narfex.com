```js
import { useState } from "react";
const [value, setValue] = useState([]);

<Select
  value={value}
  onChange={setValue}
  options={[
    { value: "btc", label: "Bitcoin" },
    { value: "eth", label: "Ethereum" },
    { value: "ltc", label: "Litecoin" }
  ]}
/>;
```

```js
import { useState } from "react";
const [value, setValue] = useState([]);

<Select
  value={value}
  onChange={setValue}
  isMulti
  options={[
    { value: "btc", label: "Bitcoin" },
    { value: "eth", label: "Ethereum" },
    { value: "ltc", label: "Litecoin" }
  ]}
/>;
```
