```js
import React, { useState } from "react";
const [active, setActive] = useState(false);

<HamburgerButton
  active={active}
  onClick={() => {
    setActive(!active);
  }}
/>;
```
