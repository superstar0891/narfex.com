```js
import Radio from "./Radio";
import { useState } from "react";

const [selected, onChange] = useState("second");

<div>
  <RadioGroup selected={selected} onChange={onChange}>
    <Radio value="first">First</Radio>
    <Radio value="second">Second</Radio>
    <Radio value="last" disabled>
      Last
    </Radio>
  </RadioGroup>
  <p>{selected}</p>
</div>;
```
