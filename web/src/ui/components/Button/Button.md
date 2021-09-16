Simple button:

```js
import Button from "./Button";

<Button>Button</Button>;
```

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button>Button</Button>
  <Button type="secondary">Secondary</Button>
  <Button type="negative">Negative</Button>
  <Button type="lite">Button</Button>
</ButtonWrapper>;
```

Disabled

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button disabled>Button</Button>
  <Button disabled type="secondary">
    Secondary
  </Button>
  <Button disabled type="negative">
    Negative
  </Button>
  <Button disabled type="lite">
    Button
  </Button>
</ButtonWrapper>;
```

state="loading"

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button state="loading">Button</Button>
  <Button state="loading" type="secondary">
    Secondary
  </Button>
  <Button state="loading" type="negative">
    Negative
  </Button>
  <Button state="loading" type="lite">
    Button
  </Button>
</ButtonWrapper>;
```

size="large"

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button size="large">Button</Button>
  <Button size="large" type="secondary">
    Secondary
  </Button>
  <Button size="large" type="negative">
    Negative
  </Button>
  <Button size="large" type="lite">
    Button
  </Button>
</ButtonWrapper>;
```

size="extra_large"

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button size="extra_large">Button</Button>
  <Button size="extra_large" type="secondary">
    Secondary
  </Button>
  <Button size="extra_large" type="negative">
    Negative
  </Button>
  <Button size="extra_large" type="lite">
    Button
  </Button>
</ButtonWrapper>;
```

size="middle"

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button size="middle">Button</Button>
  <Button size="middle" type="secondary">
    Secondary
  </Button>
  <Button size="middle" type="negative">
    Negative
  </Button>
  <Button size="middle" type="lite">
    Button
  </Button>
</ButtonWrapper>;
```

size="small"

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button size="small">Button</Button>
  <Button size="small" type="secondary">
    Secondary
  </Button>
  <Button size="small" type="negative">
    Negative
  </Button>
  <Button size="small" type="lite">
    Button
  </Button>
</ButtonWrapper>;
```

size="ultra_small"

```js
import Button, { ButtonWrapper } from "./Button";

<ButtonWrapper>
  <Button size="ultra_small">Button</Button>
  <Button size="ultra_small" type="secondary">
    Secondary
  </Button>
  <Button size="ultra_small" type="negative">
    Negative
  </Button>
  <Button size="ultra_small" type="lite">
    Button
  </Button>
</ButtonWrapper>;
```
