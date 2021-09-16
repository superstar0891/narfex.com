```js
import { useState } from "react";
const [state, setState] = useState(false);
<Switch on={state} onChange={setState} label="Label" />;
```

```js
<Switch label="Off" />
<Switch on label="On" />
<Switch disabled label="Disabled Off" />
<Switch on disabled label="Disabled On" />
```
