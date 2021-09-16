Default icon

```js
<ActionSheet
  items={[
    { title: "Create", onClick: console.log },
    { title: "Edit", onClick: console.log },
    { title: "Delete", type: "destructive", onClick: console.log }
  ]}
/>
```

Left position

```js
<ActionSheet
  position="left"
  items={[
    { title: "Create", onClick: console.log },
    { title: "Edit", onClick: console.log },
    { title: "Delete", type: "destructive", onClick: console.log }
  ]}
/>
```

subContent

```js
<ActionSheet
  items={[
    { title: "Edit", onClick: console.log, subContent: "Cmd + E" },
    {
      title: "Delete",
      type: "destructive",
      onClick: console.log,
      subContent: "Cmd + D"
    }
  ]}
/>
```

mouseOver

```js
<ActionSheet
  mouseOver
  items={[
    { title: "Create", onClick: console.log },
    { title: "Edit", onClick: console.log },
    { title: "Delete", type: "destructive", onClick: console.log }
  ]}
/>
```

Custom element

```js
import Button from "../Button/Button";

<ActionSheet
  items={[
    { title: "Create", onClick: console.log },
    { title: "Edit", onClick: console.log },
    { title: "Delete", type: "destructive", onClick: console.log }
  ]}
>
  <Button>Action sheet</Button>
</ActionSheet>;
```
