```js
<Collapse title="Collapse Block" collapsed={true} onChange={console.log}>
  <p>
    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Blanditiis
    consectetur consequatur dolor dolorem dolorum est eveniet exercitationem
    fuga ipsum iste iure laboriosam libero molestias necessitatibus non numquam,
    quis vero voluptatibus?
  </p>
</Collapse>
```

```js
import Button from "../Button/Button";

<Collapse
  title="Collapse Block with control button"
  controls={
    <Button rounded type="secondary" size="ultra_small">
      Manage
    </Button>
  }
>
  <p>Lorem ipsum dolor sit amet</p>
</Collapse>;
```

```js
import React, { useState } from "react";
const [isOpen, toggle] = useState(true);

<Collapse title="Controlled Collapse" isOpen={isOpen} onChange={toggle}>
  <p>
    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Blanditiis
    consectetur consequatur dolor dolorem dolorum est eveniet exercitationem
    fuga ipsum iste iure laboriosam libero molestias necessitatibus non numquam,
    quis vero voluptatibus?
  </p>
</Collapse>;
```
