normal

```js
import Button from "../Button/Button";

<div>
  <InputOld placeholder="Placeholder" />
  <br />
  <InputOld indicator="USD" />
  <br />
  <InputOld
    placeholder="indicator component"
    indicator={
      <Button type="ultra_small" onClick={console.log}>
        component
      </Button>
    }
  />
  <br />
  <InputOld value="Value" />
  <br />
  <InputOld placeholder="Placeholder" multiLine />
  <br />
  <InputOld type="password" placeholder="Password" />
  <br />
  <InputOld disabled value="disabled" />
  <br />
  <InputOld
    onTextChange={console.log}
    cell
    type="number"
    placeholder="Number call"
  />
</div>;
```

Number

```js
import { useState } from "react";
const [value, setValue] = useState("0.00");

<InputOld
  onTextChange={setValue}
  value={value}
  type="number"
  placeholder="0.00"
/>;
```

Indicator & description

```js
<InputOld description="Fee: 0.1%" indicator="USD" />
```

small

```js
<InputOld size="small" description="Fee: 0.1%" indicator="USD" />
```

reliability

```js
<InputOld reliability type="password" />
```

pattern

```js
import { useState } from "react";
const [value, setValue] = useState("");

<InputOld
  pattern={/[A-Za-z ,.'-]/g}
  value={value}
  onTextChange={setValue}
  indicator="A-Z"
/>;
```
